<?php
/**
 * Utilities class
 * Class to provide utilities
 *
 * @author Zecle Technology Co., Ltd.
 */

class Utilities
{
    /**
     * @param string $input
     * @param int $init
     * @param int $polynomial
     * @param bool $hex
     * @return int|string
     */
    public static function CRC16($input, $init = 0xFFFF, $polynomial = 0x1021, $hex = false)
    {
        $result = $init;
        if (($length = strlen($input)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $result ^= (ord($input[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($result <<= 1) & 0x10000) $result ^= $polynomial;
                    $result &= 0xFFFF;
                }
            }
        }
        if ($hex) {
            return dechex($result);
        }
        return $result;
    }
}
