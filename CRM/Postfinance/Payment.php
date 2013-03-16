<?php

class CRM_Postfinance_Payment extends CRM_Core_Payment {

  static private $_singleton = array();

  protected $legend;
  protected $shaIn;
  protected $shaOut;

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
    $this->legend = new CRM_Postfinance_Legend();
    $secret = $this->_paymentProcessor['password'];
    $keys = $this->legend->shaInParams();
    dpm($keys, '$keys');
    dpm($secret, '$secret');
    $this->shaIn = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha1');
    $secret = $this->_paymentProcessor['password'];
    $keys = $this->legend->shaOutParams();
    $this->shaOut = new CRM_Postfinance_ShaSignatureMaker($secret, $keys, 'sha1');
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
    $paymentProcessorParams = $this->mapParamsToPaymentProcessorFields($params, $component);

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $paymentProcessorParams);

    $processorURL = $this->_paymentProcessor['url_site'] . '?' . $this->urlQueryString($paymentProcessorParams);
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
  protected function urlQueryString(array $urlQueryParams) {
    $pieces = array();
    foreach ($urlQueryParams as $key => $value){
      if (!empty($value)) {
        // We know that the key is safe and doesn't need to be urlencoded.
        // Only the value needs to be encoded.
        if (!is_numeric($value)) {
          $value = rawurlencode($value);
        }
        $pieces[] = $key . '=' . $value;
      }
    }
    return implode('&', $pieces);
  }

  /**
   * Map the name / value set required by the payment processor.
   *
   * @param array $params
   *
   * @return array
   *   Array reflecting parameters required for payment processor
   */
  function mapParamsToPaymentProcessorFields($params, $component) {
    dpm($params, '$params');
    $obj = new stdClass;
    foreach ($this as $k => $v) {
      $obj->$k = $v;
    }
    dpm($obj, '$this');

    $partner = (empty($this->_paymentProcessor['signature'] )) ? 'PAYPAL' : $this->_paymentProcessor['signature'];
    $info = $this->_paymentProcessor;

    // Load the config for current language.
    $config = CRM_Core_Config::singleton();
    dpm($config, '$config');

    // Load the contact for contact-related information.
    if (empty($params['contactID'])) {
      throw new Exception('No contact id given in $params.');
    }
    $contact = civicrm_api('Contact', 'get', array(
      'version' => 3,
      'id' => $params['contactID'],
      'return' => array('display_name', 'city', 'email', 'postal_code', 'street_address'),
    ));
    if (empty($contact['values'][$params['contactID']])) {
      throw new Exception('No contact found for id=' . $params['contactID'] . '.');
    }
    $contact = $contact['values'][$params['contactID']];
    dpm($contact, '$contact');

    // Build the params for the url.
    $processorParams = array(

      // postfinance merchant account name.
      'PSPID' => $info['user_name'],
      // order id must be unique.
      'ORDERID' => $params['contributionID'],
      'AMOUNT' => $params['amount'],
      'CURRENCY' => $params['currencyID'],
      'LANGUAGE' => $config->lcMessages,

      // Optional customer details, highly recommended for fraud prevention.
      // Customer name
      'CN' => @$contact['display_name'],
      'EMAIL' => @$contact['email'],
      'OWNERZIP' => @$contact['postal_code'],
      'OWNERADDRESS' => @$contact['street_address'],
      'OWNERCTY' => @$contact['city'],
      // Leave tel no empty for privacy.
      // 'OWNERTELNO' => '',

      // Order description
      // (will be clipped to maximum length later)
      'COM' => $params['description'],

      // Look and feel of the payment page. Optional!!
      // 'TITLE' => '',
      // ...

      // Dynamic template page. Optional!!
      // 'TP' => '',

      // Payment method and payment specifics. Optional!!
      // 'PM' => '',
      // 'BRAND' => '',
      // 'WIN3DS' => '',
      // 'PMLIST' => '',
      // 'PMLISTTYPE' => '',

      // Link to your website.
      'HOMEURL' => 'NONE',
      'CATALOGURL' => 'NONE',

      // Post payment params: Redirection
      'COMPLUS' => '',
      'PARAMPLUS' => '',

      // Post payment params: Feedback
      'PARAMVAR' => '',

      // Post payment redirection
      'ACCEPTURL' => 'http://civilab.localhost/postfinance/accept',
      'DECLINEURL' => 'http://civilab.localhost/postfinance/decline',
      'EXCEPTIONURL' => 'http://civilab.localhost/postfinance/exception',
      'CANCELURL' => 'http://civilab.localhost/postfinance/cancel',

      // Optional operation field.
      'OPERATION' => '',

      // Optional extra login detail field.
      'USERID' => '',

      // Alias details
      'ALIAS' => '',
      'ALIASUSAGE' => '',
      'ALIASOPERATION' => '',
    );
    dpm($processorParams, '$processorParams before normalization');

    // Normalize processor params
    foreach ($processorParams as $k => &$v) {
      if (FALSE === $v || !isset($v)) {
        $v = '';
      }
      elseif (is_numeric($v)) {
        // Let it be
      }
      elseif (is_string($v)) {
        $v = CRM_Postfinance_Util::clipEncodedLength($v);
      }
      else {
        throw new Exception("Invalid value for \$processorParams['$k'].");
      }
    }

    // hash/sign
    $processorParams['SHASIGN'] = $this->shaIn->makeSignature($processorParams);

    dpm($processorParams, '$processorParams');
    return $processorParams;


    // Inspirational stuff from PayflowLink, to be removed when we are finished.
    $processorParams = array(

      'TYPE'        => 'S',
      'ADDRESS'     => $this->urlEncodeToMaximumLength($params['street_address'], 60),
      'CITY'        => $this->urlEncodeToMaximumLength($params['city'], 32),
      'LOGIN'       => $this->_paymentProcessor['user_name'],
      'PARTNER'     => $partner,
      'AMOUNT'      => $params['amount'],
      'ZIP'         => $this->urlEncodeToMaximumLength($params['postal_code'], 10),
      'COUNTRY'     => $params['country'],
      // ref not returned to Civi but visible in paypal
      'COMMENT1'    => 'civicrm contact ID ' . $params['contactID'],
      // ref not returned to Civi but visible in
      'COMMENT2'    => 'contribution id ' . $params['contributionID'],
      // 11 max
      'CUSTID'      => $params['contributionIDinvoiceID'],
      'DESCRIPTION' => $this->urlEncodeToMaximumLength($params['description'], 255),
      'EMAIL'       => $params['email'],
      // 9 max
      'INVOICE'     => $params['contributionID'],
      'NAME'        => $this->urlEncodeToMaximumLength($params['display_name'], 60),
      'STATE'       => $params['state_province'],
      // USER fields are returned to Civi silent POST. Add them all in here for debug help.
      'USER1'       => $params['contactID'],
      'USER2'       => $params['invoiceID'],
      'USER3'       => CRM_Utils_Array::value('participantID', $params),
      'USER4'       => CRM_Utils_Array::value('eventID', $params),
      'USER5'       => CRM_Utils_Array::value( 'membershipID', $params ),
      'USER6'       => CRM_Utils_Array::value( 'pledgeID', $params ),
      'USER7'       => $component . ", " .  $params['qfKey'],
      'USER8'       => CRM_Utils_Array::value('contributionPageID', $params),
      'USER9'       => CRM_Utils_Array::value('related_contact', $params),
      'USER10'      => CRM_Utils_Array::value( 'onbehalf_dupe_alert', $params ),
    );
    return $processorParams;
  }
}
