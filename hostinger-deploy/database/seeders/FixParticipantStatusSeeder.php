<?php

namespace Database\Seeders;

use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixParticipantStatusSeeder extends Seeder
{
    /**
     * Fix existing participant status based on their scores.
     *
     * Logic:
     * - If participant has any game score > 0, set status = 'approved'
     * - Otherwise, set status = 'pending'
     */
    public function run(): void
    {
        $this->command->info('Fixing participant statuses...');

        // Get all participants with their scores
        $participants = Participant::with('score')->get();

        $approvedCount = 0;
        $pendingCount = 0;

        foreach ($participants as $participant) {
            $hasScores = false;

            // Check if participant has scores and any game > 0
            if ($participant->score) {
                $hasScores =
                    $participant->score->g1 > 0 ||
                    $participant->score->g2 > 0 ||
                    $participant->score->g3 > 0 ||
                    $participant->score->g4 > 0 ||
                    $participant->score->g5 > 0;
            }

            // Update status based on scores
            if ($hasScores) {
                $participant->status = 'approved';
                $participant->save();
                $approvedCount++;
                $this->command->info("  Participant {$participant->name} (ID: {$participant->id}) - set to APPROVED (has scores)");
            } else {
                $participant->status = 'pending';
                $participant->save();
                $pendingCount++;
                $this->command->info("  Participant {$participant->name} (ID: {$participant->id}) - set to PENDING (no scores)");
            }
        }

        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info("  Approved: {$approvedCount} participants");
        $this->command->info("  Pending: {$pendingCount} participants");
        $this->command->info("  Total: " . ($approvedCount + $pendingCount) . " participants");
        $this->command->info('');
        $this->command->info('Participant status fixing completed!');
    }
}
