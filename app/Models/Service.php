<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['title', 'category_id', 'status', 'created_by'];

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checkServices()
    {
        return $this->hasMany(ServiceCheck::class);
    }
}
