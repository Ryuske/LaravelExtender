<?php namespace Ryuske\LaravelExtender\Models\Traits;

use Carbon\Carbon;

trait SoftDeleteTimestamp {
  /**
   * @return Carbon\NULL
   */
  public function getDeletedAt() {
    return $this->_fields['deleted_at'] ?? NULL;
  }

  /**
   * @param Carbon|NULL $deletedAt
   * @return $this
   */
  protected function setDeletedAt(?Carbon $deletedAt) {
    $this->_fields['deleted_at'] = $deletedAt;

    return $this;
  }
}
