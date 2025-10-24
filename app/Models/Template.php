<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Template extends Model
{
    protected $fillable = [
        'name',
        'description', 
        'type',
        'header_logo',
        'header_title',
        'header_color',
        'section_config',
        'footer_text',
        'footer_color',
        'config'
    ];

    protected $casts = [
        'config' => 'array',
        'section_config' => 'array',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_template');
    }
}
