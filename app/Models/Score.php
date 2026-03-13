<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'participant_id',
        'g1',
        'g2',
        'g3',
        'g4',
        'g5',
        'total',
        'average',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'g1' => 'integer',
            'g2' => 'integer',
            'g3' => 'integer',
            'g4' => 'integer',
            'g5' => 'integer',
            'total' => 'integer',
            'average' => 'decimal:1',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Score $score) {
            // Calculate total from all games
            $score->total = $score->g1 + $score->g2 + $score->g3 + $score->g4 + $score->g5;

            // Calculate average (total divided by 5 games)
            $score->average = $score->total > 0 ? round($score->total / 5, 1) : 0;
        });
    }

    /**
     * Get the participant that owns the score.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
