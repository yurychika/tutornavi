<?php
/**
* CodeBreeder Framework
*
* @package		CodeBreeder
* @author		VLD Interactive Inc.
* @copyright	(c) 2013 VLD Interactive Inc.
* @license		http://www.codebreeder.com/license
* @link			http://www.codebreeder.com
*/

// Set current environment
define('ENVIRONMENT', 'production');

// Set PHP error reporting level
switch ( ENVIRONMENT )
{
	case 'development':

		// Force PHP to report every error
		@error_reporting(-1);
		@ini_set('display_errors', 1);

		break;

	case 'production':
	default:

		// Do not display any errors
		@error_reporting(0);
		@ini_set('display_errors', 0);
}

// Reset time limit
if ( function_exists('set_time_limit') && !@ini_get('safe_mode') )
{
	@set_time_limit(0);
}

// Path to CodeBreeder system directory
$system = 'system';

// Path to your application directory
$application = 'application';

// Default extension of the resource files
$extension = '.php';

// Full path to this file
$basepath = str_replace('\\', '/', realpath(dirname(__FILE__))) . '/';

// Full path to the system folder
$system = $basepath . $system . '/';

// Full path to the application folder
$application = $basepath . $application . '/';

// Define absolute paths for the configured directories
define('BASEPATH', $basepath);
define('SYSPATH', $system);
define('DOCPATH', $application);
define('EXT', $extension);
define('DEMO', false);
define('LOG', false);

// Clean up configuration variables
unset($basepath, $system, $application, $extension);

// Load the base functions
require SYSPATH . 'base' . EXT;

// Load CodeBreeder core class
require SYSPATH . 'core/codebreeder/codebreeder' . EXT;

// Is CodeBreeder core extended by application?
if ( is_file(DOCPATH . 'core/codebreeder' . EXT) )
{
	// Load CodeBreeder extended core class
	require DOCPATH . 'core/codebreeder' . EXT;
}
else
{
	// Load empty core extension
	require SYSPATH . 'core/codebreeder' . EXT;
}

// Custom configuration
$custom = array(
	'autoload' => array(),
	'config' => array(),
	'database' => array(),
	'routes' => array(),
);

// Start the magic
CodeBreeder::initialize($custom);
