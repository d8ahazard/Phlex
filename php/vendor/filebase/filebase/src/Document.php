<?php  namespace Filebase;

use Exception;
use Filebase\Database;
use Base\Support\Arr;
use Base\Support\Collection;
use Base\Support\Filesystem;

class Document
{

    /**
    * $table
    *
    * @var Filebase\Table
    */
    protected $table;


    /**
    * $name
    *
    * @var string
    */
    protected $name;


    /**
    * $path
    *
    * @var string
    */
    protected $path;


    /**
    * $isCollection
    *
    * @var bool
    */
    protected $isCollection = true;


    /**
    * $collection
    *
    * @var Base\Support\Collection
    */
    protected $collection;


    /**
    * __construct
    *
    * Sets the database property
    *
    * @param Filebase\Table $table
    * @param string $name
    * @param bool $isCollection
    */
    public function __construct(Table $table, $name = '', $isCollection = true)
    {
        $this->table = $table;

        $this->name = $name;

        $this->isCollection = $isCollection;

        $this->path = $this->table()->getPath().'/'.$this->db()->safeName($name).'.'.$this->db()->config()->ext;

        $this->collection = $this->load($this->path);
    }


    /**
    * getTable
    *
    * @return Filebase\Table
    */
    public function table()
    {
        return $this->table;
    }


    /**
    * getDatabase
    *
    * @return Filebase\Table
    */
    public function db()
    {
        return $this->table->db();
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
    * Load
    *
    * @param string $name
    * @return Base\Support\Collection
    */
    protected function load($path)
    {
        $contents = Filesystem::get($path) ?? '';

        $format = $this->db()->config()->format;

        $data = (array) $format::decode( $contents ) ?? [];

        if ($this->isCollection === true) return (new Collection($data));

        return $data;
    }


    /**
    * save
    *
    * @return Base\Support\Filesystem
    */
    public function save()
    {
        if ($this->db()->isReadOnly() === false)
        {
            $format = $this->db()->config()->format;

            $data = $format::encode($this->toArray(), $this->db()->config()->prettyFormat);

            return Filesystem::put($this->path, $data);
        }
    }


    /**
    * rename
    *
    * @return void
    */
    public function rename($name)
    {
        if ($this->db()->isReadOnly() === false)
        {
            $this->name = $name;

            $currentPath = $this->path;

            $this->path = Filesystem::dirname($this->path).'/'.$this->db()->safeName($name).'.'.$this->db()->config()->ext;

            return Filesystem::rename($currentPath, $this->db()->safeName($name).'.'.$this->db()->config()->ext, false);
        }
    }


    /**
    * delete
    *
    * @return void
    */
    public function delete()
    {
        if ($this->db()->isReadOnly() === false)
        {
            $this->collection = ($this->isCollection) ? new Collection([]) : [];

            return Filesystem::delete($this->path);
        }
    }


    /**
    * Call a method on the collection
    *
    * @param string $name
    * @param array $arguments
    * @return Base\Support\Collection mixed
    */
    public function __call($name, $arguments)
    {
        if ($this->isCollection) return $this->collection->$name(...$arguments);

        throw new Exception('Filebase: Method does not exist. Document returned as array not Collection.');
    }


    /**
    * get property from the collection
    *
    * @param string $key
    * @param mixed $default
    * @return Base\Support\Collection get
    */
    public function __get($key)
    {
        if (!$this->isCollection) return Arr::get($this->collection, $key);

        return $this->collection->get($key);
    }


    /**
    * set a new property into the collection
    *
    * @param string $key
    * @param mixed $default
    * @return Base\Support\Collection set
    */
    public function __set($key, $value)
    {
        if (!$this->isCollection) return Arr::set($this->collection, $key, $value);

        return $this->collection->set($key, $value);
    }


    /**
    * __isset
    *
    */
    public function __isset($key)
    {
        if (!$this->isCollection) return Arr::has($this->collection, $key);

        return $this->collection->has($key);
    }


    /**
    * __unset
    *
    */
    public function __unset($key)
    {
        if (!$this->isCollection) return Arr::remove($this->collection, $key);

        return $this->collection->remove($key);
    }


    /**
     * Get the Document as ARRAY.
     *
     * @param  int  $options
     * @return string
     */
    public function toArray()
    {
        return ($this->isCollection) ? $this->collection->toArray() : $this->collection;
    }


    /**
     * Get the Document as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson()
    {
        return ($this->isCollection) ? $this->collection->toJson() : json_encode($this->collection,1);
    }


    /**
    * If the document is being output as a string
    *
    * @return string JSON formatted
    */
    public function __toString()
    {
        return $this->toJson();
    }

}
