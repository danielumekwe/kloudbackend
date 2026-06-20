<?php

namespace App\Models;

use App\Concerns\HasObfuscatedCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Client extends Model
{
    use HasObfuscatedCode;

    protected $fillable = [
        'email', 'password', 'firstname', 'lastname', 'phonenumber',
        'address1', 'city', 'state', 'postcode', 'country', 'credit_balance',
        'whmcs_client_id', 'suspended_at', 'email_verified_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'credit_balance' => 'decimal:2',
        'suspended_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    private const ACCOUNT_CODE_MULTIPLIER = 37156667;
    private const ACCOUNT_CODE_MULTIPLIER_INVERSE = 28239347;

    /**
     * Short, non-sequential reference customers can quote to support, instead of
     * exposing the raw auto-increment id (which would reveal total customer count
     * and signup order). Reversible via modular arithmetic, so this needs no extra
     * DB column — see findByAccountCode().
     */
    public function accountCode(): string
    {
        $salt = (int) config('services.account_codes.salt');

        return 'KLD-' . self::obfuscatedCodeEncode($this->id, $salt, self::ACCOUNT_CODE_MULTIPLIER);
    }

    public static function findByAccountCode(string $code): ?self
    {
        $code = strtoupper(trim($code));
        $code = str_starts_with($code, 'KLD-') ? substr($code, 4) : $code;

        $salt = (int) config('services.account_codes.salt');
        $id = self::obfuscatedCodeDecode($code, $salt, self::ACCOUNT_CODE_MULTIPLIER_INVERSE);

        return $id === null ? null : static::find($id);
    }

    /**
     * Supports a bcrypt hash (issued locally going forward) or a legacy 32-char
     * MD5 hash (imported as-is from WHMCS's tblclients.password during the
     * one-time migration — see App\Console\Commands\MigrateWhmcsClients).
     * Successful MD5 logins are transparently upgraded to bcrypt.
     */
    public function checkPassword(string $plain): bool
    {
        if (preg_match('/^[a-f0-9]{32}$/i', $this->password)) {
            if (! hash_equals(strtolower($this->password), md5($plain))) {
                return false;
            }

            $this->update(['password' => Hash::make($plain)]);

            return true;
        }

        // Hash::check() throws (rather than returning false) for a hash it doesn't
        // recognize as bcrypt — a real risk here specifically, since passwords are
        // imported byte-for-byte from an external system whose hash format for any
        // given account isn't actually guaranteed (see MigrateWhmcsClients). Fail
        // closed instead of a 500.
        try {
            return Hash::check($plain, $this->password);
        } catch (\RuntimeException) {
            return false;
        }
    }
}
