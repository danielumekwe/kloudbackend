<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = ['ticket_id', 'client_id', 'admin_id', 'message'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function isStaffReply(): bool
    {
        return $this->admin_id !== null;
    }
}
