<?php namespace Filebase;

use Exception;

class QueryTest extends \PHPUnit\Framework\TestCase
{

    /**
    * testDatabaseQuery()
    *
    * TEST:
    *
    */
    public function testDatabaseQuery()
    {
        $db = new Database([
            'path' => __DIR__.'/database'
        ]);


        // get filebase version
        $db->version();
        // get db config
        $db->config();
        // get a single table in the database
        $db->table($name);
        // list all the tables in the database
        $db->getTables();
        // list all the tables in the database
        $db->list();
        // delete entire database (all its tables and documents)
        $db->delete();
        // back up entire database
        // $db->backup();



        // empty entire table
        $db->table('products')->empty();
        // completely delete the table
        // $db->table('products')->delete();
        // get a count of items in db
        $db->table('products')->count();
        // get all items in the database table
        $db->table('products')->getAll();
        // get all items in the database table (with documents)
        $db->table('products')->list();

        // query the table to find documents
        $db->table('products')->where('category','shoes')->get();
        $db->table('products')->whereIn('category','shoes')->get();



        // Get a single document within a table
        $product = $db->table('products')->get('2006-key-fob');
        // save a single document
        $product->save();
        // delete a single document
        $product->delete();
        // replace a single document (from collection...)
        $product->replace($data);
        // re-name document
        $product->rename($name);


        $db->table()->select()->where();

        $db->select();
        $db->where();
        $db->orWhere();

        $db->orderBy();
        $db->groupBy();
        $db->limit();

        $db->query()
            ->where('category','shoes',function($q){
                $q->where('price','>',100);
            })->orWhere('category','shirts',function($q){
                $q->where('price','>',100);
            });

    }


}
