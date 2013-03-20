<?php

class CRM_Postfinance_Util {

  /**
   * Cuts off characters at the end of a string, so that after rawurlencode()
   * the string does not exceed the $maxlength parameter. This is to make sure
   * that we don't slice into escape sequences such as '%20'.
   *
   * @params string $str
   *   string to be encoded
   * @params int $maxlength
   *   maximum length of encoded field
   * @return string
   *   clipped string (but not encoded yet)
   */
  static function clipEncodedLength($str, $maxlength = 255) {
    $cliplength = $maxlength;
    if ('' === $str) {
      // substr('', 0, ..) would return FALSE instead of ''.
      // So we handle this as a special case.
      return '';
    }
    while (TRUE) {
      $clipped = substr($str, 0, $cliplength);
      $enc = rawurlencode($clipped);
      $length = strlen($enc);
      if ($length <= $maxlength) {
        // All fine.
        return $clipped;
      }
      --$cliplength;
    }
  }

  /**
   * Build the url of the page to display after the payment (thank you page)
   *
   * @params array $paymentProcessorParams
   *
   * @return string
   *   Payment processor query string
   */
  static function returnUrl($component,$params) {
    if ( $component == "event" ) {
      return CRM_Utils_System::url( 'civicrm/event/register',
          "_qf_ThankYou_display=1&qfKey={$params['qfKey']}", 
          true, null, false );
    } elseif ( $component == "contribute" ) {
      return CRM_Utils_System::url( 'civicrm/contribute/transact',
          "_qf_ThankYou_display=1&qfKey={$params['qfKey']}",
          true, null, false );
    }
    CRM_Core_Error::debug_log_message("Could not get component name from request url");
  }

  /**
   * Build string of name value pairs for url.
   *
   * @params array $paymentProcessorParams
   *
   * @return string
   *   Payment processor query string
   */
  static function urlQueryString(array $urlQueryParams) {
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
}
