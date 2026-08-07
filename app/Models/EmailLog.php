<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'to_email',
        'subject',
        'mailable_class',
        'related_type',
        'related_id',
        'body_html',
        'status',
        'error',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function related()
    {
        return $this->morphTo();
    }
}
