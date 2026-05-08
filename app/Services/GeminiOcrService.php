<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiOcrService
{
    /** API key pool — tried in order, fallback on rate-limit/quota errors. */
    protected array $keys;

    protected string $model;

    protected int $timeout;

    protected string $endpoint;

    /** Working directory for PDF→image conversion. */
    protected string $workDir;

    public function __construct()
    {
        $this->keys = config('services.gemini.keys', []);
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        $this->timeout = (int) config('services.gemini.timeout', 60);
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $this->workDir = storage_path('app/ocr-tmp');
        if (! is_dir($this->workDir)) {
            mkdir($this->workDir, 0755, true);
        }

        if (empty($this->keys)) {
            throw new \RuntimeException('Tiada GEMINI_API_KEY dikonfigurasikan. Set di .env');
        }
    }

    /**
     * Process a PDF/image file end-to-end via Gemini Vision API.
     *
     * @return array{raw_text: string, pages: array<int, string>, parsed: array<int, array<string, mixed>>}
     */
    public function process(string $filePath, ?string $hintExt = null): array
    {
        $ext = strtolower($hintExt ?? pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) && function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if ($mime === 'application/pdf') {
                $ext = 'pdf';
            } elseif (str_starts_with((string) $mime, 'image/')) {
                $ext = 'jpg';
            }
        }

        // Convert PDF to images (multi-page support); use image directly otherwise
        $imagePaths = $ext === 'pdf'
            ? $this->convertPdfToImages($filePath)
            : [$filePath];

        $allRows = [];
        $combinedRaw = '';
        $pageTexts = [];
        $tempImages = $ext === 'pdf' ? $imagePaths : [];

        try {
            foreach ($imagePaths as $idx => $imgPath) {
                $page = $idx + 1;
                $extracted = $this->extractFromImage($imgPath);
                $pageTexts[$page] = $extracted['raw'] ?? '';
                $combinedRaw .= "\n=== Page {$page} ===\n".($extracted['raw'] ?? '');

                foreach ($extracted['rows'] as $row) {
                    $allRows[] = $row;
                }
            }
        } finally {
            foreach ($tempImages as $img) {
                @unlink($img);
            }
        }

        return [
            'raw_text' => trim($combinedRaw),
            'pages' => $pageTexts,
            'parsed' => $allRows,
        ];
    }

    /**
     * Call Gemini Vision API for a single image. Auto-fallback to next key on quota/rate-limit.
     *
     * @return array{rows: array<int, array<string, mixed>>, raw: string}
     */
    protected function extractFromImage(string $imagePath): array
    {
        $base64 = base64_encode(file_get_contents($imagePath));
        $mime = $this->detectMime($imagePath);

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $this->buildPrompt()],
                    ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->buildJsonSchema(),
                'temperature' => 0.1, // Low temperature for accurate extraction
            ],
        ];

        $lastError = null;
        foreach ($this->keys as $idx => $key) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'x-goog-api-key' => $key,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->endpoint, $payload);

                if ($response->successful()) {
                    $keyLabel = 'KEY'.($idx === 0 ? '' : ($idx + 1));
                    Log::info("Gemini OCR success via {$keyLabel}");

                    return $this->parseResponse($response->json());
                }

                // Fallback triggers: 429 (rate limit), 403 (quota), 503 (overloaded)
                $status = $response->status();
                $errorBody = $response->body();

                if (in_array($status, [429, 403, 503], true)) {
                    Log::warning('Gemini API key #'.($idx + 1)." failed (HTTP {$status}), trying next", [
                        'error' => substr($errorBody, 0, 200),
                    ]);
                    $lastError = "HTTP {$status}: ".substr($errorBody, 0, 200);

                    continue;
                }

                // Non-recoverable error (400, 401, etc.) — don't try other keys
                throw new \RuntimeException("Gemini API error (HTTP {$status}): ".substr($errorBody, 0, 300));
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning('Gemini API connection failed, trying next key', ['error' => $e->getMessage()]);
                $lastError = $e->getMessage();

                continue;
            }
        }

        throw new \RuntimeException('Semua Gemini API key gagal. Last error: '.$lastError);
    }

    /**
     * Parse Gemini structured JSON response into our row format.
     *
     * @return array{rows: array<int, array<string, mixed>>, raw: string}
     */
    protected function parseResponse(array $json): array
    {
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $data = json_decode($text, true);
        if (! is_array($data)) {
            Log::warning('Gemini returned non-JSON', ['text' => substr($text, 0, 500)]);

            return ['rows' => [], 'raw' => $text];
        }

        $rows = [];
        $rawRows = $data['rows'] ?? [];

        foreach ($rawRows as $r) {
            $nickname = trim((string) ($r['nickname'] ?? $r['name'] ?? ''));
            if ($nickname === '') {
                continue;
            }

            $g = [
                (int) ($r['g1'] ?? 0),
                (int) ($r['g2'] ?? 0),
                (int) ($r['g3'] ?? 0),
                (int) ($r['g4'] ?? 0),
                (int) ($r['g5'] ?? 0),
            ];

            // Validate scores 0-300
            foreach ($g as $s) {
                if ($s < 0 || $s > 300) {
                    continue 2;
                }
            }

            $rows[] = [
                'nickname' => $nickname,
                'g1' => $g[0],
                'g2' => $g[1],
                'g3' => $g[2],
                'g4' => $g[3],
                'g5' => $g[4],
                'total' => array_sum($g),
                'raw_line' => $r['raw_line'] ?? '',
            ];
        }

        return ['rows' => $rows, 'raw' => $text];
    }

    /**
     * Build the extraction prompt tailored for bowling scoreboards.
     */
    protected function buildPrompt(): string
    {
        return <<<'PROMPT'
You are a bowling scoreboard OCR extractor. Analyze the attached image (which may be a photograph, scan, or screenshot of a bowling tournament scoreboard) and extract all participant scores.

Rules:
1. Each row contains a player. Extract the player's NAME (or NICKNAME) — this is the human name, NOT a number/index.
2. Extract up to 5 game scores per player. Common formats:
   - 2 games: GAME 1, GAME 2 (set g3, g4, g5 to 0)
   - 3 games: G1, G2, G3 (set g4, g5 to 0)
   - 5 games: G1 through G5
3. SKIP these columns/values:
   - Row index numbers (NO, ROW, #) — these are 1-99
   - Team numbers (TEAM) — usually 1-20
   - TOTAL or SUM columns — these are calculated, not games
   - Header rows (NAME, GAME 1, etc.)
   - Title rows (tournament name, date, category)
4. Game scores in bowling are 0-300. If you see a number > 300, it's probably the TOTAL — skip it.
5. If unclear or partially visible, output your best guess but mark with `raw_line` showing the original.
6. Names should be cleaned: trim spaces, preserve case as shown.

Return ONLY valid JSON matching the provided schema. No markdown, no explanations.
PROMPT;
    }

    /**
     * JSON schema enforced by Gemini's structured output mode.
     */
    protected function buildJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'rows' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nickname' => ['type' => 'string', 'description' => 'Player name as shown'],
                            'g1' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 300],
                            'g2' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 300],
                            'g3' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 300],
                            'g4' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 300],
                            'g5' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 300],
                            'raw_line' => ['type' => 'string', 'description' => 'Original line text for reference'],
                        ],
                        'required' => ['nickname', 'g1', 'g2', 'g3', 'g4', 'g5'],
                    ],
                ],
            ],
            'required' => ['rows'],
        ];
    }

    /**
     * Convert PDF to PNG images at 300 DPI using pdftoppm.
     *
     * @return array<int, string>
     */
    protected function convertPdfToImages(string $pdfPath): array
    {
        $prefix = $this->workDir.'/'.\Illuminate\Support\Str::uuid();
        $cmd = sprintf(
            'pdftoppm -r 300 -png %s %s 2>&1',
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('PDF conversion failed: '.implode("\n", $output));
        }

        $images = glob($prefix.'-*.png');
        sort($images);

        return $images;
    }

    /**
     * Detect MIME type for inline_data payload.
     */
    protected function detectMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => function_exists('mime_content_type') ? (mime_content_type($path) ?: 'image/png') : 'image/png',
        };
    }
}
