<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'type', 'key', 'name', 'tagline', 'description', 'is_hidden', 'is_retired',
    ];

    protected $casts = [
        'is_hidden'  => 'boolean',
        'is_retired' => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
