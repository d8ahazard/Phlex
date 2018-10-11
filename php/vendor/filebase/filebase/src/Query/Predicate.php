<?php  namespace Filebase\Query;


use Exception;

class Predicate
{

    /**
    * $allowedOperators
    * Allowed operators within the query
    *
    * @var array
    */
    protected $allowedOperators = [
        '=',
        '==',
        '===',
        '!=',
        '!==',
        '>',
        '<',
        '>=',
        '<=',
        '=>',
        '=<',
        'IN',
        'NOT',
        'NOT IN',
        'LIKE',
        'NOT LIKE',
        'REGEX'
    ];


    /**
    * $predicates
    *
    * @var array
    */
    protected $predicates = [];


    /**
    * add
    *
    */
    public function add($logic, ...$arg)
    {
        if (isset($arg[0]) && is_array($arg[0]))
        {
            foreach($arg as $index => $fields)
            {
                foreach($fields as $field => $value)
                {
                    $this->predicates[$logic][] = $this->format($field, $value);
                }
            }
        }
        else
        {
            if (count($arg) === 3)
            {
                if (isset($arg[1]) && in_array($arg[1], $this->allowedOperators))
                {
                    $this->predicates[$logic][] = $arg;
                }
            }
        }
    }


    /**
    * format
    *
    */
    protected function format($key, $value)
    {
        return [$key,'==',$value];
    }


    /**
    * get
    *
    */
    public function get()
    {
        return array_filter($this->predicates);
    }

}
