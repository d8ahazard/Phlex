<?php  namespace Filebase\Format;


class Json implements FormatInterface
{

    /**
    * encode
    *
    */
    public static function encode($data = [], $pretty = true)
    {
        $p = 1;
        if ($pretty==true) $p = JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES;

        return json_encode($data, $p);
    }


    /**
    * decode
    *
    */
    public static function decode($data)
    {
        return json_decode($data, 1);
    }

}
