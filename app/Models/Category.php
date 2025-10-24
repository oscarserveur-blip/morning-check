<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['title', 'client_id', 'category_pk', 'status', 'created_by'];

    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'category_pk');
    }
}
