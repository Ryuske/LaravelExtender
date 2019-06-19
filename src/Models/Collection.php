<?php namespace Ryuske\LaravelExtender\Models;

use Ryuske\LaravelExtender\Exceptions\MethodNotFound;
use Ryuske\LaravelExtender\Exceptions\WrongEntityType;
use Exception;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection {
  /**
   * @var AbstractPaginator
   */
  private $_paginator;

  /**
   * @var string
   */
  protected $collectionOf;

  public function __construct($items = []) {
    parent::__construct($items);

    if (empty($this->collectionOf)) {
      throw new Exception('$collectionOf not set in ' . get_class($this));
    }
  }

  /**
   * @param string $method
   * @param array $parameters
   * @return mixed
   * @throws MethodNotFound
   */
  public function __call($method, $parameters) {
    if (method_exists($this, $method)) {
      return call_user_func_array([$this, $method], $parameters);
    }

    if (is_object($this->_paginator) && method_exists($this->_paginator, $method)) {
      return call_user_func_array([$this->_paginator, $method], $parameters);
    }

    throw new MethodNotFound($method, [$this, $this->_paginator]);
  }

  /**
   * @param AbstractPaginator $paginator
   */
  public function setPaginator(AbstractPaginator $paginator) {
    $this->_paginator = $paginator;
  }

  /**
   * @return AbstractPaginator $paginator
   */
  public function getPaginator() {
    return $this->_paginator;
  }

  /**
   * @param mixed $key
   * @param mixed $value
   * @throws WrongEntityType
   */
  public function offsetSet($key, $value)
  {
    if (!$value instanceof $this->collectionOf) {
      throw new WrongEntityType('"' . get_class($value) . '" must be instance of "' . $this->collectionOf . '".');
    }

    parent::offsetSet($key, $value);
  }

  /**
   * @param mixed $key
   * @param string $unused
   * @return mixed
   */
  public function get($key, $unused='unused') {
    if ($this->offsetExists($key)) {
      return $this->items[$key];
    }

    return new $this->collectionOf;
  }
}