<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $fillable = ['email', 'password'];

    protected $hidden = ['password'];

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
}
