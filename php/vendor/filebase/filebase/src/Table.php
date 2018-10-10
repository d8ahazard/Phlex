<?php  namespace Filebase;

use Exception;
use Filebase\Database;
use Filebase\Document;
use Filebase\Query\Builder;
use Base\Support\Filesystem;

class Table
{

    /**
    * $database
    *
    * @var Filebase\Database
    */
    protected $db;


    /**
    * The table name
    * (directory name)
    *
    * @var string
    */
    protected $name;


    /**
    * The table directory path
    *
    * @var string
    */
    protected $path;


    /**
    * __construct
    *
    * @param Filebase\Database $database
    * @param string $name
    */
    public function __construct(Database $database, $name = '')
    {
        $this->db = $database;

        $this->name = $name;

        $this->path = $this->db()->config()->path.'/'.$this->db()->safeName($this->name);

        // create table directory if does not exist.
        $this->db()->directory($this->path);
    }


    /**
    * getName
    *
    * @return string $name
    */
    public function getName()
    {
        return $this->name;
    }


    /**
    * getPath
    *
    * @return string $path
    */
    public function getPath()
    {
        return $this->path;
    }


    /**
    * Gets the document within the table
    *
    * @param string $name
    * @return Filebase\Document
    */
    public function get($name, $isCollection = true)
    {
        return (new Document($this, $name, $isCollection));
    }


    /**
    * getAll()
    *
    * Get all database documents and load them as documents
    *
    * @param bool $isCollection
    * @return array
    */
    public function getAll($isCollection = true)
    {
        return array_map(function($file) use ($isCollection){
            return $this->get(str_replace('.'.$this->db()->config()->ext,'',$file), $isCollection);
        }, $this->list());
    }


    /**
    * list()
    *
    * Get all the files within the table (directory)
    *
    * @param bool $realPath
    * @return array
    */
    public function list($realPath = false)
    {
        try
        {
            return Filesystem::files($this->path, $this->db()->config()->ext, $realPath);
        }
        catch(Exception $e)
        {
            return [];
        }
    }


    /**
    * count()
    *
    * Counts all the table items (files in directory)
    *
    * @return int
    */
    public function count()
    {
        return count($this->list());
    }


    /**
    * db()
    *
    *
    * @return Filebase\Database
    */
    public function db()
    {
        return $this->db;
    }


    /**
    * empty
    *
    * Empties (deletes all files within table)
    *
    * @return void
    */
    public function empty()
    {
        if ($this->db()->isReadOnly() === false)
        {
            // It "may" delete files that are not part of the database.
            // this could lead to overdeletion, possibly other non-database files.
            return Filesystem::empty($this->path);
        }
    }


    /**
    * Call a method on query builder
    *
    * @param string $name
    * @param array $arguments
    * @return Filebase\Query\Builder
    */
    public function __call($name, $arguments)
    {
        return (new Builder($this))->$name(...$arguments);
    }


}
