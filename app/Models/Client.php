<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'label', 'logo', 'template_id', 'check_time'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'client_user');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    
    public function templates()
    {
        return $this->belongsToMany(Template::class, 'client_template');
    }
    
    public function checks()
    {
        return $this->hasMany(Check::class);
    }
    
    public function services()
    {
        return $this->hasManyThrough(Service::class, Category::class);
    }
    
    public function mailings()
    {
        return $this->hasMany(Mailing::class);
    }
    
    public function destinataires()
    {
        return $this->hasMany(RappelDestinataire::class);
    }
    
    public function rappelDestinataires()
    {
        return $this->hasMany(RappelDestinataire::class);
    }
    
    public function loadTemplateAndServices()
    {
        $this->load(['template', 'categories.services']);
    }
}