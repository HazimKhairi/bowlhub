<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\IndividualImport;
use App\Imports\ScoreImport;
use App\Imports\TeamBereguImport;
use App\Imports\TeamBerkumpulanImport;
use App\Imports\TeamTrioImport;
use App\Models\Participant;
use App\Models\PendingScoreImport;
use App\Models\Score;
use App\Services\GeminiOcrService;
use App\Services\ScoreOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $unmatchedCount = PendingScoreImport::pending()->count();

        return view('admin', compact('unmatchedCount'));
    }

    /**
     * Get all participants with optional filters.
     */
    public function participants(Request $request)
    {
        $query = Participant::query();

        // Apply filters if provided
        if ($request->has('event_type') && $request->event_type) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // Eager load score and team members (for berkumpulan events)
        $participants = $query->with('score')->with(['teamMembers' => function ($query) {
            $query->orderBy('member_order');
        }])->get();

        return response()->json($participants);
    }

    /**
     * Show the score edit form for a participant.
     */
    public function editScore(string $participantId): View
    {
        $participant = Participant::with('score')
            ->with(['teamMembers' => function ($query) {
                $query->orderBy('member_order');
            }])
            ->findOrFail($participantId);

        return view('admin.score-edit', compact('participant'));
    }

    /**
     * Update the score for a participant.
     */
    public function updateScore(Request $request, string $participantId)
    {
        $participant = Participant::findOrFail($participantId);

        // Validate the request
        $validated = $request->validate([
            'g1' => 'required|integer|min:0|max:300',
            'g2' => 'required|integer|min:0|max:300',
            'g3' => 'required|integer|min:0|max:300',
            'g4' => 'required|integer|min:0|max:300',
            'g5' => 'required|integer|min:0|max:300',
        ]);

        // Find or create score record
        $score = Score::where('participant_id', $participantId)->first();

        if (! $score) {
            $score = new Score;
            $score->participant_id = $participantId;
        }

        // Update score values
        $score->g1 = $validated['g1'];
        $score->g2 = $validated['g2'];
        $score->g3 = $validated['g3'];
        $score->g4 = $validated['g4'];
        $score->g5 = $validated['g5'];

        // Total and average will be automatically calculated by the model's booted method
        $score->save();

        return response()->json([
            'success' => true,
            'message' => 'Skor berjaya disimpan!',
            'score' => $score->load('participant'),
        ]);
    }

    /**
     * Delete a participant.
     */
    public function deleteParticipant(string $participantId)
    {
        $participant = Participant::findOrFail($participantId);

        // Delete associated score if exists
        $score = Score::where('participant_id', $participantId)->first();
        if ($score) {
            $score->delete();
        }

        $participant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Peserta berjaya dipadam.',
        ]);
    }

    /**
     * Approve a pending registration.
     */
    public function approveParticipant($id)
    {
        try {
            $participant = Participant::findOrFail($id);

            if ($participant->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Peserta sudah diluluskan',
                ]);
            }

            $participant->status = 'approved';
            $participant->save();

            \Log::info('Participant approved', ['participant_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Peserta berjaya diluluskan',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error approving participant', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ralat meluluskan peserta',
            ]);
        }
    }

    /**
     * Download Excel template for import.
     */
    public function downloadTemplate(string $type)
    {
        $allowedTypes = ['individual', 'team-beregu', 'team-trio', 'team-berkumpulan', 'score-import'];

        if (! in_array($type, $allowedTypes)) {
            abort(404, 'Jenis template tidak sah');
        }

        $fileName = match ($type) {
            'team-beregu' => 'beregu.xlsx',
            'team-trio' => 'trio.xlsx',
            'team-berkumpulan' => 'berkumpulan.xlsx',
            'score-import' => 'score-import.xlsx',
            default => 'individual.xlsx',
        };

        return Excel::download(new TemplateExport($type), $fileName);
    }

    /**
     * Import Excel file.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'type' => 'required|in:individual,team-beregu,team-trio,team-berkumpulan',
        ]);

        $import = null;

        try {
            $type = $request->input('type');
            $importClass = match ($type) {
                'individual' => IndividualImport::class,
                'team-beregu' => TeamBereguImport::class,
                'team-trio' => TeamTrioImport::class,
                'team-berkumpulan' => TeamBerkumpulanImport::class,
            };

            $import = new $importClass;
            Excel::import($import, $request->file('file'));

            if ($import->hasErrors()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import selesai dengan ralat. Sila semak ralat di bawah.',
                    'errors' => $import->getErrors(),
                    'results' => $import->getResults(),
                ]);
            }

            $results = $import->getResults();
            $totalProcessed = $results['created'] + $results['updated'];
            $errorCount = count($import->getErrors());

            if ($totalProcessed > 0) {
                $message = $errorCount > 0
                    ? "Import selesai. {$results['created']} peserta baharu, {$results['updated']} peserta dikemaskini. {$errorCount} baris mengalami ralat."
                    : "Import berjaya! {$results['created']} peserta baharu, {$results['updated']} peserta dikemaskini.";
            } else {
                $message = 'Import gagal. Tiada data sah untuk diimport.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
                'errors' => $import->getErrors(),
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $row = $failure->row(); // 1-based row number
                $attribute = $failure->attribute();
                $errors[] = "Baris {$row}: {$failure->errors()[0]} (Column: {$attribute})";
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Sila semak ralat di bawah.',
                'errors' => $errors,
                'results' => $import ? $import->getResults() : ['created' => 0, 'updated' => 0, 'created_ics' => [], 'updated_ics' => []],
            ]);

        } catch (\Exception $e) {
            \Log::error('Excel import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ralat berlaku semasa import: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Show the score import page.
     */
    public function scoreImportPage(): View
    {
        $unmatchedCount = PendingScoreImport::pending()->count();

        return view('admin.score-import', compact('unmatchedCount'));
    }

    /**
     * Import scores by nickname matching.
     */
    public function importScores(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $import = new ScoreImport;
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $errors = $import->getErrors();

            $message = "Selesai. {$results['matched']} skor dipadan dan disimpan, ".
                "{$results['unmatched']} memerlukan semakan manual.";

            if ($results['invalid'] > 0) {
                $message .= " {$results['invalid']} baris tidak sah.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
                'errors' => $errors,
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errors = [];
            foreach ($e->failures() as $failure) {
                $row = $failure->row();
                $errors[] = "Baris {$row}: {$failure->errors()[0]} (Column: {$failure->attribute()})";
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Sila semak ralat di bawah.',
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            \Log::error('Score import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ralat berlaku semasa import skor: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Show unmatched scores for manual review.
     */
    public function unmatchedScores(): View
    {
        $unmatched = PendingScoreImport::pending()
            ->orderByDesc('created_at')
            ->get();

        $participants = Participant::orderBy('name')
            ->get(['id', 'name', 'nickname', 'team', 'event_type']);

        return view('admin.unmatched-scores', compact('unmatched', 'participants'));
    }

    /**
     * Resolve an unmatched score by linking it to a participant.
     */
    public function resolveUnmatched(Request $request, int $id)
    {
        $validated = $request->validate([
            'participant_id' => 'required|string|exists:participants,id',
        ]);

        $pending = PendingScoreImport::pending()->findOrFail($id);

        DB::transaction(function () use ($pending, $validated) {
            $score = Score::firstOrNew(['participant_id' => $validated['participant_id']]);
            $score->g1 = $pending->g1;
            $score->g2 = $pending->g2;
            $score->g3 = $pending->g3;
            $score->g4 = $pending->g4;
            $score->g5 = $pending->g5;
            $score->save();

            $pending->status = 'resolved';
            $pending->resolved_participant_id = $validated['participant_id'];
            $pending->resolved_at = now();
            $pending->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Skor berjaya dipadankan kepada peserta.',
        ]);
    }

    /**
     * Discard an unmatched score record.
     */
    public function discardUnmatched(int $id)
    {
        $pending = PendingScoreImport::pending()->findOrFail($id);
        $pending->status = 'discarded';
        $pending->save();

        return response()->json([
            'success' => true,
            'message' => 'Rekod dibuang.',
        ]);
    }

    /**
     * Show the OCR upload form.
     */
    public function ocrUploadForm(): View
    {
        return view('admin.score-ocr');
    }

    /**
     * Process uploaded PDF/image with Gemini Vision API. Falls back to Tesseract if Gemini unavailable.
     */
    public function ocrPreview(Request $request, GeminiOcrService $gemini, ScoreOcrService $tesseract)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $upload = $request->file('file');
        $tmpPath = $upload->getRealPath();
        $hintExt = strtolower($upload->getClientOriginalExtension() ?: 'pdf');
        $engine = 'gemini';

        try {
            $result = $gemini->process($tmpPath, $hintExt);
        } catch (\Throwable $e) {
            \Log::warning('Gemini OCR failed, falling back to Tesseract', ['error' => $e->getMessage()]);
            $engine = 'tesseract (fallback)';
            try {
                $result = $tesseract->process($tmpPath, $hintExt);
            } catch (\Throwable $e2) {
                \Log::error('Both OCR engines failed', ['gemini' => $e->getMessage(), 'tesseract' => $e2->getMessage()]);

                return back()->with('error', 'OCR gagal: '.$e2->getMessage());
            }
        }

        return view('admin.score-ocr-preview', [
            'rawText' => $result['raw_text'],
            'pages' => $result['pages'],
            'parsed' => $result['parsed'],
            'engine' => $engine,
        ]);
    }

    /**
     * Confirm OCR-extracted scores: auto-match by nickname, send unmatched to pending.
     */
    public function ocrConfirm(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array',
            'rows.*.nickname' => 'required|string|max:100',
            'rows.*.g1' => 'required|integer|min:0|max:300',
            'rows.*.g2' => 'required|integer|min:0|max:300',
            'rows.*.g3' => 'required|integer|min:0|max:300',
            'rows.*.g4' => 'required|integer|min:0|max:300',
            'rows.*.g5' => 'required|integer|min:0|max:300',
        ]);

        $batchId = (string) Str::uuid();
        $matched = 0;
        $unmatched = 0;
        $errors = [];

        foreach ($validated['rows'] as $idx => $row) {
            $nickname = trim($row['nickname']);

            $participant = Participant::where('nickname', $nickname)->first();

            if ($participant) {
                $score = Score::firstOrNew(['participant_id' => $participant->id]);
                $score->g1 = $row['g1'];
                $score->g2 = $row['g2'];
                $score->g3 = $row['g3'];
                $score->g4 = $row['g4'];
                $score->g5 = $row['g5'];
                $score->save();
                $matched++;
            } else {
                PendingScoreImport::create([
                    'batch_id' => $batchId,
                    'nickname' => $nickname,
                    'g1' => $row['g1'],
                    'g2' => $row['g2'],
                    'g3' => $row['g3'],
                    'g4' => $row['g4'],
                    'g5' => $row['g5'],
                    'reason' => 'no_match',
                    'status' => 'pending',
                    'row_number' => $idx + 1,
                ]);
                $unmatched++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "OCR import selesai: {$matched} skor dipadan, {$unmatched} memerlukan semakan manual.",
            'matched' => $matched,
            'unmatched' => $unmatched,
        ]);
    }
}
