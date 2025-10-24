<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailing extends Model
{
    protected $fillable = ['client_id', 'email', 'type', 'created_by'];

    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }
}
