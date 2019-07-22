<?php namespace Ryuske\LaravelExtender\Models\Structs;

use Illuminate\Database\Eloquent\Model;

trait QueryStructMethods {
  /**
   * @var array
   */
  private $_where         = [];

  /**
   * @var array
   */
  private $_with          = [];

  /**
   * @var array
   */
  private $_joins         = [];

  /**
   * @var bool
   */
  private $_distinct      = false;

  /**
   * @var array
   */
  private $_orderBy       = [];

  /**
   * @var array
   */
  private $_groupBy       = [];

  /**
   * @var int
   */
  private $_pagination    = 0;

  /**
   * @var array
   */
  private $_returnedData = ['*'];

  /**
   * @var string
   */
  private $_sum          = '';

  /**
   * @var Model
   */
  private $_eloquent;

  /**
   * @param Model $eloquentModel
   */
  public function __construct(Model $eloquentModel) {
    $this->_eloquent = $eloquentModel;
  }

  /**
   * Used to return all the columns designed to be used by eloquents ->where() method
   *
   * @return array
   */
  public function getWhere(): array {
    return $this->_where;
  }

  /**
   * Used to return all the models that should be eager loaded via eloquents ->with() method
   *
   * @return array
   */
  public function getWith(): array {
    return $this->_with;
  }

  /**
   * Used to return all the tabels to join in the current query
   *
   * @return array
   */
  public function getJoins(): array {
    return $this->_joins;
  }

  /**
   * Used to return if the query should be DISTINCT
   *
   * @return array
   */
  public function getDistinct(): bool {
    return $this->_distinct;
  }

  /**
   * @return self
   */
  public function setDistinct() {
    $this->_distinct = true;

    return $this;
  }

  /**
   * Used to set the fields that are returned from the datastore
   *
   * @param array $data
   * @return self
   */
  public function setReturnedData(array $data) {
    $this->_returnedData = $data;

    return $this;
  }

  /**
   * Used to get the fields that are returned from the datastore
   *
   * @return array
   */
  public function getReturnedData(): array {
    return $this->_returnedData;
  }

  /**
   * @param string $field
   * @param string $order
   * @return $this
   */
  public function setOrderBy(string $field, string $order='desc') {
    $this->_orderBy = ['field' => $field, 'order' => $order];

    return $this;
  }

  /**
   * @return array
   */
  public function getOrderBy(): array {
    return $this->_orderBy;
  }

  /**
   * @return array
   */
  public function getGroupBy(): array {
    return $this->_groupBy;
  }

  /**
   * @param int $resultsPerPage
   * @return $this
   */
  public function setPagination(int $resultsPerPage) {
    $this->_pagination = $resultsPerPage;

    return $this;
  }

  /**
   * @return int
   */
  public function getPagination(): int {
    return $this->_pagination;
  }

  /**
   * @param string $column
   * @return $this
   */
  public function setSum(string $column) {
    $this->_sum = $column;

    return $this;
  }

  /**
   * @return string
   */
  public function getSum(): string {
    return $this->_sum;
  }

  /**
   * @return Model
   */
  public function getEloquent() {
    return $this->_eloquent;
  }

  /**
   * @param $eloquent
   * @return self
   */
  public function updateEloquent($eloquent) {
    $this->_eloquent = $eloquent;

    return $this;
  }
}