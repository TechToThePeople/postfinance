<?php

class CRM_Postfinance_CheckoutParamCollector {

  protected $info;
  protected $shaIn;

  /**
   * Constructor
   *
   * @param array $info
   *   Various information about the payment processor instance.
   *   It is called $paymentProcessor in other examples, but that's a bit stupid
   *   name for an array.
   * @param CRM_Postfinance_ShaSignatureMaker $shaIn
   *   Object that calculate the SHA-IN signature.
   */
  function __construct($info, $shaIn) {
    $this->info = $info;
    $this->shaIn = $shaIn;
  }

  /**
   * Map the name / value set required by the payment processor.
   *
   * @param array $params
   *   Name value pair of contribution data
   * @param string $component
   *   Can be either 'contribute' or 'event' or the name of another civicrm component.
   *
   * @return array
   *   Array reflecting parameters required for payment processor
   */
  function collectCheckoutParams($params, $component) {

    // Build the params for the url.
    $urlParams = $this->buildRawParams($params, $component);

    $urlParams = $this->normalizeValues($urlParams);

    // hash/sign
    $urlParams['SHASIGN'] = $this->shaIn->makeSignature($urlParams);

    return $urlParams;
  }

  /**
   * Load the contact that is doing the checkout.
   *
   * @param array $params
   *   The original params.
   *
   * @return array
   *   Data of the contact.
   */
  protected function paramsLoadContact($params) {

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

    return $contact['values'][$params['contactID']];
  }

  /**
   * Build params for postfinance url.
   *
   * @param array $params
   *   Name value pair of contribution data
   * @param string $component
   *   Can be either 'contribute' or 'event' or the name of another civicrm component.
   *
   * @return array
   *   Array reflecting parameters required for payment processor
   */
  protected function buildRawParams($params, $component) {

    // Contact has some values that we are interested in.
    $contact = $this->paramsLoadContact($params);

    $cancelURL = $this->getCancelURL($params, $component);

    // Load the config for current language.
    $config = CRM_Core_Config::singleton();

    return array(

      // postfinance merchant account name.
      'PSPID' => $this->info['user_name'],
      // order id must be unique.
      'ORDERID' => $params['contributionID'],
      'AMOUNT' => round($params['amount'] * 100),
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
  }

  /**
   * Normalize values to maximum length, and replace NULL with ''.
   *
   * @param array $values
   *   Any array of string or other elemental values.
   *
   * @return array
   *   The normalized values.
   */
  protected function normalizeValues($values) {

    foreach ($values as $k => &$v) {
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
        throw new Exception("Invalid value for \$values['$k'].");
      }
    }
    return $values;
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
   * IPN url that postfinance should call
   * to tell us that the payment is complete.
   *
   * @return string
   *   The IPN url.
   */
  protected function ipnUrl() {
    $params = $this->ipnUrlParams();
    $querystring = Util::urlQueryString($params);
    return CRM_Utils_System::url('civicrm/payment/ipn', $querystring, TRUE, NULL, FALSE, TRUE);
  }

  /**
   * url params for the IPN url that postfinance should call
   * to tell us that the payment is complete.
   *
   * @return string
   *   The IPN url.
   */
  protected function ipnUrlParams() {
    // No params for now..
    return array();
  }
}
