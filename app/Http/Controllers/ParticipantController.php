<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Score;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParticipantController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        return view('registration');
    }

    /**
     * Store a newly created participant in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Registration attempt started', $request->all());

        // Validate the request
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'ic' => 'required|string|unique:participants,ic',
                'phone' => 'required|string|max:20',
                'team' => 'required|string|max:255',
                'gender' => 'required|in:lelaki,wanita',
                'event_type' => 'required|in:individu,beregu,trio,berkumpulan',
                'payment_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'team_members' => 'sometimes|array',
                'team_members.*.name' => 'sometimes|string|max:255',
                'team_members.*.ic' => 'sometimes|string',
            ]);

            \Log::info('Validation passed', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        // Use database transaction for data integrity
        DB::beginTransaction();

        try {
            // Generate unique ID for participant
            $participantId = (string) Str::uuid();
            \Log::info('Generated participant ID', ['id' => $participantId]);

            // Handle payment receipt upload
            $receiptPath = null;
            if ($request->hasFile('payment_receipt')) {
                $file = $request->file('payment_receipt');
                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
                $receiptPath = $file->storeAs('receipts', $filename, 'public');
                \Log::info('Receipt uploaded', ['path' => $receiptPath]);
            }

            // Create participant
            $participant = Participant::create([
                'id' => $participantId,
                'name' => $validated['name'],
                'ic' => $validated['ic'],
                'phone' => $validated['phone'],
                'team' => $validated['team'],
                'gender' => $validated['gender'],
                'event_type' => $validated['event_type'],
                'payment_receipt' => $receiptPath,
                'status' => 'pending', // Default status for new registrations
            ]);
            \Log::info('Participant created', ['participant_id' => $participantId]);

            // Initialize empty scores for new participant
            $score = Score::create([
                'participant_id' => $participantId,
                'g1' => 0,
                'g2' => 0,
                'g3' => 0,
                'g4' => 0,
                'g5' => 0,
                'total' => 0,
                'average' => 0,
            ]);
            \Log::info('Score initialized', ['score_id' => $score->id]);

            // Save team members if event type requires it (beregu/trio/berkumpulan)
            if (in_array($validated['event_type'], ['beregu', 'trio', 'berkumpulan']) && isset($validated['team_members'])) {
                $memberOrder = 1;
                foreach ($validated['team_members'] as $member) {
                    TeamMember::create([
                        'participant_id' => $participantId,
                        'name' => $member['name'],
                        'ic' => $member['ic'],
                        'member_order' => $memberOrder++,
                    ]);
                }
                \Log::info('Team members saved', ['count' => count($validated['team_members'])]);
            }

            DB::commit();
            \Log::info('Transaction committed successfully');

            return redirect()
                ->route('registration')
                ->with('success', 'Pendaftaran berjaya! Peserta telah didaftarkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('registration')
                ->with('error', 'Ralat berlaku semasa pendaftaran. Sila cuba lagi.')
                ->withInput();
        }
    }
}
