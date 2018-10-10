<?php  namespace Base;

use Base\Support\Str;

class StrTest extends \PHPUnit\Framework\TestCase
{

    public function testStringCases()
    {
        $string = 'ThIs is My WebSITE!';

        // change the capitalization of strings
        $this->assertEquals(Str::upper($string), 'THIS IS MY WEBSITE!');
        $this->assertEquals(Str::lower($string), 'this is my website!');
        $this->assertEquals(Str::title($string), 'This Is My Website!');

        // check string length
        $this->assertEquals(Str::length($string), 19);
        $this->assertEquals(Str::length($string,'UTF-8'), 19);

        // test the string limits
        $this->assertEquals(Str::limit($string,5), 'ThIs...');
        $this->assertEquals(Str::limit($string,5,''), 'ThIs');
        $this->assertEquals(Str::limit($string,5,'-'), 'ThIs-');
        $this->assertEquals(Str::limit($string,80), $string);

        // limit words
        $this->assertEquals(Str::words($string,2), 'ThIs is...');
        $this->assertEquals(Str::words('my world is cool',10), 'my world is cool');

        // limit words
        $this->assertEquals(Str::ucfirst('my website'), 'My website');

        $this->assertEquals(Str::substr('my website',0,2), 'my');
    }



    public function testStringUri()
    {
        $string = 'product Name%-4-@&(*)';

        // basic uri check
        $this->assertEquals(Str::uri($string), 'product-name-4');
    }


    public function testStringWordsArray()
    {
        $this->assertEquals(Str::wordArray('word1 word2 word3'),['word1','word2','word3']);

        $this->assertEquals(Str::wordArray('word1, word2, word3'),['word1','word2','word3']);

        $this->assertEquals(Str::wordArray('the, as, world',5,32),['world']);

        $this->assertEquals(Str::wordArray('dfsdfgsd    ggheerre     ghfdghfh'),['dfsdfgsd','ggheerre','ghfdghfh']);

        $this->assertEquals(Str::wordArray('wo-rd1, word%2, word3'),['word1','word2','word3']);

        $this->assertEquals(Str::wordArray('Dorado Office Chair White Office Star'),['Dorado','Office','Chair','White','Office','Star']);

        $this->assertEquals(Str::wordArray('Dorado Office Chair White Office Star',3,32,true),['Dorado','Office','Chair','White','Star']);
    }


    public function testStringClean()
    {
        $string = ' product.   &+hel&lo+ test ';

        $this->assertEquals(Str::clean($string), 'product hello test');

        $this->assertEquals(Str::clean($string, true), 'producthellotest');
    }


    public function testStringIs()
    {
        $string = 'This website is the best, I love it so much.';
        $this->assertEquals(Str::is('This website is the best, I love it so much.',$string), true);

        $string = '/php/framework/lib';
        $this->assertEquals(Str::is('*php*',$string), true);

        $string = 'Music can be loud. Music can be soft.';
        $this->assertEquals(Str::is('*can*',$string), true);
        $this->assertEquals(Str::is('',$string), false);
        $this->assertEquals(Str::is($string), false);
    }


    public function testStringContains()
    {
        $string = 'Music can be loud. Music can be soft.';
        $this->assertEquals(Str::contains($string, ['Music','loud','soft']), true);
        $this->assertEquals(Str::contains($string, 'sdfsdfdsf'), false);
    }

}
