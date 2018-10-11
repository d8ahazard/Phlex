<?php

namespace Base\Support;

use Closure;

class Container
{

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * The tags assigned to instance handles
     *
     * @var array
     */
    protected $tags = [];


    /**
     * Get the resolved instances
     *
     * @return array
     */
    public function getResolved()
    {
        return array_keys($this->instances);
    }

    /**
     * Get all active instances
     *
     * @return array
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param  string  $key
     * @return bool
     */
    public function isAlias($key)
    {
        return isset($this->aliases[$key]);
    }

    /**
     * Get an alias
     *
     * @param  string  $name
     * @return string
     *
     */
    public function getAlias($key)
    {
        if ($this->isAlias($key)) {
            return $this->aliases[$key];
        }

        return $key;
    }

    /**
     * Set alias
     *
     * @param  mixed  $alias
     *
     */
    public function setAlias($alias)
    {
        $alias = (array) $alias;

        $this->aliases = array_merge($this->aliases, $alias);

        return $this;
    }

    /**
     * Register an instance that has already been established
     *
     * @param  string  $name
     * @param  object  $instance
     * @return bool
     */
    public function register($name, $instance)
    {
        return $this->instances[$name] = $instance;
    }

    /**
     * Check if the instance exist
     *
     * @param  string  $handle
     *
     */
    public function has($handle)
    {
        return isset($this->instances[$handle]);
    }

    /**
     * Get the instance if exist
     *
     * @param  string  $id
     *
     */
    public function get($handle)
    {
        if ($this->has($handle)) {
            return $this->resolve($handle);
        }

        return null;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($handle, array $parameters = [])
    {
        return $this->resolve($handle, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    protected function resolve($handle, $parameters = [])
    {
        // holding off on the closure at this time.
        // I dont think its worth the memory consumption
        // if ($handle instanceof Closure) {
            // return $handle($this);
        // }

        $namespace = $this->getAlias($handle);

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$handle])) {
            return $this->instances[$handle];
        }

        if (!empty($parameters)) {
            $obj = new $namespace(...$parameters);
        } else {
            $obj = new $namespace;
        }

        return $this->instances[$handle] = $obj;
    }

    /**
     * Assign a set of tags to a given instances.
     *
     * @param  array|string  $handles
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($handles, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $handles as $handle) {
                $this->tags[$tag][] = $handle;
            }
        }
    }

    /**
     * Resolve all of the instances for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $handle) {
                $results[] = $this->make($handle);
            }
        }

        return $results;
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetInstance($handle)
    {
        unset($this->instances[$handle]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
    * Set the container instance
    *
    * @return static
    */
    public static function setInstance(Container $container)
    {
        return static::$instance = $container;
    }

    /**
    * Get an instance
    *
    * @param  string  $key
    * @return mixed
    */
    public function __get($key)
    {
        return $this->get($key);
    }

}
