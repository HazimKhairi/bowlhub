<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $participants = $query->with('score')->get();

        return response()->json($participants);
    }

    /**
     * Show the score edit form for a participant.
     */
    public function editScore(string $participantId): View
    {
        $participant = Participant::with('score')->findOrFail($participantId);

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

        if (!$score) {
            $score = new Score();
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
            'score' => $score->load('participant')
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
            'message' => 'Peserta berjaya dipadam.'
        ]);
    }
}
