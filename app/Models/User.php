<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasName;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     protected $fillable = [
        'name', 'password', 'role', 'email', 'email_verified_at', 'client_id', 'must_change_password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function checks()
    {
        return $this->hasMany(Check::class, 'created_by');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'created_by');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_user');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    public function isGestionnaire()
    {
        return $this->role === 'gestionnaire';
    }

    /**
     * Vérifie si l'utilisateur peut accéder à un client donné
     */
    public function canAccessClient($clientId)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isGestionnaire()) {
            return $this->clients->contains($clientId);
        }

        return false;
    }

    /**
     * Récupère les clients accessibles à l'utilisateur
     */
    public function getAccessibleClients()
    {
        if ($this->isAdmin()) {
            return Client::all();
        }

        if ($this->isGestionnaire()) {
            return $this->clients;
        }

        return collect();
    }
}
