<?php  namespace Filebase;

use Exception;
use Filebase\Config;
use Filebase\Table;
use Base\Support\Filesystem;

class Database
{

    /**
    * VERSION
    *
    * Stores the version of Filebase
    * use $db->version()
    *
    * @return string
    */
    const VERSION = '2.0-beta';


    /**
    * $config
    *
    * Stores all the configuration object settings
    *
    * @see Filebase\Config
    */
    protected $config;


    /**
    * __construct
    *
    * @see Filebase\Config
    */
    public function __construct(array $config = [])
    {
        // set up our configuration class
        $this->config = (new Config($config));

        // create database directory if does not exist.
        $this->directory($this->config()->path);
    }


    /**
    * version
    *
    * gets the Filebase version
    *
    * @return VERSION
    */
    public function version()
    {
        return self::VERSION;
    }


    /**
    * config
    *
    * @return Filebase\Config
    */
    public function config()
    {
        return $this->config;
    }


    /**
    * table
    *
    * @param string $name
    * @return Filebase\Table
    */
    public function table($name)
    {
        return (new Table($this, $name));
    }


    /**
    * backup
    *
    * @param string $location (optional)
    * @return Filebase\Backup
    */
    /*public function backup($path = null)
    {
        $path = ($path) ?? $this->config->backupPath;

        return (new Backup($this, $path));
    }*/


    /**
    * getTables()
    *
    * Get all the tables in the database (either by class or name)
    *
    * @param bool $isTableClass
    * @return array
    */
    public function getTables()
    {
        return array_map(function($folder) {
            return $this->table($folder);
        }, $this->list());
    }


    /**
    * list
    *
    * @param bool $realPath
    * @return array
    */
    public function list($realPath = false)
    {
        try
        {
            return array_values(Filesystem::folders($this->config()->path, $realPath));
        }
        catch(Exception $e)
        {
            return [];
        }
    }


    /**
    * allowErrors
    *
    * Check to see if errors are allowed to be thrown
    *
    * @return bool
    */
    public function allowErrors()
    {
        if ($this->config()->errors === true)
        {
            return true;
        }

        return false;
    }


    /**
    * isReadOnly
    *
    * Check to see if the database can be modified,
    * Otherwise, throw an exception (checking if we can throw errors)
    *
    * @see truncate
    * @return void
    */
    public function isReadOnly()
    {
        if ($this->config()->readOnly === true)
        {
            if ($this->allowErrors()===false) return true;

            throw new Exception("Filebase: This database is set to be read-only. No modifications can be made.");
        }

        return false;
    }


    /**
    *
    * This function is for internal use.
    * Create a directory for the database
    *
    * @param string $path
    * @return void
    */
    public function directory($path = '')
    {
        if ($this->isReadOnly() === false)
        {
            if (!Filesystem::isDirectory($path))
            {
                if (!@Filesystem::makeDirectory($path, 0775, true))
                {
                    throw new Exception(sprintf('`%s` doesn\'t exist and can\'t be created.', $path));
                }
            }
            else if (!Filesystem::isWritable($path))
            {
                throw new Exception(sprintf('`%s` is not writable.', $path));
            }
        }
    }


    /**
    *
    * This function is for internal use.
    * Changes the name of a string to be formatted properly,
    * For being used in file names and directories
    *
    * @return string $name
    */
    public function safeName($name)
    {
        return preg_replace('/[^A-Za-z0-9_\.-]/', '', $name);
    }

}
