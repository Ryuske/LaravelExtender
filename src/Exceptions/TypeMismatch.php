<?php namespace Ryuske\LaravelExtender\Exceptions;

use Exception;

class TypeMismatch extends Exception {
  /**
   * @param string $message
   * @param int $code
   * @param Exception|NULL $previous
   */
  public function __construct($message, $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }
}
