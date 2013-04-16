<?php

// Make the autoloader work.
$extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
set_include_path($extRoot . PATH_SEPARATOR . get_include_path());

class eu_tttp_postfinance extends CRM_Postfinance_Payment {}




/**
 * Implements hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function postfinance_civicrm_xmlMenu(&$files) {
  dpm($files);
  $files[] = __DIR__ . '/menu.xml';
}
