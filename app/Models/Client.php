<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Client extends Model
{
    protected $fillable = [
        'email', 'password', 'firstname', 'lastname', 'phonenumber',
        'address1', 'city', 'state', 'postcode', 'country', 'credit_balance',
        'whmcs_client_id',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'credit_balance' => 'decimal:2',
    ];

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
