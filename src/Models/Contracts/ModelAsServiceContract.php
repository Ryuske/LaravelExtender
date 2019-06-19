<?php namespace Ryuske\LaravelExtender\Models\Contracts;

use Ryuske\LaravelExtender\Models\Structs\QueryStructContract;

interface ModelAsServiceContract {
  public function queryAll();

  /**
   * @param QueryStructContract $parameters
   * @return mixed
   */
  public function querySingle(QueryStructContract $parameters);

  /**
   * @param QueryStructContract $parameters
   * @return mixed
   */
  public function querySearchBy(QueryStructContract $parameters);
}
