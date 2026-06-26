<?php

namespace App\Helpers;

/**
 * String Helper
 * 
 * Helper functions untuk operasi string yang aman
 */
class StringHelper
{
    /**
     * Safe strlen - memastikan parameter adalah string sebelum menghitung panjang
     * 
     * @param mixed $value
     * @return int
     */
    public static function safeStrlen($value): int
    {
        if (is_null($value)) {
            return 0;
        }
        
        if (is_array($value)) {
            return strlen(json_encode($value));
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return strlen((string)$value);
            }
            return strlen(json_encode($value));
        }
        
        if (is_bool($value)) {
            return strlen($value ? '1' : '0');
        }
        
        return strlen((string)$value);
    }
    
    /**
     * Ensure string - konversi apapun ke string
     * 
     * @param mixed $value
     * @return string
     */
    public static function ensureString($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        if (is_array($value)) {
            return json_encode($value);
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
            return json_encode($value);
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return (string)$value;
    }
}
