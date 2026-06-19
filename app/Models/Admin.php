<?php

namespace App\Models;

use App\Enums\AdminRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $fillable = [
        'email', 'password', 'role',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    ];

    protected $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'role' => AdminRole::class,
            // Encrypted at rest — these are TOTP secrets/backup codes, not just app data.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Supports two formats: a bcrypt hash (issued by the in-app reset flow) or a
     * legacy 32-char MD5 hash, so the password can still be set by hand via
     * phpMyAdmin's built-in MD5() function when editing the row directly.
     */
    public function checkPassword(string $plain): bool
    {
        if (preg_match('/^[a-f0-9]{32}$/i', $this->password)) {
            return hash_equals(strtolower($this->password), md5($plain));
        }

        return Hash::check($plain, $this->password);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }
}
