<?php namespace Filebase;

use Exception;
use Filebase\Database;
use Base\Support\Filesystem;

class DocumentTest extends \PHPUnit\Framework\TestCase
{


    /**
    * testDocumentSave()
    *
    * TEST:
    * (1) Test that we can SAVE the document
    * (2) Test that we can edit/change document properties
    * (3) Test that we can getName() of document
    * (4) Test that we can use the Collection->get()
    * (5) Test that we can run a DEFAULT value on GET request
    *
    */
    public function testDocumentSave()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');
        // use the collection->set()
        $doc->set('name','Timothy Marois');
        // define the property directly
        $doc->topic = 'php';
        // save the file
        $doc->save();

        $this->assertEquals('timothymarois', $doc->getName());
        $this->assertInternalType('string', $doc->getPath());

        $this->assertEquals('Timothy Marois', $doc->name);
        $this->assertEquals('Timothy Marois', $doc->get('name'));

        $this->assertEquals('php', $doc->topic);
        $this->assertEquals('php', $doc->get('topic'));

        $this->assertEquals('mydefault', $doc->get('checkvalue','mydefault'));
    }


    /**
    * testDocumentGetWithCollection()
    *
    * TEST:
    * (1) Test that we can get the saved document (previous test)
    * (2) Test that we can use the Collection->get()
    *
    */
    public function testDocumentGetWithCollection()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');

        $this->assertEquals('Timothy Marois', $doc->name);
        $this->assertEquals('Timothy Marois', $doc->get('name'));

        $this->assertEquals('php', $doc->topic);
        $this->assertEquals('php', $doc->get('topic'));
    }


    /**
    * testDocumentGetNoCollection()
    *
    * TEST:
    * (1) Test that we get document (without collection object)
    *
    */
    public function testDocumentGetNoCollection()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');

        $this->assertInternalType('array', $doc->toArray());

        $this->assertEquals('Timothy Marois', $doc->name);
    }


    /**
    * testDocumentGetNoCollectionError()
    *
    * TEST:
    * (1) Test if we get an ERROR when trying to access collection methods
    *
    */
    public function testDocumentGetNoCollectionError()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois',false);

        // get is a collection method...
        // this should NOT work.
        $name = $doc->get('name');
    }


    /**
    * testDocumentGetNoCollecttestDocumentBadMethodionError()
    *
    * TEST:
    * (1) Test bad method on document
    *
    */
    public function testDocumentBadMethod()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois',false);

        // this should NOT work...
        $badMethod = $doc->methodDoesNotExist();
    }


    /**
    * testDocumentRename()
    *
    * TEST:
    * (1) Test the creation of a document and its data
    * (2) Test we can RENAME the same doc and the data exists
    *
    */
    public function testDocumentRename()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');

        $this->assertEquals('timothymarois', $doc->getName());
        $this->assertInternalType('string', $doc->getPath());
        $this->assertEquals('Timothy Marois', $doc->name);

        $doc->rename('janedoe');

        $this->assertEquals('janedoe', $doc->getName());
        $this->assertInternalType('string', $doc->getPath());
        $this->assertEquals('Timothy Marois', $doc->name);

        $ndoc = $db->table('users')->get('janedoe');
        $this->assertEquals('Timothy Marois', $ndoc->name);
    }


    /**
    * testDocumentRenameReadonlyWithError()
    *
    * TEST:
    * (1) Test renaming item on readonly with errors
    *
    */
    public function testDocumentRenameReadonlyWithError()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => true
         ]);

        $doc = $db->table('users')->get('timothymarois');

        $doc->rename('janedoe');
    }


    /**
    * testDocumentRenameReadonlyNoError()
    *
    * TEST:
    * (1) Test renaming item on readonly with errors
    *
    */
    public function testDocumentRenameReadonlyNoError()
    {

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => false
         ]);

        $doc = $db->table('users')->get('timothymarois');

        $doc->rename('janedoe');

        $this->assertEquals('timothymarois', $doc->getName());
    }


    /**
    * testDocumentGetDotNotation()
    *
    * TEST:
    * (1) Test that we can grab "DOT" notation from GET (collection)
    * (2) Test we can get the multi-array without collection
    *
    */
    public function testDocumentGetDotNotation()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');
        $doc->us = ['nc'=>'charlotte'];
        $doc->save();

        $place = $doc->get('us.nc');

        // check that "DOT" notation works (collection)
        $this->assertEquals('charlotte', $place);


        // check without collection
        $doc = $db->table('users')->get('timothymarois',false);
        $place = $doc->us['nc'];

        $this->assertEquals('charlotte', $place);
    }


    /**
    * testDocumentDelete()
    *
    * TEST:
    * (1) Test that we can DELETE document
    * (2) Test that deleted document clears current object data
    *
    */
    public function testDocumentDelete()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('timothymarois');
        // use the collection->set()
        $doc->set('name','Timothy Marois');
        // define the property directly
        $doc->topic = 'php';
        // save the file
        $doc->save();

        $ndoc = $db->table('users')->get('timothymarois');
        $this->assertEquals('Timothy Marois', $doc->name);

        $ndoc->delete();

        $xdoc = $db->table('users')->get('timothymarois');

        $this->assertEquals([], $xdoc->all());

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testDocumentSaveReadOnly()
    *
    * TEST:
    * (1) Test SAVE on document of database READ ONLY
    *
    */
    public function testDocumentSaveReadOnly()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => true
        ]);

        $doc = $db->table('users')->get('timothymarois');
        $doc->author = 'Timothy Marois';
        $doc->save();
    }


    /**
    * testDocumentDeleteReadOnlyWithErrors()
    *
    * TEST:
    * (1) Test ERROR on DELETE document with READ ONLY MODE
    *
    */
    public function testDocumentDeleteReadOnlyWithErrors()
    {
        $this->expectException(Exception::class);

        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => true
        ]);

        $doc = $db->table('users')->get('timothymarois');
        $doc->delete();
    }


    /**
    * testDocumentDeleteReadOnlyNoErrors()
    *
    * TEST:
    * (1) Test NO ERROR on DELETE document with READ ONLY MODE
    *
    */
    public function testDocumentDeleteReadOnlyNoErrors()
    {
        $db = new Database([
            'path' => __DIR__.'/database',
            'readOnly' => true,
            'errors' => false
        ]);

        $doc = $db->table('users')->get('timothymarois');
        $doc->about = 'Something Cool';
        $doc->delete();

        $this->assertEquals('Something Cool', $doc->about);
    }


    /**
    * testDocumentBadName()
    *
    * TEST:
    * (1) Test BAD document name gets fixed
    *
    */
    public function testDocumentBadName()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('bad name');
        $doc->save();
        $doc->delete();

        $this->assertEquals('bad name', $doc->getName());
        $this->assertRegExp('/badname.db$/', $doc->getPath());

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testDocumentOutputAsJSON()
    *
    * TEST:
    * (1) Test the document can be returend as JSON when outputing
    * (2) Test the document can be returend as JSON using toJson()
    * (3) Test the document can be returend as JSON using toJson() without collection
    *
    */
    public function testDocumentOutputAsJSON()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('product1');
        $doc->productId = 123;
        $doc->productName = 'Apple Watch';
        $doc->save();

        // check that we can output the whole doc as a string JSON
        $this->assertEquals('{"productId":123,"productName":"Apple Watch"}', $doc);
        $this->assertEquals('{"productId":123,"productName":"Apple Watch"}', $doc->toJson());

        $doc = $db->table('users')->get('product1',false);

        $this->assertEquals('{"productId":123,"productName":"Apple Watch"}', $doc->toJson());
        $doc->delete();

        Filesystem::deleteDirectory(__DIR__.'/database');
    }


    /**
    * testDocumentOutputAsArray()
    *
    * TEST:
    * (1) Test that docuement can be returned as an ARRAY (toArray())
    * (2) Test that docuement can be returned as an ARRAY (all()) collection method
    * (3) Test that docuement can be returned as an ARRAY (toArray()) without collection
    *
    */
    public function testDocumentOutputAsArray()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);

        $doc = $db->table('users')->get('product1');
        $doc->productId = 123;
        $doc->productName = 'Apple Watch';
        $doc->save();

        $this->assertEquals(['productId'=>123,'productName'=>'Apple Watch'], $doc->toArray());
        $this->assertEquals(['productId'=>123,'productName'=>'Apple Watch'], $doc->all());

        $doc = $db->table('users')->get('product1',false);

        $this->assertEquals(['productId'=>123,'productName'=>'Apple Watch'], $doc->toArray());
        $doc->delete();

        Filesystem::deleteDirectory(__DIR__.'/database');
    }



    /**
    * testTableName()
    *
    * TEST:
    * (1) Get the table name from document
    *
    */
    public function testTableName()
    {
        $makeDir = __DIR__.'/database';

        Filesystem::deleteDirectory($makeDir);

        $db = new Database([
            'path' => $makeDir
        ]);

        $doc = $db->table('products')->get('iphone');

        $this->assertEquals('products', $doc->table()->getName());

        Filesystem::deleteDirectory($makeDir);
    }



    /**
    * testRemoveItem()
    *
    * TEST:
    * (1) Removing item from document using "unset"
    * (2) Extract document again and check removed item does not exist
    * (3) Using the collection to remove the item
    * (4) Checking isset on undefined property
    * (5) Checking isset on defined property
    *
    */
    public function testRemoveItem()
    {
        $makeDir = __DIR__.'/database';

        Filesystem::deleteDirectory($makeDir);

        $db = new Database([
            'path' => $makeDir
        ]);


        // TEST (1)

        $doc = $db->table('users')->get('testproduct');
        $doc->productId = 123;
        $doc->productName = 'Apple Watch';
        $doc->save();

        unset($doc->productName);
        $this->assertEquals(null, $doc->productName);

        // TEST (2)

        $doc->save();
        $doc = $db->table('users')->get('testproduct');
        $this->assertEquals(null, $doc->productName);

        // TEST (3)

        $doc->remove('productId');
        $doc->save();
        $doc = $db->table('users')->get('testproduct');
        $this->assertEquals(null, $doc->productId);


        // TEST (4)

        $this->assertEquals(false, isset($doc->productId));

        // TEST (5)

        $doc->newkey = '123';
        $doc->save();
        $doc = $db->table('users')->get('testproduct');
        $this->assertEquals('123', $doc->newkey);
        $this->assertEquals(true, isset($doc->newkey));

        Filesystem::deleteDirectory($makeDir);
    }




}
