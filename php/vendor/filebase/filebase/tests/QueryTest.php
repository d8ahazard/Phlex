<?php namespace Filebase;

use Exception;
use Filebase\Database;
use Base\Support\Filesystem;

class QueryTest extends \PHPUnit\Framework\TestCase
{

    /**
    * testWhereQuery()
    *
    *
    */
    public function testWhereQuery()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);


        $tDb = $db->table('products');

        for ($x = 1; $x <= 10; $x++)
    	{
    		$user = $tDb->get(uniqid());

            $user->name = 'John';

            if ($x==4) {
                $user->name = 'Tim';
            }

            $user->number = $x;
            // $user->contact['email'] = 'john@john.com';
    		$user->save();
    	}

        $this->assertEquals(10, $tDb->count());

        $query1 = $tDb->where(['name'=>'John','number'=>2])->get()->first();
        $query1_2 = $tDb->where(['name'=>'John'])->where(['number'=>2])->get()->first();
        $query1_3 = $tDb->where(['name'=>'John'])->where(['number'=>4])->get()->first();
        $query1_4 = $tDb->where(['name'=>'Tim'])->get()->first();

        $this->assertEquals(2, $query1->number);
        $this->assertEquals(2, $query1_2->number);
        $this->assertEquals(false, $query1_3);
        $this->assertEquals(4, $query1_4->number);

        $query2 = $tDb->where('number','>',4)->get()->first();
        $query2_1 = $tDb->where('number','>',5)->get()->last();
        $query2_3 = $tDb->where('number','<',5)->get()->last();

        $this->assertEquals(5, $query2->number);
        $this->assertEquals(10, $query2_1->number);
        $this->assertEquals(4, $query2_3->number);
        $this->assertEquals('Tim', $query2_3->name);


        $query3 = $tDb->where('number','>',4)->get()->count();
        $this->assertEquals(6, $query3);

        $query4 = $tDb->where('number','>=',4)->get()->count();
        $this->assertEquals(7, $query4);

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testWhereIn()
    *
    *
    */
    public function testWhereIn()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $tDb = $db->table('products');

        for ($x = 1; $x <= 10; $x++)
    	{
    		$user = $tDb->get(uniqid());

            $user->name = 'John';
            $user->set('tags', ['js', 'html']);
            $user->email = 'example@ymail.com';

            if ($x==4) {
                $user->set('tags', ['js', 'html', 'php']);
                $user->email = 'tmjr@gmail.com';
            }

            $user->number = $x;
    		$user->save();
    	}

        $this->assertEquals(10, $tDb->count());

        $query1 = $tDb->where('name','==','John')->get()->first();
        $query2 = $tDb->where('tags','IN','php')->get()->first();
        $query3 = $tDb->in('tags','php')->get()->first();
        $query4 = $tDb->not('number',1)->get()->first();
        $query5 = $tDb->like('email','gmail')->get()->first();

        $this->assertEquals(1, $query1->number);
        $this->assertEquals(4, $query2->number);
        $this->assertEquals(4, $query3->number);
        $this->assertEquals(2, $query4->number);
        $this->assertEquals(4, $query5->number);

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testWhereIn()
    *
    *
    */
    public function testOrderBy()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $tDb = $db->table('products');

        for ($x = 1; $x <= 10; $x++)
    	{
    		$user = $tDb->get(uniqid());

            $user->name = 'John';
            $user->number = $x;
    		$user->save();
    	}

        $this->assertEquals(10, $tDb->count());

        $query1 = $tDb->where('name','==','John')->orderBy('number','DESC')->get()->first();
        $this->assertEquals(10, $query1->number);

        $query2 = $tDb->where('name','==','John')->limit(9)->get()->count();
        $this->assertEquals(9, $query2);

        $query3 = $tDb->where('name','==','John')->limit(3)->get()->count();
        $this->assertEquals(3, $query3);


        Filesystem::deleteDirectory(__DIR__.'/database');
    }

}
