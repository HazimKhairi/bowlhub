<?php

namespace App\Http\Controllers;

use App\Imports\IndividualImport;
use App\Imports\TeamBereguImport;
use App\Imports\TeamBerkumpulanImport;
use App\Imports\TeamTrioImport;
use App\Models\Participant;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        return view('admin');
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
        $allowedTypes = ['individual', 'team-beregu', 'team-trio', 'team-berkumpulan'];

        if (! in_array($type, $allowedTypes)) {
            abort(404, 'Jenis template tidak sah');
        }

        $fileName = str_replace('team-', '', $type).'.xlsx';
        $filePath = storage_path('app/public/templates/'.$fileName);

        if (! file_exists($filePath)) {
            abort(404, 'Template tidak dijumpai');
        }

        return Response::download($filePath, $fileName);
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
}
