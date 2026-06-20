<?php

namespace App\Concerns;

/**
 * Turns a sequential id into a short, non-sequential, reversible code (and back)
 * without an extra DB column — e.g. Client::accountCode() / SupportTicket::ticketCode().
 * Multiplying by a coprime "multiplier" scrambles the id (a *small* multiplier would
 * just add a small, near-constant step per id, leaving consecutive codes visibly
 * adjacent — the multiplier must be on the same order of magnitude as the modulus so
 * consecutive ids wrap around it many times). Adding a secret salt means the
 * multiplier alone isn't enough to reverse a code without knowing it.
 */
trait HasObfuscatedCode
{
    // 36^5 — the modulus a 5-character base36 code can hold exactly, with no wasted
    // or overflowing range.
    private const OBFUSCATED_CODE_MODULUS = 60466176;

    private static function obfuscatedCodeEncode(int $id, int $salt, int $multiplier): string
    {
        $salt = $salt % self::OBFUSCATED_CODE_MODULUS;
        $scrambled = ($id * $multiplier) % self::OBFUSCATED_CODE_MODULUS;
        $obfuscated = ($scrambled + $salt) % self::OBFUSCATED_CODE_MODULUS;

        return strtoupper(str_pad(base_convert((string) $obfuscated, 10, 36), 5, '0', STR_PAD_LEFT));
    }

    /**
     * @param int $multiplierInverse the modular inverse of the multiplier used to
     *            encode, i.e. satisfying (multiplier * multiplierInverse) % MODULUS === 1
     */
    private static function obfuscatedCodeDecode(string $code, int $salt, int $multiplierInverse): ?int
    {
        if ($code === '' || ! ctype_alnum($code)) {
            return null;
        }

        $salt = $salt % self::OBFUSCATED_CODE_MODULUS;
        $obfuscated = (int) base_convert($code, 36, 10) % self::OBFUSCATED_CODE_MODULUS;
        $scrambled = (($obfuscated - $salt) % self::OBFUSCATED_CODE_MODULUS + self::OBFUSCATED_CODE_MODULUS) % self::OBFUSCATED_CODE_MODULUS;

        return ($scrambled * $multiplierInverse) % self::OBFUSCATED_CODE_MODULUS;
    }
}
