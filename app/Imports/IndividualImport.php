<?php

namespace App\Imports;

class IndividualImport extends ParticipantImport
{
    /**
     * Get the captain IC from a row.
     */
    protected function getCaptainIc(array $row): ?string
    {
        return $this->formatIc($row['no_kp'] ?? null);
    }

    /**
     * Get the captain name from a row.
     */
    protected function getCaptainName(array $row): ?string
    {
        return $row['nama_penuh'] ?? null;
    }

    /**
     * Get the captain phone from a row.
     */
    protected function getCaptainPhone(array $row): ?string
    {
        return $row['no_telefon'] ?? null;
    }

    /**
     * Get the event type for this import.
     */
    protected function getEventType(): string
    {
        return 'individu';
    }

    /**
     * Get the team members for a row.
     */
    protected function getTeamMembers(array $row): array
    {
        // Individual events have no team members
        return [];
    }

    /**
     * Get the validation rules for individual import.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Individual import requires no_kp, nama_penuh, and no_telefon
        // Accept string or numeric for no_kp to handle Excel's scientific notation
        $rules['no_kp'] = 'required|string|numeric';
        $rules['nama_penuh'] = 'required|string|max:255';
        $rules['no_telefon'] = 'required|string|max:20';

        return $rules;
    }

    /**
     * Get the custom validation messages.
     */
    public function customValidationMessages(): array
    {
        $messages = parent::customValidationMessages();

        $messages['no_kp.required'] = 'No. KP diperlukan';
        $messages['nama_penuh.required'] = 'Nama penuh diperlukan';
        $messages['no_telefon.required'] = 'No. telefon diperlukan';

        return $messages;
    }
}
