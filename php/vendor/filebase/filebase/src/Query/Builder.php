<?php  namespace Filebase\Query;

use Exception;
use Filebase\Table;
use Filebase\Document;
use Filebase\Query\Results;
use Filebase\Query\Predicate;

class Builder
{

    /**
    * $table
    *
    * @var Filebase\Table
    */
    protected $table;


    /**
    * $predicate
    *
    * @var Filebase\Query\Predicate
    */
    protected $predicate;


    /**
    * $limit
    *
    * @var int
    */
    public $limit  = 0;


    /**
    * $offset
    *
    * @var int
    */
    public $offset  = 0;


    /**
    * $orderBy
    *
    * @var string
    */
    public $orderBy = '';


    /**
    * $sortBy
    *
    * @var string
    */
    public $sortBy = 'ASC';


    /**
    * __construct
    *
    * @param Filebase\Table $table
    *
    */
    public function __construct(Table $table)
    {
        $this->table = $table;

        $this->predicate = new Predicate();
    }


    /**
    * predicate()
    *
    * @return Filebase\Query\Predicate
    */
    public function predicate()
    {
        return $this->predicate;
    }


    /**
    * table()
    *
    * @return Filebase\Table
    */
    public function table()
    {
        return $this->table;
    }


    /**
    * where()
    *
    * Set up a where query
    *
    * @param mixed $arg
    *
    * @return Filebase\Query\Builder
    */
    public function where(...$arg)
    {
        $this->predicate()->add('and', ...$arg);

        return $this;
    }


    /**
    * in()
    *
    * Set up a where query
    *
    * @param string $field
    * @param mixed $values
    *
    * @return Filebase\Query\Builder
    */
    public function in($field, $values)
    {
        $this->predicate()->add('and', $field, 'IN', $values);

        return $this;
    }


    /**
    * not()
    *
    * Set up a where query
    *
    * @param string $field
    * @param mixed $values
    *
    * @return Filebase\Query\Builder
    */
    public function not($field, $values)
    {
        $this->predicate()->add('and', $field, 'NOT', $values);

        return $this;
    }


    /**
    * like()
    *
    * Set up a where query
    *
    * @param string $field
    * @param mixed $values
    *
    * @return Filebase\Query\Builder
    */
    public function like($field, $values)
    {
        $this->predicate()->add('and', $field, 'LIKE', $values);

        return $this;
    }


    /**
    * notLike()
    *
    * Set up a where query
    *
    * @param string $field
    * @param mixed $values
    *
    * @return Filebase\Query\Builder
    */
    public function notLike($field, $values)
    {
        $this->predicate()->add('and', $field, 'NOT LIKE', $values);

        return $this;
    }


    /**
    * limit()
    *
    * Set the query limit and offset
    *
    * @param int $limit
    * @param int $offset
    *
    * @return Filebase\Query\Builder
    */
    public function limit($limit, $offset = 0)
    {
        $this->limit = (int) $limit;

        if ($this->limit === 0)
        {
            $this->limit = 9999999;
        }

        $this->offset = (int) $offset;

        return $this;
    }


   /**
   * orderBy()
   *
   * Set the order by and sort by fields
   *
   * @param string $orderByField
   * @param string $sortDirection
   *
   * @return Filebase\Query\Builder
   */
   public function orderBy($orderByField, $sortDirection = 'ASC')
   {
       $this->orderBy = $orderByField;

       $this->sortBy = $sortDirection;

       return $this;
   }


   /**
   * get()
   *
   * Query builder results
   *
   * @return Filebase\Query\Results
   */
   public function get()
   {
       return (new Results($this))->get();
   }

}
