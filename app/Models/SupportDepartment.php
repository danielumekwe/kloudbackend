<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportDepartment extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'department_id');
    }
}
