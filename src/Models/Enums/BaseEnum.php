<?php namespace Ryuske\LaravelExtender\Models\Enums;

use ReflectionClass;

class BaseEnum {
  static public function has(string $value) {
    return in_array($value, (new ReflectionClass(get_called_class()))->getConstants());
  }
}