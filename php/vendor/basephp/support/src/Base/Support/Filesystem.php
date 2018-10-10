<?php

namespace Base\Support;

use ErrorException;
use FilesystemIterator;

class Filesystem
{

    /**
    * Check if a file or directory exist.
    *
    * @param  string  $path
    * @return bool
    */
    public static function exists($path)
    {
        return file_exists($path);
    }


    /**
    * Get or set UNIX mode of a file or directory.
    *
    * @param  string  $path
    * @param  int     $mode
    * @return mixed
    */
    public static function chmod($path, $mode = null)
    {
        if ($mode) return chmod($path, $mode);

        return substr(sprintf('%o', fileperms($path)), -4);
    }


    /**
    * Delete the file at a given path.
    *
    * @param  string|array $path
    * @return bool
    */
	public static function delete($path)
	{
        $paths = is_array($path) ? $path : func_get_args();

        $success = true;

        foreach ($paths as $path)
        {
            try
            {
                if (! @unlink($path))
                {
                    $success = false;
                }

            }
            catch (ErrorException $e)
            {
                $success = false;
            }
        }

        return $success;
	}


    /**
    * Get the contents of a file.
    *
    * @param  string      $path
    * @return string|bool
    */
    public static function get($path)
    {
        if (static::isFile($path))
        {
            return file_get_contents($path);
        }

        return false;
    }


    /**
    * Write the contents of a file.
    *
    * @param  string  $path
    * @param  string  $contents
    * @param  bool    $lock
    * @return int
    */
    public static function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }


    /**
     * Prepend contents to a file (start)
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public static function prepend($path, $data)
    {
        if (static::exists($path))
        {
            return static::put($path, $data.static::get($path));
        }

        return static::put($path, $data);
    }


    /**
     * Append contents to a file (end)
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public static function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }


    /**
    * Move a file/directory to a new location.
    *
    * @param  string  $path
    * @param  string  $target
    * @param  string  $replaceDirectory
    * @return bool
    */
    public static function move($path, $target, $replaceDirectory = false)
    {
        if ($replaceDirectory && static::isDirectory($target))
        {
            if (! static::deleteDirectory($target))
            {
                return false;
            }
        }

        return @rename($path, $target) === true;
    }


    /**
    * Rename a file or directory
    * Uses the move() method
    *
    * @param  string  $path
    * @param  string  $name
    * @param  string  $replaceDirectory
    * @return bool
    */
    public static function rename($path, $name, $replaceDirectory = false)
    {
        return static::move($path, static::dirname($path).'/'.$name, $replaceDirectory);
    }


    /**
    * Copy a file to a new location.
    *
    * @param  string  $path
    * @param  string  $target
    * @return bool
    */
    public static function copy($path, $target)
    {
        return copy($path, $target);
    }


    /**
    * Extract the file name from a file path.
    *
    * @param  string  $path
    * @return string
    */
    public static function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }


    /**
    * Get all the files within a directory (and by extension if requested)
    *
    * @param  string  $path
    * @param  string  $extension
    * @param  string  $realpath
    * @return array
    */
    public static function getAll($path, $extension = '', $realpath = false, $type = 'all')
    {
        if ($files = array_diff(scandir($path), array('.', '..')))
        {
            if ($extension != '')
            {
                foreach($files as $index=>$file)
                {
                    if (static::extension($file) !== $extension)
                    {
                        unset($files[$index]);
                    }
                }
            }

            if ($type === 'folders')
            {
                $files = array_filter($files,function($file) use ($path) {
                    return (static::isDirectory(rtrim($path,'/').'/'.$file));
                });
            }

            if ($type === 'files')
            {
                $files = array_filter($files,function($file) use ($path) {
                    return (static::isFile(rtrim($path,'/').'/'.$file));
                });
            }

            if ($realpath === true)
            {
                $files = array_map(function($file) use ($path) {
                    return realpath(rtrim($path,'/').'/'.$file);
                }, $files);
            }

            return $files;
        }

        return [];
    }


    /**
    * Get all the files within a directory (and by extension if requested)
    *
    * @param  string  $path
    * @param  string  $extension
    * @param  string  $realpath
    * @return array
    */
    public static function files($path, $extension = '', $realpath = false)
    {
        return static::getAll($path, $extension, $realpath, 'files');
    }


    /**
    * Get all the folders in a directory
    *
    * @param  string  $path
    * @param  string  $realpath
    * @return array
    */
    public static function folders($path, $realpath = false)
    {
        return static::getAll($path, '', $realpath, 'folders');
    }


    /**
    * Count how many files in directory
    *
    * @param  string  $path
    * @return int
    */
    public static function count($path)
    {
        $files = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        return iterator_count($files);
    }


    /**
    * Extract the trailing name component from a file path.
    *
    * @param  string  $path
    * @return string
    */
    public static function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }


    /**
    * Extract the parent directory from a file path.
    *
    * @param  string  $path
    * @return string
    */
    public static function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }


    /**
    * Extract the file extension from a file path.
    *
    * @param  string  $path
    * @return string
    */
    public static function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }


    /**
    * Get the file type of a given file.
    *
    * @param  string  $path
    * @return string
    */
    public static function type($path)
    {
        return filetype($path);
    }


    /**
    * Get the mime-type of a given file.
    *
    * @param  string  $path
    * @return string|false
    */
    public static function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }


    /**
    * Get the file size of a given file.
    *
    * @param  string  $path
    * @return int
    */
    public static function size($path)
    {
        return filesize($path);
    }


    /**
    * Get the file's last modification time.
    *
    * @param  string  $path
    * @return int
    */
    public static function lastModified($path)
    {
        return filemtime($path);
    }


    /**
    * Create a new directory.
    *
    * @param  string  $path
    * @param  string  $chmod
    * @param  string  $recursive
    * @return bool
    */
    public static function makeDirectory($path, $chmod = 0775, $recursive = false)
    {
        if (!static::isDirectory($path))
        {
            return mkdir($path, $chmod, $recursive);
        }

        return false;
    }


    /**
    * Determine if the given path is a directory.
    *
    * @param  string  $directory
    * @return bool
    */
    public static function isDirectory($directory)
    {
        return is_dir($directory);
    }


    /**
    * Determine if the given path is a file.
    *
    * @param  string  $file
    * @return bool
    */
    public static function isFile($file)
    {
        return is_file($file);
    }


    /**
    * Determine if the given path is readable.
    *
    * @param  string  $path
    * @return bool
    */
    public static function isReadable($path)
    {
        return is_readable($path);
    }


    /**
    * Determine if the given path is writable.
    *
    * @param  string  $path
    * @return bool
    */
    public static function isWritable($path)
    {
        return is_writable($path);
    }


    /**
    * Find path names matching a given pattern.
    *
    * @param  string  $pattern
    * @param  int     $flags
    * @return array
    */
    public static function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }


    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool    $preserve
     * @return bool
     */
    public static function deleteDirectory($directory, $preserve = false)
    {
        if (! static::isDirectory($directory))
        {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item)
        {
            if ($item->isDir() && ! $item->isLink())
            {
                static::deleteDirectory($item->getPathname());
            }
            else
            {
                static::delete($item->getPathname());
            }
        }

        if (! $preserve)
        {
            @rmdir($directory);
        }

        return true;
    }


    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory
     * @return bool
     */
    public static function empty($directory)
    {
        return static::deleteDirectory($directory, true);
    }
}
