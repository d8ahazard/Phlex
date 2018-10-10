<?php namespace Filebase;

use Exception;
use Filebase\Database;
use Base\Support\Filesystem;

class BadClass {

}

class FormatTest extends \PHPUnit\Framework\TestCase
{

    /**
    * testMissingFormatClass()
    *
    * TEST:
    * Missing format class
    *
    */
    public function testMissingFormatClass()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'format' => ''
        ]);
    }


    /**
    * testBadFormatClass()
    *
    * TEST:
    * Test a BAD format class
    *
    */
    public function testBadFormatClass()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'format' => BadClass::class
        ]);
    }

}
