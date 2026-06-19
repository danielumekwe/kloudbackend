<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'client_id', 'department_id', 'subject', 'message', 'priority', 'status', 'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(SupportDepartment::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id')->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === 'Closed';
    }
}
