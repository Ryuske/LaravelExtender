<?php namespace Ryuske\LaravelExtender\Services\Contracts;

use Ryuske\LaravelExtender\Models\Structs\QueryStructContract;

interface QueryableServiceProviderContract {
  /**
   * @return mixed
   */
  public function all();

  /**
   * @param QueryStructContract $parameters
   * @return mixed
   */
  public function single(QueryStructContract $parameters);

  /**
   * @param QueryStructContract $parameters
   * @return mixed
   */
  public function searchBy(QueryStructContract $parameters);

  /**
   * @return QueryStructContract
   */
  public function singleStruct(): QueryStructContract;

  /**
   * @return QueryStructContract
   */
  public function searchByStruct(): QueryStructContract;
}
