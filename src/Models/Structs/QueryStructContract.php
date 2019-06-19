<?php namespace Ryuske\LaravelExtender\Models\Structs;

interface QueryStructContract {
  /**
   * @return array
   */
  public function getWhere(): array;

  /**
   * @return array
   */
  public function getWith(): array;

  /**
   * @return bool
   */
  public function getDistinct(): bool;

  /**
   * @return array
   */
  public function getReturnedData(): array;

  /**
   * @return array
   */
  public function getOrderBy(): array;

  /**
   * @return array
   */
  public function getGroupBy(): array;

  /**
   * @return int
   */
  public function getPagination(): int;

  /**
   * @return string
   */
  public function getSum(): string;
}
