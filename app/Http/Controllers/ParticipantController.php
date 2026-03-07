<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\TeamMember;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ic' => 'required|string|unique:participants,ic',
            'phone' => 'required|string|max:20',
            'team' => 'required|string|max:255',
            'gender' => 'required|in:lelaki,wanita',
            'event_type' => 'required|in:individu,beregu,trio,berkumpulan',
            'team_members' => 'array',
            'team_members.*.name' => 'required|string|max:255',
            'team_members.*.ic' => 'required|string',
        ]);

        // Use database transaction for data integrity
        DB::beginTransaction();

        try {
            // Generate unique ID for participant
            $participantId = (string) Str::uuid();

            // Create participant
            $participant = Participant::create([
                'id' => $participantId,
                'name' => $validated['name'],
                'ic' => $validated['ic'],
                'phone' => $validated['phone'],
                'team' => $validated['team'],
                'gender' => $validated['gender'],
                'event_type' => $validated['event_type'],
            ]);

            // Initialize empty scores for new participant
            Score::create([
                'participant_id' => $participantId,
                'g1' => 0,
                'g2' => 0,
                'g3' => 0,
                'g4' => 0,
                'g5' => 0,
                'total' => 0,
                'average' => 0,
            ]);

            // Save team members if event type requires it (beregu/trio)
            if (in_array($validated['event_type'], ['beregu', 'trio']) && isset($validated['team_members'])) {
                $memberOrder = 1;
                foreach ($validated['team_members'] as $member) {
                    TeamMember::create([
                        'participant_id' => $participantId,
                        'name' => $member['name'],
                        'ic' => $member['ic'],
                        'member_order' => $memberOrder++,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('registration')
                ->with('success', 'Pendaftaran berjaya! Peserta telah didaftarkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('registration')
                ->with('error', 'Ralat berlaku semasa pendaftaran. Sila cuba lagi.')
                ->withInput();
        }
    }
}
