<?php namespace Ryuske\LaravelExtender\Services;

abstract class SingletonServiceProvider {
  public function __construct() {
    /**
     * Make it so that the same instance of the service is always resolved from the container
     */
    app()->singleton(get_class($this), function ($app) {
      return $app->make($this);
    });
  }
}
