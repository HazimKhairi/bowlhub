<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ScoreOcrService
{
    /** Working directory for OCR temp files. */
    protected string $workDir;

    public function __construct()
    {
        $this->workDir = storage_path('app/ocr-tmp');
        if (! is_dir($this->workDir)) {
            mkdir($this->workDir, 0755, true);
        }
    }

    /**
     * Process a PDF or image file end-to-end.
     *
     * @param  string|null  $hintExt  optional extension hint (pdf|jpg|png) when file has no extension
     *
     * @return array{raw_text: string, pages: array<int, string>, parsed: array<int, array<string, mixed>>}
     */
    public function process(string $filePath, ?string $hintExt = null): array
    {
        $ext = strtolower($hintExt ?? pathinfo($filePath, PATHINFO_EXTENSION));

        // Fallback: detect via MIME type
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) && function_exists('mime_content_type')) {
            $mime = @mime_content_type($filePath);
            if ($mime === 'application/pdf') {
                $ext = 'pdf';
            } elseif (str_starts_with((string) $mime, 'image/')) {
                $ext = 'jpg';
            }
        }

        // Convert PDF to images, or use image directly
        $imagePaths = $ext === 'pdf'
            ? $this->convertPdfToImages($filePath)
            : [$filePath];

        $pageTexts = [];
        $combinedText = '';

        foreach ($imagePaths as $idx => $imagePath) {
            $preprocessed = $this->preprocessImage($imagePath);
            $text = $this->extractText($preprocessed);
            $pageTexts[$idx + 1] = $text;
            $combinedText .= "\n=== Page ".($idx + 1)." ===\n".$text;
        }

        $parsed = $this->parseScores($combinedText);

        // Cleanup temp images (only PDF-derived ones)
        if ($ext === 'pdf') {
            foreach ($imagePaths as $img) {
                @unlink($img);
            }
        }

        return [
            'raw_text' => $combinedText,
            'pages' => $pageTexts,
            'parsed' => $parsed,
        ];
    }

    /**
     * Convert PDF to PNG images at 300 DPI using pdftoppm.
     *
     * @return array<int, string> Image file paths
     */
    protected function convertPdfToImages(string $pdfPath): array
    {
        $prefix = $this->workDir.'/'.Str::uuid();
        $cmd = sprintf(
            'pdftoppm -r 300 -png %s %s 2>&1',
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::error('pdftoppm failed', ['cmd' => $cmd, 'output' => $output]);
            throw new \RuntimeException('PDF conversion failed: '.implode("\n", $output));
        }

        $images = glob($prefix.'-*.png');
        sort($images);

        return $images;
    }

    /**
     * Aggressive preprocessing for photographed tables/scoreboards:
     * grayscale → auto-level → deskew → despeckle → threshold (binarize) → sharpen.
     * Helps Tesseract handle phone-camera photos with table borders.
     */
    protected function preprocessImage(string $imagePath): string
    {
        $output = $imagePath.'-pre.png';
        $cmd = sprintf(
            'convert %s -colorspace Gray -auto-level -deskew 40%% -despeckle -sharpen 0x1.5 -threshold 60%% %s 2>&1',
            escapeshellarg($imagePath),
            escapeshellarg($output)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($output)) {
            Log::warning('ImageMagick preprocess failed (using original)', ['output' => $out]);

            return $imagePath;
        }

        return $output;
    }

    /**
     * Run Tesseract OCR on an image. PSM 6 = uniform block of text (preserves row order).
     */
    protected function extractText(string $imagePath): string
    {
        try {
            return (new TesseractOCR($imagePath))
                ->lang('eng')
                ->psm(6)
                ->run();
        } catch (\Throwable $e) {
            Log::error('Tesseract OCR failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('OCR failed: '.$e->getMessage());
        }
    }

    /**
     * Parse OCR text to extract structured score rows.
     *
     * Supports multiple formats (auto-detected per line):
     *   - "NO TEAM NAME G1 G2 TOTAL"     (2-game tournament)
     *   - "NO TEAM NAME G1 G2 G3 TOTAL"  (3-game)
     *   - "nickname G1 G2 G3 G4 G5"      (5-game with nickname)
     *   - "nickname G1 G2 G3 G4 G5 TOT"  (5-game with total)
     *
     * Strips table borders ('|', table chars) before parsing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseScores(string $text): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\n|\r/', $text);

        foreach ($lines as $line) {
            // Remove table border chars and normalize whitespace
            $clean = preg_replace('/[|\[\]]+/', ' ', $line);
            $clean = trim(preg_replace('/\s+/', ' ', $clean));

            if ($clean === '' || stripos($clean, 'page') === 0) {
                continue;
            }

            // Skip header/title lines (no digits or all caps wording)
            if (! preg_match('/\d{1,3}/', $clean)) {
                continue;
            }

            // Extract all 1-3 digit numbers and the longest name-like token
            preg_match_all('/\b\d{1,3}\b/', $clean, $numMatches);
            $numbers = array_map('intval', $numMatches[0]);

            // Need at least 2 valid scores to be a score row
            $validScores = array_filter($numbers, fn ($n) => $n >= 0 && $n <= 300);
            if (count($validScores) < 2) {
                continue;
            }

            // Find name: longest alphabetic token (≥3 letters, not all-digit)
            preg_match_all('/[A-Za-z][A-Za-z\-_.]{2,}/', $clean, $nameMatches);
            $candidates = array_filter($nameMatches[0], function ($n) {
                $skip = ['no', 'team', 'name', 'game', 'total', 'kategori', 'individu',
                    'beregu', 'trio', 'page', 'wednesday', 'monday', 'tuesday', 'thursday',
                    'friday', 'saturday', 'sunday', 'ampang', 'superbowl', 'kluang',
                    'kuala', 'lumpur', 'malaysia', 'avg', 'average', 'tarikh', 'lane'];

                return ! in_array(strtolower($n), $skip, true) && strlen($n) >= 3;
            });

            if (empty($candidates)) {
                continue;
            }

            // Pick longest name candidate (usually the participant's name)
            usort($candidates, fn ($a, $b) => strlen($b) - strlen($a));
            $nickname = $candidates[0];

            // Filter scores: drop "NO" (1-2 digit row index) at start, drop "TEAM" (small int) if present,
            // drop "TOTAL" (large sum) at end
            $scores = $this->extractGameScores(array_values($validScores));

            if (count($scores) < 2) {
                continue;
            }

            // Pad to 5 games
            $g = array_pad($scores, 5, 0);
            $row = $this->buildRow($nickname, [$g[0], $g[1], $g[2], $g[3], $g[4]], $clean);
            if ($row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Heuristically extract game scores from a number list.
     * Drops likely NO/TEAM index numbers at start and TOTAL at end.
     * Bowling game scores typically 50-300; NO/TEAM are usually <50.
     */
    protected function extractGameScores(array $numbers): array
    {
        if (count($numbers) <= 1) {
            return $numbers;
        }

        // Step 1: Drop leading "NO TEAM" indices (small numbers <50) before larger scores appear
        while (count($numbers) >= 3 && $numbers[0] < 50) {
            // Check if rest has at least 2 game-like scores (≥50)
            $rest = array_slice($numbers, 1);
            $gameScoresInRest = count(array_filter($rest, fn ($n) => $n >= 50));
            if ($gameScoresInRest >= 2) {
                $numbers = $rest;
            } else {
                break;
            }
        }

        // Step 2: Drop trailing TOTAL if matches sum of preceding
        $count = count($numbers);
        if ($count >= 3) {
            $potentialTotal = end($numbers);
            $rest = array_slice($numbers, 0, -1);
            if ($potentialTotal === array_sum($rest)) {
                $numbers = $rest;
            }
            // Also handle: 3 numbers where last is much larger than others = total
            elseif ($count === 3 && $potentialTotal > $numbers[0] && $potentialTotal > $numbers[1]
                && abs($potentialTotal - ($numbers[0] + $numbers[1])) <= 5) {
                $numbers = [$numbers[0], $numbers[1]];
            }
        }

        return $numbers;
    }

    /**
     * Build a parsed row, validating score ranges.
     *
     * @return array<string, mixed>|null
     */
    protected function buildRow(string $nickname, array $scores, string $rawLine): ?array
    {
        $scores = array_map('intval', $scores);

        // Validate: all scores 0-300
        foreach ($scores as $s) {
            if ($s < 0 || $s > 300) {
                return null;
            }
        }

        // Skip nicknames that look like header words
        $skipWords = ['game', 'total', 'avg', 'average', 'name', 'nickname', 'frame', 'lane'];
        if (in_array(strtolower($nickname), $skipWords, true)) {
            return null;
        }

        return [
            'nickname' => $nickname,
            'g1' => $scores[0],
            'g2' => $scores[1],
            'g3' => $scores[2],
            'g4' => $scores[3],
            'g5' => $scores[4],
            'total' => array_sum($scores),
            'raw_line' => $rawLine,
        ];
    }
}
