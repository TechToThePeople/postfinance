<?php

use CRM_Utils_Array as A;
use CRM_Core_Error as E;
use CRM_Postfinance_Util as Util;

class CRM_Postfinance_Payment extends CRM_Core_Payment {

  static private $_singleton = array();

  protected $checkout;
  protected $ipn;
  protected $logger;

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

    // Protected attributes of the parent class.
    $this->_mode = $mode;
    $this->_paymentProcessor = $info;
    $this->_processorName = ts('Post Finance');

    // Protected attributes that we introduce.

    // Logger
    $this->logger = new CRM_Postfinance_Logger();

    // This thing knows long static lists.
    $legend = new CRM_Postfinance_Legend();

    // CheckoutParamCollector with SHA-IN
    $secret = $this->_paymentProcessor['password'];
    $keys = $legend->shaInParams();
    $shaIn = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha1');
    $this->checkout = new CRM_Postfinance_CheckoutParamCollector($info, $shaIn);

    // IPN with SHA-OUT
    // TODO: shaOut should have a different secret than shaIn.
    $secret = $this->_paymentProcessor['password'];
    $keys = $legend->shaOutParams();
    dpm($this->_paymentProcessor);
    dpm($secret, '$secret');
    $shaOut = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha1');
    $this->ipn = new CRM_Postfinance_IPN($info, $shaOut);
  }

  /**
   * Check that the params in $this->_paymentProcessor are all legit.
   *
   * TODO:
   *   This stuff is all copied. We need to adapt.
   *
   * @param string $mode
   *   The mode we are operating in (live or test)
   *   The parent class has this comment, but it doesn't have the parameter on
   *   this abstract function.
   *
   * @return array
   *   The result in an nice formatted array (or an error object)
   *   (the example code returns a string instead, so...)
   */
  function checkConfig() {

    $errors = '';

    if (empty($this->_paymentProcessor['user_name'])) {
      $errors .= '<p>' . ts('UserID is not set in the Administer CiviCRM &raquo; Payment Processor.') . '</p>';
    }

    if (empty($this->_paymentProcessor['password'])) {
      $errors .= '<p>' . ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.') . '</p>';
    }

    return strlen($errors) ? $errors : NULL;
  }

  // TODO: Can we remove this crap?
  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @abstract
   */
  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }
  function setExpressCheckOut( &$params ) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }
  function getExpressCheckoutDetails( $token ) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }
  function doExpressCheckout( &$params ) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }

  /**
   * Main transaction function
   *
   * @param array $params
   *   Name value pair of contribution data
   * @param string $component
   *   Can be either 'contribute' or 'event' or the name of another civicrm component.
   *
   * @return void
   */
  function doTransferCheckout(&$params, $component) {

    $component = strtolower($component);
    $paymentProcessorParams = $this->checkout->collectCheckoutParams($params, $component);

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $paymentProcessorParams);

    $urlQueryString = Util::urlQueryString($paymentProcessorParams);
    $processorURL = $this->_paymentProcessor['url_site'] . '?' . $urlQueryString;
    CRM_Utils_System::redirect($processorURL);
  }

  /**
   * Handle the IPN
   * This does not return anything, and will exit() instead.
   */
  function handlePaymentNotification() {

    $this->logger->log($_GET, 'IPN $_GET');
    $this->logger->log($_POST, 'IPN $_POST');

    $component = A::value('mo', $_GET, 'contribute');

    // Attempt to determine component type ...
    switch ($component) {

      case 'contribute':
      case 'event':
        ob_start();
        $this->ipn->main($component, $_POST);
        $output = ob_get_clean();
        break;

      default:
        CRM_Core_Error::debug_log_message("Could not get component name from request url");
        $output = "Could not get component name from request url\r\n";
    }

    // Print and exit(),
    // to prevent CiviCRM from doing the usual request output.
    print $output;
    exit();
  }
}
