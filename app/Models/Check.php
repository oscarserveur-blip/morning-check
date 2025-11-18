<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Check extends Model
{
    protected $fillable = [
        'date_time',
        'client_id',
        'statut',
        'notes',
        'created_by',
        'email_sent_at'
    ];

    protected $casts = [
        'statut' => 'string',
        'date_time' => 'datetime',
        'email_sent_at' => 'datetime'
    ];

    // Définir les valeurs de statut acceptées
    const STATUT_PENDING = 'pending';
    const STATUT_IN_PROGRESS = 'in_progress';
    const STATUT_SUCCESS = 'success';
    const STATUT_WARNING = 'warning';
    const STATUT_ERROR = 'error';

    public static function getStatutValues(): array
    {
        return [
            self::STATUT_PENDING,
            self::STATUT_IN_PROGRESS,
            self::STATUT_SUCCESS,
            self::STATUT_WARNING,
            self::STATUT_ERROR
        ];
    }

    /**
     * Get the client that owns the check.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user that created the check.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the service checks for the check.
     */
    public function serviceChecks(): HasMany
    {
        return $this->hasMany(ServiceCheck::class);
    }
}
