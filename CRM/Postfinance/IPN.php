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
   * @param string $component
   *   Typically either 'contribute' or 'event'.
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
      return 'SHASIGN FAIL: ' . $sha;
    }

    if (0
      // "Authorized"
      || $params['STATUS'] == 5
      // "Payment requested"
      || $params['STATUS'] == 9
    ) {
      // Accept
      $this->setContributionStatusId($params['orderID'], 1);
    }
    else {
      // Reject
      $this->setContributionStatusId($params['orderID'], 2);
    }

    // TODO: Return something smarter.
    return 'SUCCESS';
  }

  /**
   * Mark a contribution as saved or not saved.
   */
  protected function setContributionStatusId($contribution_id, $status_id) {
    $api_result = civicrm_api('contribution', 'create', array(
      'version' => 3,
      'contribution_id' => $contribution_id,
      'contribution_status_id' => $status_id,
    ));
  }
}
