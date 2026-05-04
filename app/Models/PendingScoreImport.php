<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingScoreImport extends Model
{
    protected $fillable = [
        'batch_id',
        'nickname',
        'g1',
        'g2',
        'g3',
        'g4',
        'g5',
        'total',
        'reason',
        'match_candidates',
        'status',
        'resolved_participant_id',
        'resolved_at',
        'row_number',
    ];

    protected function casts(): array
    {
        return [
            'g1' => 'integer',
            'g2' => 'integer',
            'g3' => 'integer',
            'g4' => 'integer',
            'g5' => 'integer',
            'total' => 'integer',
            'match_candidates' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PendingScoreImport $record) {
            $record->total = $record->g1 + $record->g2 + $record->g3 + $record->g4 + $record->g5;
        });
    }

    public function resolvedParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'resolved_participant_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
