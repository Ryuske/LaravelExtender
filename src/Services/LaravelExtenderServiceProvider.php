<?php namespace Ryuske\LaravelExtender\Services;

use Illuminate\Support\ServiceProvider;
use Validator;
use Hash;

class LaravelExtenderServiceProvider extends ServiceProvider {
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot() {
    Validator::extend('is_password', function ($attribute, $value, $parameters, $validator) {
      return Hash::check($value, auth()->user()->getPassword());
    });

    Validator::extend('enum_exists', function ($attribute, $value, $parameters, $validator) {
      $reflectionClass = new ReflectionClass($parameters[0]);

      return in_array($value, $reflectionClass->getConstants());
    });
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {

  }
}
