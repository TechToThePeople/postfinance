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

    $errors = array();

    if ( empty( $this->_paymentProcessor['user_name'] ) ) {
      $error[] = ts( 'UserID is not set in the Administer CiviCRM &raquo; Payment Processor.' );
    }

    if ( empty( $this->_paymentProcessor['password'] ) ) {
      $error[] = ts( 'password is not set in the Administer CiviCRM &raquo; Payment Processor.' );
    }

    if ( ! empty( $error ) ) {
      return implode( '<p>', $error );
    } else {
      return null;
    }
  }

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

    $cancelURL = $this->getCancelURL($params, $component);
    $component = strtolower($component);
    $paymentProcessorParams = $this->mapParamstoPaymentProcessorFields($params, $component);

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $paymentProcessorParams);

    $processorURL = $this->_paymentProcessor['url_site'] . "?" . $this->buildPaymentProcessorString($paymentProcessorParams);
    CRM_Utils_System::redirect($processorURL);
  }

  /**
   * Get URL which the browser should be returned to if they cancel or are unsuccessful
   *
   * @param array $params
   *   Name value pair of contribution data
   * @param string $component
   *   Can be either 'contribute' or 'event' or the name of another civicrm component.
   *
   * @return string
   *   Fully qualified return URL
   *
   * @todo Ideally this would be in the parent payment class
   */
  function getCancelURL($params, $component){
    $component = strtolower( $component );
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    if ($component == 'event') {
      $path = 'civicrm/event/register';
    }
    elseif ($component == 'contribute') {
      $path = 'civicrm/contribute/transact';
    }

    $query = "_qf_Confirm_display=true&qfKey={$params['qfKey']}";
    return CRM_Utils_System::url($path, $query, FALSE, NULL, FALSE);
  }

  /**
   * Build string of name value pairs for submission to payment processor
   * 
   * @params array $paymentProcessorParams
   *
   * @return string
   *   Payment processor query string
   */
  function buildPaymentProcessorString($paymentProcessorParams){
    $validParams = array();
    foreach ($paymentProcessorParams as $key => $value){
      if (!empty($value)){
        $validParams[] = $key ."=".$value;   
      }
    }
    $paymentProcessorString = implode('&',$validParams);

    return $paymentProcessorString;
  }

  /*
   * map the name / value set required by the payment processor
   * @param array $params
   * @return array $processorParams array reflecting parameters required for payment processor
   */
  function mapParamstoPaymentProcessorFields($params, $component) {

    $partner = (empty($this->_paymentProcessor['signature'] )) ? 'PAYPAL' : $this->_paymentProcessor['signature'];

    $processorParams = array(
      'TYPE'        => 'S',
      'ADDRESS'     => $this->URLencodetoMaximumLength($params['street_address'], 60),
      'CITY'        => $this->URLencodetoMaximumLength($params['city'], 32),
      'LOGIN'       => $this->_paymentProcessor['user_name'],
      'PARTNER'     => $partner,
      'AMOUNT'      => $params['amount'],
      'ZIP'         => $this->URLencodetoMaximumLength($params['postal_code'], 10),
      'COUNTRY'     => $params['country'],
      // ref not returned to Civi but visible in paypal
      'COMMENT1'    => 'civicrm contact ID ' . $params['contactID'],
      // ref not returned to Civi but visible in
      'COMMENT2'    => 'contribution id ' . $params['contributionID'], paypal
      // 11 max
      'CUSTID'      => $params['contributionIDinvoiceID'],
      'DESCRIPTION' => $this->URLencodetoMaximumLength($params['description'], 255),
      'EMAIL'       => $params['email'],
      // 9 max
      'INVOICE'     => $params['contributionID'],
      'NAME'        => $this->URLencodetoMaximumLength($params['display_name'], 60),
      'STATE'       => $params['state_province'],
      // USER fields are returned to Civi silent POST. Add them all in here for debug help.
      'USER1'       => $params['contactID'],
      'USER2'       => $params['invoiceID'], 
      'USER3'       => CRM_Utils_Array::value('participantID',$params),      
      'USER4'       => CRM_Utils_Array::value('eventID',$params), 
      'USER5'       => CRM_Utils_Array::value( 'membershipID', $params ),    
      'USER6'       => CRM_Utils_Array::value( 'pledgeID', $params ), 
      'USER7'       => $component . ", " .  $params['qfKey'],
      'USER8'       => CRM_Utils_Array::value('contributionPageID',$params), 
      'USER9'       => CRM_Utils_Array::value('related_contact',$params),
      'USER10'      => CRM_Utils_Array::value( 'onbehalf_dupe_alert', $params ),                
    );
    return $processorParams;
  }
}
