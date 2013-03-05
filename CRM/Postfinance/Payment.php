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
   */
  function __construct($mode, &$info) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $info;
    $this->_processorName = ts('Post Finance');
  }

  function 
}
