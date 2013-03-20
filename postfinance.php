<?php

// Make the autoloader work.
$extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
set_include_path($extRoot . PATH_SEPARATOR . get_include_path());

class eu_tttp_postfinance extends CRM_Postfinance_Payment {}

require_once 'postfinance.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function postfinance_civicrm_config(&$config) {
  // _postfinance_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function postfinance_civicrm_xmlMenu(&$files) {
  // _postfinance_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function postfinance_civicrm_install() {
  // return _postfinance_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function postfinance_civicrm_uninstall() {
  // return _postfinance_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function postfinance_civicrm_enable() {
  // return _postfinance_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function postfinance_civicrm_disable() {
  // return _postfinance_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function postfinance_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  // return _postfinance_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function postfinance_civicrm_managed(&$entities) {
  // return _postfinance_civix_civicrm_managed($entities);
}
