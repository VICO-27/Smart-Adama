<?php

namespace App\Utils;

class PhoneNormalizer
{
    /**
     * Normalizes an Ethiopian phone number.
     * Ensures output format is +2519... or +2517...
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        // Handle Ethiopian numbers
        if (preg_match('/^0(9|7)\d{8}$/', $cleaned)) {
            return '+251'.substr($cleaned, 1);
        }

        if (preg_match('/^251(9|7)\d{8}$/', $cleaned)) {
            return '+'.$cleaned;
        }

        if (preg_match('/^\+251(9|7)\d{8}$/', $cleaned)) {
            return $cleaned;
        }

        // Fallback: return cleaned string if it doesn't match standard ET patterns
        return $cleaned;
    }
}
