<?php namespace Filebase\Format;

interface FormatInterface
{
    public static function encode($data, $pretty);
    public static function decode($data);
}
