<?php namespace Ryuske\LaravelExtender\Models\Traits;

use Carbon\Carbon;

trait Timestamps {
  /**
   * @return Carbon
   */
  public function getCreatedAt(): Carbon {
    return $this->_fields['created_at'] ?? Carbon::now();
  }

  /**
   * @param Carbon $createdAt
   * @return $this
   */
  protected function setCreatedAt(Carbon $createdAt) {
    $this->_fields['created_at'] = $createdAt;

    return $this;
  }

  /**
   * @return Carbon
   */
  public function getUpdatedAt(): Carbon {
    return $this->_fields['updated_at'] ?? Carbon::now();
  }

  /**
   * @param Carbon $updatedAt
   * @return $this
   */
  protected function setUpdatedAt(Carbon $updatedAt) {
    $this->_fields['updated_at'] = $updatedAt;

    return $this;
  }
}
