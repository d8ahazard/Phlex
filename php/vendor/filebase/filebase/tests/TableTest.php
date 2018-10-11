<?php namespace Filebase;

use Exception;
use Filebase\Database;
use Base\Support\Filesystem;

class TableTest extends \PHPUnit\Framework\TestCase
{

    /**
    * testDatabaseCountZeroNoErrors()
    *
    * TEST:
    * (1) Test database-table COUNT() zero
    *     BUT hide errors from throwing when does not exist
    *
    */
    public function testDatabaseCountZeroNoErrors()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => false
        ]);

        $this->assertEquals(0, $db->table('products')->count());
    }


    /**
    * testDatabaseCountZeroWithErrors()
    *
    * TEST:
    * (1) Test database-table COUNT() zero
    *     BUT throw error because it does not exist.
    *
    */
    public function testDatabaseCountZeroWithErrors()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => true
        ]);

        $db->table('products')->count();
    }


    /**
    * testCreateTableDirectory()
    *
    * TEST:
    * (1) Create table directory and check it exists
    *
    */
    public function testCreateTableDirectory()
    {
        $makeDir = __DIR__.'/database';

        Filesystem::deleteDirectory($makeDir);

        $db = new Database([
            'path' => $makeDir
        ]);

        $db->table('products');

        $this->assertEquals(true, Filesystem::isDirectory($makeDir.'/products'));

        Filesystem::deleteDirectory($makeDir);
    }


    /**
    * testGetName()
    *
    * TEST:
    * (1) Get the table name
    *
    */
    public function testGetName()
    {
        $makeDir = __DIR__.'/database';

        Filesystem::deleteDirectory($makeDir);

        $db = new Database([
            'path' => $makeDir
        ]);

        $table = $db->table('products');

        $this->assertEquals('products', $table->getName());

        Filesystem::deleteDirectory($makeDir);
    }



    /**
    * testCreateBadTableName()
    *
    * TEST:
    * (1) Create table using a bad name
    *
    */
    public function testCreateBadTableName()
    {
        $makeDir = __DIR__.'/database';

        Filesystem::deleteDirectory($makeDir);

        $db = new Database([
            'path' => $makeDir
        ]);

        $db->table('products pages');

        $this->assertEquals(true, Filesystem::isDirectory($makeDir.'/productspages'));

        Filesystem::deleteDirectory($makeDir);
    }


    /**
    * testCreateCountAndEmpty()
    *
    * TEST:
    * (1) Test creation of many documents
    * (2) Test table COUNT() is working
    * (3) EMPTY the entire table
    *
    */
    public function testCreateCountAndEmpty()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $dbTable = $db->table('products');

        for ($x = 1; $x <= 10; $x++)
        {
            $user = $dbTable->get(uniqid());
            $user->name = 'John';
            $user->save();
        }

        $before = $dbTable->count();

        $dbTable->empty();

        $this->assertEquals(10, $before);
        $this->assertEquals(0, $dbTable->count());

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testCreateCountAndEmpty()
    *
    * TEST:
    * (1) Test creation of many documents
    * (2) Test table COUNT() is working
    * (3) EMPTY the entire table
    *
    */
    public function testEmptyReadyOnlyNoErrors()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $dbTable = $db->table('products');

        for ($x = 1; $x <= 10; $x++)
        {
            $user = $dbTable->get(uniqid());
            $user->name = 'John';
            $user->save();
        }

        $before = $dbTable->count();


        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => false
        ]);

        $dbTable = $db->table('products');

        $dbTable->empty();

        $this->assertEquals(10, $before);
        $this->assertEquals(10, $dbTable->count());

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testDatabaseGetAll()
    *
    * TEST:
    * (1) Get all files within the database
    * (2) Get all database collection items
    *
    */
    public function testTableGetAll()
    {
        Filesystem::deleteDirectory(__DIR__.'/database');

        $db = new Database([
            'path' => __DIR__.'/database',
            'ext' => 'db'
        ]);

        $dbTable = $db->table('users');

        for ($x = 1; $x <= 10; $x++)
        {
            $user = $dbTable->get(uniqid());
            $user->name = 'John';
            $user->save();
        }

        $getAll   = $dbTable->list();
        $getReal  = $dbTable->list(true);
        $allFiles = $dbTable->getAll();

        $dbTable->empty();

        $this->assertEquals(10, count($allFiles));
        $this->assertEquals(10, count($getReal));
        $this->assertEquals(10, count($getAll));

        $loadOneItem = current($allFiles);
        $this->assertEquals('John', ($loadOneItem->name));
        $this->assertEquals('John', ($loadOneItem->get('name')));

        $loadSingleItem = current($getAll);
        $this->assertRegExp('/[a-zA-Z0-9]+\.db/', $loadSingleItem);

        Filesystem::deleteDirectory(__DIR__.'/database');
    }

}
