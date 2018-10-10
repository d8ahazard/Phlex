<?php  namespace Base;

use Base\Support\Arr;

class ArrTest extends \PHPUnit\Framework\TestCase
{

    public function testArrayCheck()
    {
        $this->assertSame(Arr::accessible([1,2,3]), true);
        $this->assertSame(Arr::accessible(), false);
        $this->assertSame(Arr::accessible('test'), false);

        $this->assertSame(Arr::isAssoc([1,2,3]), false);
        $this->assertSame(Arr::isAssoc([1=>[1,2,3],2=>[1,2,3],3=>[1,2,3]]), true);

        $this->assertSame(Arr::get(['test1','test2']), ['test1','test2']);
    }


    public function testArrayExists()
    {
        $array = [
            'key1' => 123,
            'key2' => 456,
            'key3' => 789
        ];

        $this->assertNotEquals($array, Arr::shuffle($array));

        $this->assertSame(Arr::exists($array,'key1'), true);
        $this->assertSame(Arr::exists($array,'23424'), false);

        $this->assertSame(Arr::first($array), 123);
        $this->assertSame(Arr::last($array), 789);

        $this->assertSame(Arr::first([],'default'), 'default');
        $this->assertSame(Arr::last([], 'default'), 'default');

        $this->assertSame(Arr::first(null,'default'), 'default');
        $this->assertSame(Arr::last(null, 'default'), 'default');


        $this->assertSame(Arr::has($array, 'key1'), true);
        $this->assertSame(Arr::has($array, 'sfsfdf'), false);

        // check if target is an array
        $this->assertSame(Arr::has('fdgdfg', 'sfsfdf'), false);
        // check if keys are emptyu
        $this->assertSame(Arr::has($array, []), false);

        // check that we have wrapped an array around these items
        $this->assertInternalType('array',Arr::wrap($array));
        $this->assertInternalType('array',Arr::wrap('333'));


        $this->assertSame(['one'=>'two','three'=>'four'], Arr::add(['one'=>'two'], 'three', 'four'));

        $array['config'] = [
            'test' => 505,
            'test2' => 505
        ];

        $this->assertSame(Arr::has($array, 'config.test'), true);
        $this->assertSame(Arr::has($array, ['config.test','config.test2']), true);
        $this->assertSame(Arr::has($array, null), false);

        $this->assertSame(Arr::only($array, ['config','key1']), [
            'key1' => 123,
            'config' => [
                'test' => 505,
                'test2' => 505
            ]
        ]);


        $addArray = [];
        Arr::set($addArray, 'keyOne','ValueOne');

        $this->assertEquals($addArray, [
            'keyOne' => 'ValueOne'
        ]);
    }


    public function testArrayActs()
    {
        $array = [
            'key1' => 123,
            'key2' => 456,
            'key3' => 789
        ];

        $arrayCheck = [
            'key0' => '000',
            'key1' => 123,
            'key2' => 456,
            'key3' => 789
        ];

        // add an element to the beginning of an array
        $this->assertSame(Arr::prepend($array,'000','key0'), $arrayCheck);

        // pull an element from the array and remove it
        $this->assertSame(Arr::pull($arrayCheck,'key0'), '000');
        // no item in the array, so lets use a default
        $this->assertSame(Arr::pull($arrayCheck,'key777','defaulted'), 'defaulted');
        // check that both arrays are equal now...
        $this->assertSame($array, $arrayCheck);

        // get all items in teh array except for some values
        $this->assertSame(Arr::except($array,['key1','key2']), ['key3'=>789]);
    }


    public function testArrayDots()
    {
        $array = [
            'config' => [
                'db' => [
                    'driver' => 'mysql',
                    'port' => 3306
                ],
                'views' => [
                    'error' => '404',
                    'debug' => true
                ]
            ],
            'routes' => [
                'action' => 'controller',
                'method' => 'index'
            ]
        ];


        $flatten = [
            'mysql',
            3306,
            '404',
            true,
            'controller',
            'index'
        ];

        // flatten the array
        $this->assertSame(Arr::flatten($array), $flatten);
        // get a variable by dot notation
        $this->assertSame(Arr::get($array,'config.db.driver'), 'mysql');
        // get a variable by dot notation (using default)
        $this->assertSame(Arr::get($array,'config.db.host','localhost'), 'localhost');

    }




    public function testArrayDensity()
    {
        $array = [
            'computer',
            'office',
            'chair',
            'ofFice',
            'OFFICE',
            'chair'
        ];

        $density = Arr::density($array);

        $this->assertSame($density, ['office'=>3,'chair'=>2,'computer'=>1]);

        $density = Arr::density([
            'computer',
            'office',
            'office',
            'chair',
            'office',
            'OFFICE',
            'chair'
        ], true);

        $this->assertSame(['office'=>3,'chair'=>2,'computer'=>1,'OFFICE'=>1], $density);

    }

}
