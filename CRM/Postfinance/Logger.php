<?php

class CRM_Postfinance_Logger {

  function log($message, $label) {
    if (is_object($message) || is_array($message)) {
      if (function_exists('krumo_ob')) {
        $message = krumo_ob($message);
      }
    }
    if (function_exists('watchdog')) {
      watchdog('eu.tttp.postfinance', $message, array(), WATCHDOG_INFO);
    }
  }
}
