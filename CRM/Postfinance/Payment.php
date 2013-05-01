<?php

use CRM_Utils_Array as A;
use CRM_Core_Error as E;
use CRM_Postfinance_Util as Util;

class CRM_Postfinance_Payment extends CRM_Core_Payment {

  protected $_paymentProcessor;

  protected $_paymentForm = NULL;

  protected $checkout;
  protected $ipn;
  protected $logger;

  /**
   * Constructor
   *
   * @param string $mode
   *   The mode of the operation: live or test.
   * @param array $info
   *   Array of configuraton params for the payment processor instance.
   *   It is called $paymentProcessor in other examples. We name it $info, to
   *   avoid it being mistaken for an object.
   */
  function __construct($mode, $info) {

    // Protected attributes of the parent class.
    $this->_mode = $mode;
    $this->_paymentProcessor = $info;
    $this->_processorName = 'Post Finance';

    // Protected attributes that we introduce.
    $this->initServices();
  }

  protected function initServices() {

    $info = $this->_paymentProcessor;

    // Logger
    $this->logger = new CRM_Postfinance_Logger();

    // This thing knows long static lists.
    $legend = new CRM_Postfinance_Legend();

    // CheckoutParamCollector with SHA-IN
    $secret = $info['password'];
    $keys = $legend->shaInParams();
    $shaIn = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha512');
    $this->checkout = new CRM_Postfinance_CheckoutParamCollector($info, $shaIn);

    // IPN with SHA-OUT
    $secret = isset($info['signature']) ? $info['signature'] : $info['password'];
    $keys = $legend->shaOutParams();
    $shaOut = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha512');
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

    $output = $this->ipn->handleIPN($_POST);

    // Print and exit(),
    // to prevent CiviCRM from doing the usual request output.
    print $output;
    exit();
  }
}
