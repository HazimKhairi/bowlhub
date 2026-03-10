<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Score;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * Display the leaderboard page.
     */
    public function index(): View
    {
        return view('leaderboard');
    }

    /**
     * Get event results for the leaderboard.
     */
    public function getEventResults(string $eventType, string $gender): JsonResponse
    {
        $query = Participant::with(['score', 'teamMembers' => function ($query) {
            $query->orderBy('member_order');
        }])
            ->where('event_type', $eventType)
            ->where('gender', $gender)
            ->whereHas('score', function ($query) {
                $query->whereNotNull('total')
                    ->where('total', '>', 0);
            });

        $participants = $query->get();

        // Sort by total score descending
        $ranked = $participants->sortByDesc(function ($participant) {
            return $participant->score ? $participant->score->total : 0;
        })->values();

        // Add rank to each participant
        $ranked = $ranked->map(function ($participant, $index) {
            $participant->rank = $index + 1;
            return $participant;
        });

        // Format response based on event type
        return response()->json([
            'eventType' => $eventType,
            'gender' => $gender,
            'participants' => $ranked->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'team' => $participant->team,
                    'gender' => $participant->gender,
                    'eventType' => $participant->event_type,
                    'rank' => $participant->rank,
                    'scores' => [
                        'g1' => $participant->score->g1 ?? 0,
                        'g2' => $participant->score->g2 ?? 0,
                        'g3' => $participant->score->g3 ?? 0,
                        'g4' => $participant->score->g4 ?? 0,
                        'g5' => $participant->score->g5 ?? 0,
                    ],
                    'total' => $participant->score->total ?? 0,
                    'average' => $participant->score->average ?? 0,
                    'teamMembers' => $participant->teamMembers->map(function ($member) {
                        return [
                            'name' => $member->name,
                            'ic' => $member->ic,
                        ];
                    })->toArray(),
                ];
            }),
        ]);
    }

    /**
     * Get medal standings.
     */
    public function getMedalStandings(): JsonResponse
    {
        $eventTypes = ['individu', 'beregu', 'trio', 'berkumpulan'];
        $genders = ['lelaki', 'wanita'];

        $medalCounts = [];

        foreach ($eventTypes as $eventType) {
            foreach ($genders as $gender) {
                $participants = Participant::with('score')
                    ->where('event_type', $eventType)
                    ->where('gender', $gender)
                    ->whereHas('score', function ($query) {
                        $query->whereNotNull('total')
                            ->where('total', '>', 0);
                    })
                    ->get()
                    ->sortByDesc(function ($participant) {
                        return $participant->score ? $participant->score->total : 0;
                    })
                    ->take(5);

                foreach ($participants as $index => $participant) {
                    $team = $participant->team;

                    if (!isset($medalCounts[$team])) {
                        $medalCounts[$team] = [
                            'emas' => 0,
                            'perak' => 0,
                            'gangsa' => 0,
                            'fourth' => 0,
                            'fifth' => 0,
                        ];
                    }

                    if ($index === 0) {
                        $medalCounts[$team]['emas']++;
                    } elseif ($index === 1) {
                        $medalCounts[$team]['perak']++;
                    } elseif ($index === 2) {
                        $medalCounts[$team]['gangsa']++;
                    } elseif ($index === 3) {
                        $medalCounts[$team]['fourth']++;
                    } elseif ($index === 4) {
                        $medalCounts[$team]['fifth']++;
                    }
                }
            }
        }

        // Sort by gold, then silver, then bronze
        uasort($medalCounts, function ($a, $b) {
            if ($b['emas'] !== $a['emas']) {
                return $b['emas'] - $a['emas'];
            }
            if ($b['perak'] !== $a['perak']) {
                return $b['perak'] - $a['perak'];
            }
            return $b['gangsa'] - $a['gangsa'];
        });

        $standings = [];
        $rank = 1;
        foreach ($medalCounts as $team => $medals) {
            $standings[] = [
                'rank' => $rank++,
                'team' => $team,
                'emas' => $medals['emas'],
                'perak' => $medals['perak'],
                'gangsa' => $medals['gangsa'],
                'fourth' => $medals['fourth'],
                'fifth' => $medals['fifth'],
            ];
        }

        return response()->json([
            'standings' => $standings,
        ]);
    }

    /**
     * Get rank label based on rank position.
     */
    private function getRankLabel(int $rank): string
    {
        $labels = [
            1 => 'JOHAN',
            2 => 'NAIB JOHAN',
            3 => 'KETIGA',
        ];

        return $labels[$rank] ?? "KE-{$rank}";
    }

    /**
     * Get rank CSS class based on rank position.
     */
    private function getRankClass(int $rank): string
    {
        if ($rank === 1) {
            return 'rank-1';
        } elseif ($rank === 2) {
            return 'rank-2';
        } elseif ($rank === 3) {
            return 'rank-3';
        }

        return 'rank-other';
    }
}
