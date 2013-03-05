<?php

class CRM_Postfinance_Payment extends CRM_Core_Payment {

  static private $_singleton = NULL;

  /**
   * Singleton.
   * Seems like that is a necessary part.
   */
  static function singleton($mode, &$info) {
    $name = $info['name'];
    if (!isset(self::$_singleton[$name])) {
      self::$_singleton[$name] = new self($mode, $info);
    }
    return self::$_singleton[$name];
  }

  /**
   * Constructor
   *
   * @param string $mode
   *   The mode of the operation: live or test.
   * @param array $info
   *   Not sure what that is.
   *   It is called $paymentProcessor in other examples, but that's a bit stupid
   *   name for an array.
   */
  function __construct($mode, &$info) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $info;
    $this->_processorName = ts('Post Finance');
  }
}
