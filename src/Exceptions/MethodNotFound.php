<?php namespace Ryuske\LaravelExtender\Exceptions;

use Exception;

class MethodNotFound extends Exception {
  /**
   * @param string $method
   * @param array $classes
   * @param int $code
   * @param Exception|NULL $previous
   * @internal param string $message
   */
  public function __construct(string $method, array $classes, $code = 0, Exception $previous = NULL) {
    $message = "Method `$method` not found in";
    $i       = 0;

    foreach ($classes as $class) {
      if (is_object($class)) {
        if (0 < $i) {
          $message .= ' or';
        }

        $message .= ' ' . get_class($class);

        $i++;
      }
    }

    parent::__construct($message, $code, $previous);
  }
}
