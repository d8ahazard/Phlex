<?php

namespace Base\Support;

use Base\Support\Container;
use Closure;

class Pipeline
{

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;


    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];


    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';


    /**
     * Create a new class instance for the pipeline
     *
     * @param  \Base\Support\Container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }


    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }


    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }


    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }


    /**
     * Get the final piece of the Closure
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }


    /**
     * Get a closure and return the response
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // if the pipe is a closure, lets call it directly
                    return $pipe($passable, $stack);
                } elseif (!is_object($pipe)) {

                    list($name, $parameters) = $this->parsePipeString($pipe);

                    if ($this->container !== null) {
                        // Providing a container allows a convenient way to retrieve the instance later
                        // if the container does exist, we will push it into the container bindings
                        $pipe = $this->container->make($name);
                    } else {
                        // if container doesnt exist, we will instantiate our
                        // instance class now, we just wont be able to get it later
                        if (!class_exists($name)) {
                            return NULL;
                        }

                        $pipe = new $name();
                    }

                    $parameters = array_merge([$passable, $stack], $parameters);

                } else {
                    $parameters = [$passable, $stack];
                }

                $response = method_exists($pipe, $this->method)
                                ? $pipe->{$this->method}(...$parameters)
                                : $pipe(...$parameters);

                return $response;
            };
        };
    }


    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

}
