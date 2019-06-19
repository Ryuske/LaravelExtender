<?php namespace Ryuske\LaravelExtender\Models\Eloquent;

use Ryuske\LaravelExtender\Models\Eloquent\QueryBuilder as Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel {
  /**
   * Get a new query builder instance for the connection.
   *
   * @return Builder
   */
  protected function newBaseQueryBuilder() {
    $connection = $this->getConnection();

    return new Builder(
      $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
    );
  }
}
