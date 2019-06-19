<?php namespace Ryuske\LaravelExtender\Models\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Builder;

class QueryBuilder extends Builder {

  /**
   * Add a "where in" clause to the query.
   *
   * @param  string  $column
   * @param  mixed   $values
   * @param  string  $boolean
   * @param  bool    $not
   * @return $this
   */
  public function whereIn($column, $values, $boolean = 'and', $not = false) {
    if ($values instanceof Arrayable) {
      $values = $values->toArray();
    }

    $valuesAreInts = true;

    foreach ($values as $value) {
      if (!is_int($value)) {
        $valuesAreInts = false;
        break;
      }
    }

    if ($valuesAreInts) {
      $column   = $this->grammar->wrap($column);
      $operator = $not ? 'not in' : 'in';
      $sql      = "$column $operator (" . implode($values, ',') . ")";

      $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];

      return $this;
    }

    return parent::whereIn($column, $values, $boolean, $not);
  }
}
