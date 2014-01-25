<?php
/*
=====================================================
PHP Setup Wizard Script - by VLD Interactive Inc.
----------------------------------------------------
http://www.phpsetupwizard.com/
http://www.vldinteractive.com/
-----------------------------------------------------
Copyright (c) 2013 VLD Interactive Inc.
=====================================================
THIS IS COPYRIGHTED SOFTWARE
PLEASE READ THE LICENSE AGREEMENT
http://www.phpsetupwizard.com/license/
=====================================================
*/

define('ENVIRONMENT', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost' ? 'development' : 'production');

@error_reporting(0);
@ini_set('display_errors', 0);
//@error_reporting(-1);
//@ini_set('display_errors', 1);

// Set time zone
if ( !ini_get('date.timezone') )
{
	ini_set('date.timezone', 'America/Toronto');
}

// Reset time limit
if ( function_exists('set_time_limit') && !@ini_get('safe_mode') )
{
	@set_time_limit(0);
}


$base_path = str_replace('\\', '/', realpath(dirname(__FILE__))).'/';
$virtual_path = str_replace('\\', '/', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])).'/';
define('BASE_PATH', $base_path);
define('VIRTUAL_PATH', $virtual_path);
define('INSTALLATION', true);

include BASE_PATH . 'includes/core/wizard.php';
include BASE_PATH . 'includes/wizard.php';

$wizard = new phpSetupWizard();

$wizard->run();
