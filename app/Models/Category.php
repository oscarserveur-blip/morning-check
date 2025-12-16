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

    public function children()
    {
        return $this->hasMany(Category::class, 'category_pk');
    }

    /**
     * Get the full category path (parent > child)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->title];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->title);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Get the full category path as array
     */
    public function getFullPathArray(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current->title);
            $current = $current->parent;
        }
        
        return $path;
    }
}
