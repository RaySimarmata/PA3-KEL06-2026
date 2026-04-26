<?php

use App\Helpers\StringHelper;

if (!function_exists('safe_strlen')) {
    /**
     * Safe strlen - memastikan parameter adalah string sebelum menghitung panjang
     * 
     * @param mixed $value
     * @return int
     */
    function safe_strlen($value): int
    {
        return StringHelper::safeStrlen($value);
    }
}

if (!function_exists('ensure_string')) {
    /**
     * Ensure string - konversi apapun ke string
     * 
     * @param mixed $value
     * @return string
     */
    function ensure_string($value): string
    {
        return StringHelper::ensureString($value);
    }
}
