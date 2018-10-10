<?php

namespace Base\Support;

class Str
{


    /**
     * Convert the given string to upper-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }


    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }


    /**
     * Convert the given string to title case.
     *
     * @param  string  $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }


    /**
     * Make a string's first character uppercase.
     *
     * @param  string  $string
     * @return string
     */
    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)).static::substr($string, 1);
    }


    /**
     * Return the length of the given string.
     *
     * @param  string  $value
     * @param  string  $encoding
     * @return int
     */
    public static function length($value, $encoding = null)
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }

        return mb_strlen($value);
    }


    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle)
        {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false)
            {
                return true;
            }
        }

        return false;
    }


    /**
     * Limit the number of characters in a string.
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit)
        {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }


    /**
     * Limit the number of words in a string.
     *
     * @param  string  $value
     * @param  int     $words
     * @param  string  $end
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]).$end;
    }


    /**
     * Get all the words from a string (and return the array)
     *
     * @param  string  $string
     * @param  int     $min
     * @param  int     $max
     * @param  bool    $unique
     * @return array
     */
    public static function wordArray($string, $min = 3, $max = 32, $unique = false)
    {
        $rwords = [];
        $words  = preg_split('/\s+/', static::clean($string, false));

        foreach($words as $i=>$word)
        {
            if (static::length($word) >= $min && static::length($word) <= $max)
            {
                if ($unique===true && in_array($word, $rwords))
                {
                    continue;
                }

                array_push($rwords, $word);
            }
        }

        return $rwords;
    }


    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param  string  $string
     * @param  int  $start
     * @param  int|null  $length
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }


    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string|array  $pattern
     * @param  string  $value
     * @return bool
     */
    public static function is($pattern = [], $value = '')
    {
        $patterns = is_array($pattern) ? $pattern : (array) $pattern;

        if (empty($patterns))
        {
            return false;
        }

        foreach ($patterns as $pattern)
        {
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern == $value)
            {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1)
            {
                return true;
            }
        }

        return false;
    }


    /**
     * Generate a alpha-numeric string
     *
     * @param  string  $string
     * @param  string  $separator
     * @return string
     */
    public static function clean($string, $strict = false)
    {
        if ($strict == true)
        {
            $result = preg_replace("/[^a-zA-Z0-9]+/", '', $string);
            $result = preg_replace('/\s+/', '', $result);
        }
        else
        {
            $result = preg_replace("/[^a-zA-Z0-9\s]+/", '', $string);
            $result = preg_replace('/\s+/', ' ', $result);
        }

        // remove whitespace from the start and end of string
        return trim($result);
    }


    /**
     * Generate a URI formatted string
     *
     * @param  string  $uri
     * @param  string  $separator
     * @return string
     */
    public static function uri($uri, $separator = '-')
    {
        // Convert all dashes/underscores into a separator
        $flip = $separator == '-' ? '_' : '-';

        $uri = preg_replace('!['.preg_quote($flip).']+!u', $separator, $uri);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $uri = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($uri));

        // Replace all separator characters and whitespace by a single separator
        $uri = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $uri);

        // remove from the start and end of string
        return trim($uri, $separator);
    }



}
