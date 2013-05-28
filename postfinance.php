<?php

// Make the autoloader work.
$extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
set_include_path($extRoot . PATH_SEPARATOR . get_include_path());

class eu_tttp_postfinance extends CRM_Postfinance_Payment {

  static private $_singleton = array();

  /**
   * "Singleton": Request an instance of this payment method based on the $info
   * parameters. If an instance with the same $info['name'] already exists, it
   * will return that, otherwise it will create a new one.
   *
   * @param string $mode
   *   The mode of the operation: live or test.
   * @param array $info
   *   Array of configuraton params for the payment processor instance.
   *   It is called $paymentProcessor in other examples. We name it $info, to
   *   avoid it being mistaken for an object.
  static function singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = false) {
    $name = $info['name'];
    if (!isset(self::$_singleton[$name])) {
      self::$_singleton[$name] = new self($mode, $info);
      serialize(self::$_singleton[$name]);
    }
    return self::$_singleton[$name];
  }
   */

  /**
   * Magic __sleep() method, called before the object is serialized.
   *
   * Payment processors are always serialized when a payment form is submitted.
   * They are unserialized on the next request.
   *
   * When the unserialize() happens, none of the extension's PHP files are
   * included yet, and thus, none of the extension's classes are defined.
   *
   * We also cannot rely on autoload, because CiviCRM won't add payment
   * extensions to the PATH.
   *
   * The only class that CiviCRM recognizes is the eu_tttp_postfinance class.
   * To make this class available, it includes the postfinance.php, which
   * activates the autoload for the other postfinance classes. So, we can do all
   * the stuff in __wakeup().
   */
  public function __sleep() {

    // Find all keys that could be serialized.
    $keys = array();
    foreach ($this as $k => $v) {
      $keys[$k] = $k;
    }

    // We need to avoid that services are instantiated on unserialize(),
    // before the main object is instantiated. .
    unset($keys['checkout']);
    unset($keys['ipn']);
    unset($keys['logger']);

    return array_keys($keys);
  }

  /**
   * Magic __wakeup() method, called after the object is unserialized.
   */
  public function __wakeup() {
    $this->initServices();
  }
}
