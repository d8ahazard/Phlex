<?php  namespace Base;

use Base\Support\Arr;
use Base\Support\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{

    public function testBasic()
    {
        $collect = new Collection([1,2,3,4,5,6]);

        // get the keys in collection
        $this->assertEquals([0,1,2,3,4,5], $collect->keys()->all());

        // check that we get all items
        $this->assertEquals($collect->all(), [1,2,3,4,5,6]);

        // check that we get all items
        $this->assertNotEquals($collect->shuffle(), [1,2,3,4,5,6]);

        // check that we count all items
        $this->assertEquals($collect->count(), 6);

        // remove the first item from the array
        $this->assertEquals(true, $collect->shift());
        // the first item was removed...
        $this->assertEquals([2,3,4,5,6], $collect->all());

    }


    public function testActions()
    {
        $collect = new Collection([
            'sites' =>
                [
                    'timothymarois' => 'tmarois.com',
                    'johndoe' => 'example.com',
                    'another' => 'website.com'
                ]
        ]);

        // grab an item from array with dot notation
        $this->assertEquals($collect->get('sites.timothymarois'), 'tmarois.com');

        // get multiple...
        $this->assertEquals($collect->get(['sites.timothymarois','sites.johndoe']),[
            'sites.timothymarois' => 'tmarois.com',
            'sites.johndoe' => 'example.com'
        ]);

        // grab many items from the array, using dot notation
        $this->assertEquals($collect->getMany(['sites.timothymarois','sites.another']), ['sites.timothymarois' => 'tmarois.com', 'sites.another' => 'website.com']);

        // set a new item on the array
        $collect->set('author','timothymarois');
        $this->assertEquals($collect->get('author',false), 'timothymarois');

        // replace existing item on the array
        $collect->set('author','jamesdean');
        $this->assertEquals($collect->get('author',false), 'jamesdean');


        $collect = new Collection([
            'timothymarois' => 'tmarois.com',
            'johndoe' => 'example.com',
            'another' => 'website.com'
        ]);

        $this->assertEquals($collect->first(), 'tmarois.com' );
        $this->assertEquals($collect->last(), 'website.com' );

        // reverse, and return a new collection
        $newCollection = $collect->reverse();

        $this->assertEquals($newCollection->first(), 'website.com' );
        $this->assertEquals($newCollection->last(), 'tmarois.com' );

        // remove an item in the collection
        $collect->remove('timothymarois');
        $this->assertEquals($collect->first(), 'example.com' );
        $this->assertEquals($collect->count(), 2 );

        // take only 1 item from the array
        $this->assertEquals($collect->take(1)->all(), ['johndoe' => 'example.com']);

        // implode all items on a string
        $this->assertEquals($collect->implode(',','.'), 'example.com,website.com');
    }


    public function testOutput()
    {
        $collect = new Collection(['timothymarois' => 'tmarois.com']);

        // Return all items as an array
        $this->assertEquals($collect->toArray(), ['timothymarois' => 'tmarois.com']);
        // Return all items as an array
        $this->assertEquals($collect->all(), ['timothymarois' => 'tmarois.com']);

        // force it to be returned as a string
        $this->assertEquals((string) $collect, '{"timothymarois":"tmarois.com"}');
        // return it as JSON format
        $this->assertEquals($collect->toJson(), '{"timothymarois":"tmarois.com"}');
    }


    public function testDefined()
    {
        $collect = new Collection([
            'sites' =>
                [
                    'timothymarois' => 'tmarois.com',
                    'johndoe' => 'example.com',
                    'another' => 'website.com'
                ]
        ]);

        $this->assertEquals($collect->has('sites'),true);
        $this->assertEquals($collect->has('sites.timothymarois'),true);
        $this->assertEquals($collect->has('doesnotexist'),false);
    }



    public function testWhere()
    {
        $collect = new Collection([
            'productA' => ['price'=>10,'keyword'=>'car'],
            'productB' => ['price'=>30,'keyword'=>'truck'],
            'productC' => ['price'=>2,'keyword'=>'car'],
            'productD' => ['price'=>15,'keyword'=>'atv']
        ]);

        $items1 = $collect->where('keyword','car');
        $items2 = $collect->where('price',2);
        $items3 = $collect->where('price','>',20);
        $items4 = $collect->whereIn('keyword',['truck','atv']);

        $this->assertEquals(2,$items1->count());
        $this->assertEquals(['price'=>10,'keyword'=>'car'],$items1->first());
        $this->assertEquals(['price'=>2,'keyword'=>'car'],$items2->first());
        $this->assertEquals(['price'=>30,'keyword'=>'truck'],$items3->first());
        $this->assertEquals(2,$items4->count());
    }


    public function testSortable()
    {
        $collect = new Collection([
            'productA' => ['price'=>10],
            'productB' => ['price'=>30],
            'productC' => ['price'=>2]
        ]);

        $this->assertEquals(['price'=>10],$collect->first());
        $this->assertEquals(['price'=>2],$collect->last());

        $productSorted = $collect->sortBy('price','ASC');

        $this->assertEquals(['price'=>2],$productSorted->first());
        $this->assertEquals(['price'=>30],$productSorted->last());

        $productSorted = $collect->sortBy('price','DESC');

        $this->assertEquals(['price'=>30],$productSorted->first());
        $this->assertEquals(['price'=>2],$productSorted->last());

    }


    public function testRandom()
    {
        $collect = new Collection([
            ['name'=>'Watch','price'=>249],
            ['name'=>'Display','price'=>129],
            ['name'=>'Phone','price'=>749],
        ]);

        $this->assertEquals(['name'=>'Watch','price'=>249],$collect->first());
        $this->assertEquals(['name'=>'Phone','price'=>749],$collect->last());

        $randomItems = $collect->random(2);
        $this->assertEquals(2,$randomItems->count());
    }


    public function testExcept()
    {
        $collect = new Collection([
            'id' => 1,
            'name' => 'Apple Watch',
            'key' => 'kjsdhfuioeiwjfkld'
        ]);

        $items = $collect->except(['id','key']);

        $this->assertEquals(['name' => 'Apple Watch'],$items->all());

        $items = $collect->except('key');

        $this->assertEquals(['id' => 1, 'name' => 'Apple Watch'],$items->all());
    }


    public function testOnly()
    {
        $collect = new Collection([
            'id' => 1,
            'name' => 'Apple Watch',
            'key' => 'kjsdhfuioeiwjfkld'
        ]);

        $items = $collect->only(['id','key']);

        $this->assertEquals(['id' => 1,'key' => 'kjsdhfuioeiwjfkld'],$items->all());

        $items = $collect->only('id');

        $this->assertEquals(['id' => 1],$items->all());
    }


    public function testGroupBy()
    {
        $collect = new Collection([
            ['category' => 'furniture', 'product' => 'Chair'],
            ['category' => 'reading', 'product' => 'Bookcase'],
            ['category' => 'furniture', 'product' => 'Desk']
        ]);

        $grabbedItems = $collect->groupBy('category');

        $this->assertEquals(2,$grabbedItems->get('furniture')->count());
        $this->assertEquals(1,$grabbedItems->get('reading')->count());
        $this->assertEquals(['category' => 'reading', 'product' => 'Bookcase'],$grabbedItems->get('reading')->first());
    }


    public function testMergeCollectionArray()
    {
        $collect1 = new Collection([
            ['category' => 'furniture', 'product' => 'Chair'],
        ]);

        $collect2 = new Collection([
            ['category' => 'furniture', 'product' => 'Desk'],
        ]);

        $newCollection = $collect1->merge($collect2->all());

        $this->assertEquals(2,$newCollection->count());
        $this->assertEquals(['category' => 'furniture', 'product' => 'Desk'],$newCollection->last());
    }


    public function testMergeCollectionSelf()
    {
        $collect1 = new Collection([
            ['category' => 'furniture', 'product' => 'Chair'],
        ]);

        $collect2 = new Collection([
            ['category' => 'furniture', 'product' => 'Desk'],
        ]);

        $newCollection = $collect1->merge($collect2);

        $this->assertEquals(2,$newCollection->count());
        $this->assertEquals(['category' => 'furniture', 'product' => 'Desk'],$newCollection->last());
    }



    public function testCombine()
    {
        $collect1 = new Collection([
            'green', 'red', 'yellow'
        ]);

        $collect2 = new Collection([
            'avocado', 'apple', 'banana'
        ]);

        $newCollection = $collect1->combine($collect2);

        $this->assertEquals(3,$newCollection->count());
        $this->assertEquals(['green'=>'avocado','red'=>'apple','yellow'=>'banana'],$newCollection->all());
    }


    public function testReject()
    {
        $collect1 = new Collection([1, 2, 3, 4]);

        $filtered = $collect1->reject(function ($value, $key) {
            return $value > 2;
        });

        $this->assertEquals([1,2],$filtered->all());
    }


    public function testUnion()
    {
        $collect1 = new Collection([1 => ['a'], 2 => ['b']]);

        $newCollection = $collect1->union([3 => ['c'], 1 => ['b']]);

        $this->assertEquals([1 => ['a'], 2 => ['b'], 3 => ['c']],$newCollection->all());
    }


    public function testUnique()
    {
        $collection = new Collection([1, 1, 2, 2, 3, 4, 2]);

        $unique = $collection->unique();

        $this->assertEquals([1, 2, 3, 4],$unique->values()->all());
    }


    public function testEach()
    {
        $collection = new Collection([1, 1, 2, 2, 3, 4, 2]);

        $test = $collection->each(function ($item, $key) {

        });

        $this->assertEquals([1, 1, 2, 2, 3, 4, 2],$test->all());

    }


}
