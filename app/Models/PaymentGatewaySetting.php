<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $fillable = ['gateway', 'key_type', 'value'];

    protected $casts = [
        'value' => 'encrypted',
    ];

    public static function get(string $gateway, string $keyType): ?string
    {
        return static::where('gateway', $gateway)->where('key_type', $keyType)->first()?->value;
    }

    public static function set(string $gateway, string $keyType, ?string $value): void
    {
        static::updateOrCreate(['gateway' => $gateway, 'key_type' => $keyType], ['value' => $value]);
    }
}
