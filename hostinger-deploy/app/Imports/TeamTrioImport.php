<?php

namespace App\Imports;

class TeamTrioImport extends ParticipantImport
{
    /**
     * Get the captain IC from a row.
     */
    protected function getCaptainIc(array $row): ?string
    {
        return $this->formatIc($row['ketua_kp'] ?? null);
    }

    /**
     * Get the captain name from a row.
     */
    protected function getCaptainName(array $row): ?string
    {
        return $row['ketua_nama'] ?? null;
    }

    /**
     * Get the captain phone from a row.
     */
    protected function getCaptainPhone(array $row): ?string
    {
        return $row['ketua_telefon'] ?? null;
    }

    /**
     * Get the event type for this import.
     */
    protected function getEventType(): string
    {
        return 'trio';
    }

    /**
     * Get the team members for a row.
     */
    protected function getTeamMembers(array $row): array
    {
        $members = [];

        if (! empty($row['member_2_nama'])) {
            $members[2] = [
                'name' => $row['member_2_nama'],
                'ic' => $row['member_2_kp'] ?? '',
            ];
        }

        if (! empty($row['member_3_nama'])) {
            $members[3] = [
                'name' => $row['member_3_nama'],
                'ic' => $row['member_3_kp'] ?? '',
            ];
        }

        return $members;
    }

    /**
     * Get the validation rules for team trio import.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Team trio import requires ketua_kp, ketua_nama, and ketua_telefon
        // Accept string or numeric for ketua_kp to handle Excel's scientific notation
        $rules['ketua_kp'] = 'required|string|numeric';
        $rules['ketua_nama'] = 'required|string|max:255';
        $rules['ketua_telefon'] = 'required|string|max:20';

        return $rules;
    }

    /**
     * Get the custom validation messages.
     */
    public function customValidationMessages(): array
    {
        $messages = parent::customValidationMessages();

        $messages['ketua_kp.required'] = 'No. KP ketua diperlukan';
        $messages['ketua_nama.required'] = 'Nama ketua diperlukan';
        $messages['ketua_telefon.required'] = 'No. telefon ketua diperlukan';

        return $messages;
    }
}
