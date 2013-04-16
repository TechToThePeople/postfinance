<?php

// Make the autoloader work.
$extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
set_include_path($extRoot . PATH_SEPARATOR . get_include_path());

class eu_tttp_postfinance extends CRM_Postfinance_Payment {}
