<?php

class CRM_Postfinance_IPN {

  protected $info;
  protected $shaOut;
  protected $crm;

  function __construct($info, $shaOut) {
    $this->info = $info;
    $this->shaOut = $shaOut;

    require_once 'api/class.api.php';
    $this->crm = new civicrm_api3();
  }

  /**
   * @param array $params
   *   The POST params sent with the request
   */
  function handleIPN($params) {

    // Just to see how the params can look like.
    $example_params = array(
      'orderID' => 65,
      'currency' => 'CHF',
      'amount' => 555,
      'PM' => 'PostFinance e-finance',
      'ACCEPTANCE' => 'TEST',
      'STATUS' => 5,
      'CARDNO' => '',
      'ED' => '',
      'CN' => '',
      'TRXDATE' => '03/20/13',
      'PAYID' => '20079496',
      'NCERROR' => '0',
      'BRAND' => 'PostFinance e-finance',
      'IP' => '78.49.196.121',
      'SHASIGN' => '0DBDC22BB1DBDAC5CE051968A5C792CDB513C325',
    );

    $sha = $this->shaOut->makeSignature($params);
    if (strtoupper($sha) !== $params['SHASIGN']) {
      $print_r_params = var_export($params, TRUE);
      watchdog('civicrm.postfinance', "IPN SHASIGN FAIL. orderID=$params[orderID]. status=$params[STATUS]. '$sha' !== '$params[SHASIGN]' params: $print_r_params.");
      return 'SHASIGN FAIL: ' . $sha;
    }

    if (0
      // "Authorized"
      || $params['STATUS'] == 5
      // "Payment requested"
      || $params['STATUS'] == 9
    ) {
      // Accept
      $status_id = 1;
      $status_name = 'REJECT';
    }
    else {
      // Reject
      $status_id = 2;
      $status_name = 'REJECT';
    }

    $api_result = civicrm_api('contribution', 'create', $api_params = array(
      'version' => 3,
      'id' => $params['orderID'],
      'contribution_status_id' => $status_id,
      // 'cancel_reason' => print_r($params, TRUE),
    ));

    if (!empty($api_result['is_error'])) {
      $print_r_api_result = var_export($api_result, TRUE);
      watchdog('civicrm.postfinance', "IPN $status_name. orderID=$params[orderID]. status=$params[STATUS].  API result: $print_r_api_result.");
      $print_r_api_params = var_export($api_params, TRUE);
      watchdog('civicrm.postfinance', "API params: $print_r_api_params.");
    }
    else {
      $print_r_api_result = var_export($api_result, TRUE);
      watchdog('civicrm.postfinance', "IPN $status_name. orderID=$params[orderID]. status=$params[STATUS]. API result: $print_r_api_result");
    }

    // TODO: Return something smarter.
    return 'SUCCESS';
  }
}
