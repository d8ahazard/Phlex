<?php

namespace Base\Support;

class Num
{

    /**
     * Add 2 numbers together
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function add($a, $b)
    {
        return ($a + $b);
    }


    /**
     * Subtract 2 numbers from eachother
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function subtract($a, $b)
    {
        return ($a - $b);
    }


    /**
     * Divide 2 numbers
     * (using a save divide since php likes to throw errors)
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function divide($a, $b)
    {
        if ($a == 0 || $b == 0) return 0;

        return ($a / $b);
    }


    /**
     * Multiply 2 numbers
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function multiply($a, $b)
    {
        return ($a * $b);
    }


    /**
     * Get the percentage of 2 numbers
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public static function percent($a, $b, $percent = true)
    {
        $total = $a;
        $num = $b;

        if ($b < $a) {
            $total = $b;
            $num = $a;
        }

        $number = static::divide($total, $num);

        // return the number as a float (0.00)
        if ($percent == false) return $number;

        // return the number as a true percent 0%
        return static::multiply($number, 100);
    }


    /**
     * get average from an array of numbers
     *
     * @param array $array
     * @return int
     */
    public static function avg(array $array, $decmial = 0)
    {
        // filter array (to remove empty items)
        $array = array_filter($array);

        return static::round(static::divide(array_sum($array), count($array)),$decmial);
    }


    /**
     * get average from an array of numbers
     *
     * @param array $array
     * @return int
     */
    public static function average(array $array, $decmial = 0)
    {
        return static::avg($array, $decmial);
    }


    /**
     * Find the highest number (max) within an array
     *
     * @param array $array
     * @return int
     */
    public static function max(array $array)
    {
        return max($array);
    }


    /**
     * Find the lowest number (min) within an array
     *
     * @param array $array
     * @return int
     */
    public static function min(array $array)
    {
        return min($array);
    }


    /**
     * Total up all the numbers in array
     *
     * @param array $array
     * @return int
     */
    public static function total(array $array)
    {
        return array_sum($array);
    }


    /**
     * Round a number
     *
     * @param int $num
     * @param int $decmial
     * @return int
     */
    public static function round($num, $decmial = 0)
    {
        return round($num, $decmial);
    }


    /**
     * Currency format
     *
     * @param int $num
     * @param int $decmial
     * @return int
     */
    public static function currency($number = 0, $decmial = 2)
    {
        return number_format($number, $decmial, '.', ',');
    }


    /**
     * Get the CPM of the impressions served
     *
     * @param int $impressions
     * @param int $revenue
     * @return int
     */
    public static function cpm($impressions = 0, $revenue = 0, $decmial = 0)
    {
        return static::round(static::divide($revenue, static::divide($impressions,1000)),$decmial);
    }

}
