<?php

namespace App\Models;

use App\Concerns\HasObfuscatedCode;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasObfuscatedCode;

    protected $fillable = [
        'client_id', 'department_id', 'subject', 'message', 'priority', 'status', 'last_reply_at',
        'related_service_type', 'related_service_id',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    private const TICKET_CODE_MULTIPLIER = 21686129;
    private const TICKET_CODE_MULTIPLIER_INVERSE = 43005329;

    /**
     * Short, non-sequential reference clients quote to support, instead of exposing
     * the raw auto-increment id (which would reveal total ticket volume). Uses its
     * own salt (separate from Client::accountCode) so the two can't be correlated.
     */
    public function ticketCode(): string
    {
        $salt = (int) config('services.ticket_codes.salt');

        return 'TKT-' . self::obfuscatedCodeEncode($this->id, $salt, self::TICKET_CODE_MULTIPLIER);
    }

    public static function findByTicketCode(string $code): ?self
    {
        $code = strtoupper(trim($code));
        $code = str_starts_with($code, 'TKT-') ? substr($code, 4) : $code;

        $salt = (int) config('services.ticket_codes.salt');
        $id = self::obfuscatedCodeDecode($code, $salt, self::TICKET_CODE_MULTIPLIER_INVERSE);

        return $id === null ? null : static::find($id);
    }

    public function department()
    {
        return $this->belongsTo(SupportDepartment::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
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
