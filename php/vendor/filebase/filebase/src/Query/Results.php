<?php  namespace Filebase\Query;

use Exception;
use Filebase\Table;
use Filebase\Document;
use Filebase\Query\Builder;
use Base\Support\Arr;

class Results
{

    /**
    * $builder
    *
    * @var Filebase\Query\Builder
    */
    protected $builder;


    /**
    * $predicate
    *
    * @var Filebase\Query\Predicate
    */
    protected $predicate;


    /**
    * documentResults
    *
    * @var array
    */
    protected $documentResults = [];


    /**
    * __construct
    *
    * @param Filebase\Builder $builder
    *
    */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

        $this->predicate = $this->builder->predicate()->get();
    }


    /**
    * table
    *
    *
    * @param Filebase\Table
    *
    */
    protected function table()
    {
        return $this->builder->table();
    }


    /**
    * get
    *
    * Get the results of the query....
    *
    * @param Filebase\Builder $builder
    *
    */
    public function get()
    {
        $this->documentResults = [];

        if (!empty($this->predicate))
        {
            $this->documentResults = $this->filter($this->table()->getAll(), $this->predicate);

            $this->sort();
            $this->limit();
        }

        return $this;
    }


    /**
    * results
    *
    *
    * @return documentResults
    */
    public function results()
    {
        return $this->documentResults;
    }


    /**
    * count
    *
    * Count how many items are within the query
    *
    * @return int
    */
    public function count()
    {
        return count($this->documentResults);
    }


    /**
    * first
    *
    * Get the first document in the array
    *
    * @return int
    */
    public function first()
    {
        return current($this->documentResults);
    }


    /**
    * last
    *
    * Get the last document in the array
    *
    * @return int
    */
    public function last()
    {
        return end($this->documentResults);
    }


    /**
    * sort
    *
    */
    protected function sort()
    {
        $orderBy = $this->builder->orderBy;

        $sortBy  = $this->builder->sortBy;

        if ($orderBy=='')
        {
            return false;
        }

        usort($this->documentResults, function($a, $b) use ($orderBy, $sortBy) {

            $propA = $a->get($orderBy);
            $propB = $b->get($orderBy);

            if (strnatcasecmp($propB, $propA) == strnatcasecmp($propA, $propB)) {
                return 0;
            }

            if ($sortBy == 'DESC')
            {
                return (strnatcasecmp($propB, $propA) < strnatcasecmp($propA, $propB)) ? -1 : 1;
            }
            else
            {
                return (strnatcasecmp($propA, $propB) < strnatcasecmp($propB, $propA)) ? -1 : 1;
            }

        });

    }


    /**
    * limit
    *
    */
    protected function limit()
    {
        if ($this->builder->limit != 0 || $this->builder->offset != 0)
        {
            $this->documentResults = array_slice($this->documentResults, $this->builder->offset, $this->builder->limit);
        }
    }



    /**
    * filter
    *
    * Filter results from query
    *
    */
    protected function filter($documents, $predicates)
    {
        $documents = $this->filterPredicates($documents, $predicates);

        return $documents;
    }


    /**
    * filterPredicate
    *
    *
    */
    protected function filterPredicates($documents, $predicates, $type = '')
    {
        foreach($predicates['and'] as $index=>$predicate)
        {
            list($field, $operator, $value) = $predicate;

            $documents = Arr::where($documents, function($document) use ($field, $operator, $value) {
                return $this->match($document, $field, $operator, $value);
            });
        }

        return $documents;
    }


    /**
    * match
    *
    */
    public function match($document, $field, $operator, $value)
    {
        $operator = strtoupper($operator);

        $d_value = $document->get($field);
        switch (true)
        {
            case ($operator === '=' && $d_value == $value):
                return true;
            case ($operator === '==' && $d_value == $value):
                return true;
            case ($operator === '===' && $d_value === $value):
                return true;
            case ($operator === '!=' && $d_value != $value):
                return true;
            case ($operator === '!==' && $d_value !== $value):
                return true;
            case ($operator === 'NOT' && $d_value != $value):
                return true;
            case ($operator === 'NOT IN' && $d_value != $value):
                return true;
            case ($operator === '>'  && $d_value >  $value):
                return true;
            case ($operator === '>=' && $d_value >= $value):
                return true;
            case ($operator === '=>' && $d_value >= $value):
                return true;
            case ($operator === '<'  && $d_value <  $value):
                return true;
            case ($operator === '<=' && $d_value <= $value):
                return true;
            case ($operator === '=<' && $d_value <= $value):
                return true;
            case ($operator === 'LIKE' && preg_match('/'.$value.'/is',$d_value)):
                return true;
            case ($operator === 'NOT LIKE' && !preg_match('/'.$value.'/is',$d_value)):
                return true;
            case ($operator === 'IN' && in_array($d_value, (array) $value)):
                return true;
            case ($operator === 'IN' && in_array($value, (array) $d_value)):
                return true;
            case ($operator === 'REGEX' && preg_match($value, $d_value)):
                return true;
            default:
                return false;
        }
    }

}
