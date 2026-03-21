<?php

namespace App\Imports;

use App\Models\Participant;
use App\Models\Score;
use App\Models\TeamMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

abstract class ParticipantImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected array $errors = [];

    protected array $created = [];

    protected array $updated = [];

    protected int $rowNumber = 2; // Excel row number (1-based, header is row 1)

    /**
     * Convert scientific notation to original number format.
     */
    protected function convertScientificNotation(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Handle scientific notation (e.g., 9.501E+11 -> 950101015555)
        if (preg_match('/^(\d+)\.(\d+)E\+(\d+)$/', $value, $matches)) {
            $base = $matches[1].$matches[2];
            $exponent = (int) $matches[3];

            return str_pad($base, $exponent + 1, '0');
        }

        return $value;
    }

    /**
     * Get the validation rules that apply to the import.
     */
    public function rules(): array
    {
        return [
            'ketua_kp' => 'nullable|string',
            'nama_penuh' => 'nullable|string|max:255',
            'ketua_nama' => 'nullable|string|max:255',
            'no_telefon' => 'nullable|string|max:20',
            'ketua_telefon' => 'nullable|string|max:20',
            'nama_pasukan' => 'required|string|max:255',
            'jantina' => 'required|in:lelaki,wanita',
            'g1' => 'nullable|integer|min:0|max:300',
            'g2' => 'nullable|integer|min:0|max:300',
            'g3' => 'nullable|integer|min:0|max:300',
            'g4' => 'nullable|integer|min:0|max:300',
            'g5' => 'nullable|integer|min:0|max:300',
            // Team member fields - these are handled by subclasses
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function customValidationMessages(): array
    {
        return [
            'nama_pasukan.required' => 'Nama pasukan diperlukan',
            'jantina.required' => 'Jantina diperlukan',
            'jantina.in' => 'Jantina mestilah lelaki atau wanita',
            'g1.integer' => 'Game 1 mestilah nombor bulat',
            'g1.min' => 'Game 1 mestilah sekurang-kurangnya 0',
            'g1.max' => 'Game 1 mestilah tidak melebihi 300',
            'g2.integer' => 'Game 2 mestilah nombor bulat',
            'g2.min' => 'Game 2 mestilah sekurang-kurangnya 0',
            'g2.max' => 'Game 2 mestilah tidak melebihi 300',
            'g3.integer' => 'Game 3 mestilah nombor bulat',
            'g3.min' => 'Game 3 mestilah sekurang-kurangnya 0',
            'g3.max' => 'Game 3 mestilah tidak melebihi 300',
            'g4.integer' => 'Game 4 mestilah nombor bulat',
            'g4.min' => 'Game 4 mestilah sekurang-kurangnya 0',
            'g4.max' => 'Game 4 mestilah tidak melebihi 300',
            'g5.integer' => 'Game 5 mestilah nombor bulat',
            'g5.min' => 'Game 5 mestilah sekurang-kurangnya 0',
            'g5.max' => 'Game 5 mestilah tidak melebihi 300',
        ];
    }

    /**
     * Process the collection.
     */
    public function collection(Collection $collection)
    {
        // Pre-process rows to handle scientific notation
        $allRows = $collection->map(function ($row) {
            foreach ($row as $key => $value) {
                if (is_string($value) && str_starts_with($key, 'kp') && str_ends_with($key, '_kp')) {
                    // Handle scientific notation for IC fields
                    $row[$key] = $this->convertScientificNotation($value);
                }
            }

            return $row;
        })->toArray();

        // First pass: Collect all captain ICs and check for duplicates
        $captainIcs = [];

        foreach ($allRows as $row) {
            $captainIc = $this->getCaptainIc($row);

            if (empty($captainIc)) {
                continue;
            }

            // Check for duplicate IC within the Excel file
            if (isset($captainIcs[$captainIc])) {
                $this->errors[] = "Baris {$this->rowNumber}: No. KP '$captainIc' didapati berulang dalam fail (juga pada baris {$captainIcs[$captainIc]})";
            } else {
                $captainIcs[$captainIc] = $this->rowNumber;
            }

            $this->rowNumber++;
        }

        // If there are duplicate ICs within the file, return early
        if (! empty($this->errors)) {
            return;
        }

        // Third pass: Import data with error handling per row
        $this->rowNumber = 2;

        foreach ($allRows as $row) {
            try {
                $this->importRow($row);
                $this->rowNumber++;
            } catch (\Exception $e) {
                Log::error('Excel import row error', [
                    'row' => $this->rowNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->errors[] = "Baris {$this->rowNumber}: {$e->getMessage()}";
                $this->rowNumber++;
            }
        }
    }

    /**
     * Get the captain IC from a row.
     */
    abstract protected function getCaptainIc(array $row): ?string;

    /**
     * Get the captain IC from a row and handle scientific notation.
     */
    protected function formatIc(?string $ic): ?string
    {
        if (empty($ic)) {
            return null;
        }

        // Handle scientific notation (e.g., 9.501E+11 -> 950101015555)
        if (preg_match('/^(\d+)\.(\d+)E\+(\d+)$/', $ic, $matches)) {
            $base = $matches[1].$matches[2];
            $exponent = (int) $matches[3];

            return str_pad($base, $exponent + 1, '0');
        }

        return $ic;
    }

    /**
     * Prepare row data for validation - fix scientific notation.
     */
    public function prepareForValidation(array $row, int $rowIndex): array
    {
        // Convert all IC fields from scientific notation to proper format
        foreach ($row as $key => $value) {
            if (is_string($value) && str_ends_with($key, '_kp')) {
                $row[$key] = $this->formatIc($value);
            }
        }

        return $row;
    }

    /**
     * Get the captain name from a row.
     */
    abstract protected function getCaptainName(array $row): ?string;

    /**
     * Get the captain phone from a row.
     */
    abstract protected function getCaptainPhone(array $row): ?string;

    /**
     * Get the event type for this import.
     */
    abstract protected function getEventType(): string;

    /**
     * Get the team members for a row.
     */
    abstract protected function getTeamMembers(array $row): array;

    /**
     * Import a single row with result tracking.
     */
    protected function importRow(array $row)
    {
        DB::transaction(function () use ($row) {
            $captainIc = $this->getCaptainIc($row);
            $captainName = $this->getCaptainName($row);
            $captainPhone = $this->getCaptainPhone($row);
            $team = $row['nama_pasukan'];
            $gender = $row['jantina'];

            // For individual events, the captain info is the participant info
            $ic = empty($captainIc) ? \Str::uuid()->toString() : $captainIc;
            $name = $captainName ?? $team; // For individual, use name; for team, use team name as captain name
            $phone = $captainPhone ?? '';

            // Find or create participant
            $participant = Participant::where('ic', $ic)->first();
            $wasExisting = $participant !== null;

            if (! $participant) {
                $participant = new Participant;
                $participant->id = \Str::uuid();
                $participant->ic = $ic;
            } else {
                // Prevent switching from team event to individual (must delete manually first)
                $currentEventType = $participant->event_type;
                $newEventType = $this->getEventType();
                $teamEventTypes = ['beregu', 'trio', 'berkumpulan'];

                if (in_array($currentEventType, $teamEventTypes) && $newEventType === 'individu') {
                    $participantTeamCount = $participant->teamMembers()->count();

                    if ($participantTeamCount > 0) {
                        throw new \Exception(
                            "Peserta dengan IC '{$ic}' sudah berdaftar dalam acara '{$currentEventType}' dengan {$participantTeamCount} ahli pasukan. ".
                            'Sila padam rekod peserta tersebut dahulu sebelum mengimport sebagai individu.'
                        );
                    }
                }

                // Warn if changing between team events (allowed but warn admin)
                if ($currentEventType !== $newEventType && in_array($currentEventType, $teamEventTypes) && in_array($newEventType, $teamEventTypes)) {
                    $this->errors[] = "Amaran: Peserta dengan IC '{$ic}' bertukar acara dari '{$currentEventType}' ke '{$newEventType}'";
                }
            }

            $participant->name = $name;
            $participant->phone = $phone;
            $participant->team = $team;
            $participant->gender = $gender;
            $participant->event_type = $this->getEventType();
            $participant->status = 'approved'; // Auto-approve all imported participants
            $participant->save();

            // Track result
            if ($wasExisting) {
                $this->updated[] = $ic;
            } else {
                $this->created[] = $ic;
            }

            // Create or update score
            $score = Score::where('participant_id', $participant->id)->first();

            if (! $score) {
                $score = new Score;
                $score->participant_id = $participant->id;
            }

            $score->g1 = isset($row['g1']) ? (int) $row['g1'] : 0;
            $score->g2 = isset($row['g2']) ? (int) $row['g2'] : 0;
            $score->g3 = isset($row['g3']) ? (int) $row['g3'] : 0;
            $score->g4 = isset($row['g4']) ? (int) $row['g4'] : 0;
            $score->g5 = isset($row['g5']) ? (int) $row['g5'] : 0;
            $score->save();

            // Create team members if applicable
            $teamMembers = $this->getTeamMembers($row);

            foreach ($teamMembers as $order => $memberData) {
                $teamMember = TeamMember::where('participant_id', $participant->id)
                    ->where('member_order', $order)
                    ->first();

                if (! $teamMember) {
                    $teamMember = new TeamMember;
                    $teamMember->participant_id = $participant->id;
                    $teamMember->member_order = $order;
                }

                $teamMember->name = $memberData['name'];
                $teamMember->ic = $memberData['ic'] ?? '';
                $teamMember->save();
            }
        });
    }

    /**
     * Get the validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get the import results.
     */
    public function getResults(): array
    {
        return [
            'created' => count($this->created),
            'updated' => count($this->updated),
            'created_ics' => $this->created,
            'updated_ics' => $this->updated,
        ];
    }

    /**
     * Get the imported data.
     */
    public function getImportedData(): ?array
    {
        return null;
    }
}
