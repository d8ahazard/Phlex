<?php  namespace Base;

use Base\Support\Num;


class NumTest extends \PHPUnit\Framework\TestCase
{

    public function testAddition()
    {
        $this->assertEquals(Num::add(5,10), 15);

        $this->assertEquals(Num::total([10,5]), 15);
    }


    public function testSubtraction()
    {
        $result1 = Num::subtract(6,5);
        $result2 = Num::subtract(5,6);

        $this->assertEquals($result1, 1);
        $this->assertEquals($result2, -1);
    }


    public function testMultiplication()
    {
        $result1 = Num::multiply(1,5);
        $result2 = Num::multiply(0,5);
        $result3 = Num::multiply(5,5);
        $result4 = Num::multiply(5.5,2);

        $this->assertEquals($result1, 5);
        $this->assertEquals($result2, 0);
        $this->assertEquals($result3, 25);
        $this->assertEquals($result4, 11);
    }


    public function testDivision()
    {
        $result1 = Num::divide(10,2);
        $result2 = Num::divide(10,0);
        $result3 = Num::divide(0,10);

        $this->assertEquals($result1, 5);
        $this->assertEquals($result2, 0);
        $this->assertEquals($result3, 0);
    }


    public function testRound()
    {
        $result1 = Num::round(10.100);
        $result2 = Num::round(10.100,2);
        $result3 = Num::round(10.100,1);

        $this->assertEquals($result1, 10);
        $this->assertEquals($result2, 10.10);
        $this->assertEquals($result3, 10.1);
    }


    public function testPercent()
    {
        $result1 = Num::percent(50,100);
        $result2 = Num::percent(100,25);
        $result3 = Num::percent(0,100);
        $result4 = Num::percent(100,100);
        $result5 = Num::percent(50,100,false);

        $this->assertEquals($result1, 50);
        $this->assertEquals($result2, 25);
        $this->assertEquals($result3, 0);
        $this->assertEquals($result4, 100);
        $this->assertEquals($result5, 0.5);
    }


    public function testAverage()
    {
        $result1 = Num::avg([1,2,2,2,2,2,5,6,5,1,2,2],2);
        $result2 = Num::avg([1,2,2,2,2,2,5,6,5,1,2,2]);
        $result3 = Num::average([1,2,2,2,2,2,5,6,5,1,2,2]);

        $this->assertEquals($result1, 2.67);
        $this->assertEquals($result2, 3);
        $this->assertEquals($result3, 3);
    }


    public function testMax()
    {
        $result1 = Num::max([1,2,2,2,2,2,5,6,5,1,2,2]);

        $this->assertEquals($result1, 6);
    }


    public function testMin()
    {
        $result1 = Num::min([1,2,2,2,2,2,5,6,5,1,2,2]);

        $this->assertEquals($result1, 1);
    }


    public function testCPM()
    {
        $result1 = Num::cpm(13544,43);
        $result2 = Num::cpm(13544,43,2);

        $this->assertSame($result1, 3.0);
        $this->assertSame($result2, 3.17);
    }


    public function testCurrency()
    {
        $result1 = Num::currency(500);
        $result2 = Num::currency(5000);
        $result3 = Num::currency(5000,0);

        $this->assertEquals($result1, 500.00);
        $this->assertEquals($result2, '5,000.00');
        $this->assertEquals($result3, '5,000');
    }

}
