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
}
