<?php

class CRM_Postfinance_Payment extends CRM_Core_Payment {

  static private $_singleton = array();

  /**
   * "Singleton": Request an instance of this payment method based on the $info
   * parameters. If an instance with the same $info['name'] already exists, it
   * will return that, otherwise it will create a new one.
   *
   * @param string $mode
   *   The mode of the operation: live or test.
   * @param array $info
   *   Not sure what that is.
   *   It is called $paymentProcessor in other examples, but that's a bit stupid
   *   name for an array. This thing is not the processor itself, it is just an
   *   array of params.
   */
  static function singleton($mode, $info) {
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
   *   Various information about the payment processor instance.
   *   It is called $paymentProcessor in other examples, but that's a bit stupid
   *   name for an array.
   */
  function __construct($mode, $info) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $info;
    $this->_processorName = ts('Post Finance');
  }
}
