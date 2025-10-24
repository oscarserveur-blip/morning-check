<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCheck extends Model
{
    protected $fillable = [
        'check_id', 
        'service_id', 
        'statut', 
        'observations',
        'notes',
        'intervenant'
    ];

    protected $casts = [
        'statut' => 'string',
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

    public function check()
    {
        return $this->belongsTo(Check::class, 'check_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function intervenant()
    {
        return $this->belongsTo(User::class, 'intervenant');
    }

    public function intervenantUser()
    {
        return $this->belongsTo(User::class, 'intervenant');
    }
}