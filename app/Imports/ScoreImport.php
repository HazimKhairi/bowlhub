<?php

namespace App\Imports;

use App\Models\Participant;
use App\Models\PendingScoreImport;
use App\Models\Score;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ScoreImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected string $batchId;

    protected array $errors = [];

    protected int $matched = 0;

    protected int $unmatched = 0;

    protected int $invalid = 0;

    protected array $matchedNicknames = [];

    protected array $unmatchedNicknames = [];

    public function __construct()
    {
        $this->batchId = (string) Str::uuid();
    }

    public function rules(): array
    {
        return [
            'nickname' => 'required|string|max:100',
            'g1' => 'nullable|integer|min:0|max:300',
            'g2' => 'nullable|integer|min:0|max:300',
            'g3' => 'nullable|integer|min:0|max:300',
            'g4' => 'nullable|integer|min:0|max:300',
            'g5' => 'nullable|integer|min:0|max:300',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nickname.required' => 'Nickname diperlukan',
            'nickname.max' => 'Nickname terlalu panjang (max 100 aksara)',
            'g1.integer' => 'Game 1 mestilah nombor bulat',
            'g1.max' => 'Game 1 tidak boleh melebihi 300',
            'g2.integer' => 'Game 2 mestilah nombor bulat',
            'g2.max' => 'Game 2 tidak boleh melebihi 300',
            'g3.integer' => 'Game 3 mestilah nombor bulat',
            'g3.max' => 'Game 3 tidak boleh melebihi 300',
            'g4.integer' => 'Game 4 mestilah nombor bulat',
            'g4.max' => 'Game 4 tidak boleh melebihi 300',
            'g5.integer' => 'Game 5 mestilah nombor bulat',
            'g5.max' => 'Game 5 tidak boleh melebihi 300',
        ];
    }

    public function collection(Collection $collection)
    {
        $rowNumber = 2;

        foreach ($collection as $row) {
            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (\Throwable $e) {
                Log::error('Score import row error', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
                $this->errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                $this->invalid++;
            }
            $rowNumber++;
        }
    }

    protected function processRow(array $row, int $rowNumber): void
    {
        $nickname = isset($row['nickname']) ? trim((string) $row['nickname']) : '';

        if ($nickname === '') {
            $this->errors[] = "Baris {$rowNumber}: Nickname kosong, baris diabaikan";
            $this->invalid++;

            return;
        }

        $g1 = (int) ($row['g1'] ?? 0);
        $g2 = (int) ($row['g2'] ?? 0);
        $g3 = (int) ($row['g3'] ?? 0);
        $g4 = (int) ($row['g4'] ?? 0);
        $g5 = (int) ($row['g5'] ?? 0);

        $matches = $this->findMatches($nickname);

        if ($matches->count() === 1) {
            $participant = $matches->first();
            $this->saveScore($participant->id, $g1, $g2, $g3, $g4, $g5);
            $this->matched++;
            $this->matchedNicknames[] = $nickname;

            return;
        }

        $reason = $matches->count() === 0 ? 'no_match' : 'multiple_matches';

        PendingScoreImport::create([
            'batch_id' => $this->batchId,
            'nickname' => $nickname,
            'g1' => $g1,
            'g2' => $g2,
            'g3' => $g3,
            'g4' => $g4,
            'g5' => $g5,
            'reason' => $reason,
            'match_candidates' => $matches->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'nickname' => $p->nickname,
                'team' => $p->team,
                'event_type' => $p->event_type,
            ])->values()->all(),
            'status' => 'pending',
            'row_number' => $rowNumber,
        ]);

        $this->unmatched++;
        $this->unmatchedNicknames[] = $nickname;
    }

    protected function findMatches(string $nickname): Collection
    {
        $normalized = mb_strtolower(trim($nickname));

        return Participant::query()
            ->whereRaw('LOWER(TRIM(nickname)) = ?', [$normalized])
            ->get();
    }

    protected function saveScore(string $participantId, int $g1, int $g2, int $g3, int $g4, int $g5): void
    {
        DB::transaction(function () use ($participantId, $g1, $g2, $g3, $g4, $g5) {
            $score = Score::firstOrNew(['participant_id' => $participantId]);
            $score->g1 = $g1;
            $score->g2 = $g2;
            $score->g3 = $g3;
            $score->g4 = $g4;
            $score->g5 = $g5;
            $score->save();
        });
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getResults(): array
    {
        return [
            'batch_id' => $this->batchId,
            'matched' => $this->matched,
            'unmatched' => $this->unmatched,
            'invalid' => $this->invalid,
            'matched_nicknames' => $this->matchedNicknames,
            'unmatched_nicknames' => $this->unmatchedNicknames,
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }
}
