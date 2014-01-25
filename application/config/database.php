<?php defined('SYSPATH') || die('No direct script access allowed.');

if ( !is_file(DOCPATH . 'config.php') ) {
	die('"' . DOCPATH . 'config.php" file does not exist.');
}

include DOCPATH . 'config.php';

if ( !defined('INSTALLATION') && ( !isset($database) || !is_array($database) || !$database ) )
{
	die('Missing database configuration. System does not appear to be properly installed.');
}
elseif ( !defined('INSTALLATION') )
{
	$config['default'] = $database;
}

$config['active'] = 'default';
$config['default']['port'] = '';

// Set database error reporting
switch ( defined('ENVIRONMENT') ? ENVIRONMENT : 'production' )
{
	case 'development':

		// Display all errors
		$config['default']['debug'] = true;
		$config['default']['strict'] = true;

		break;

	case 'production':
	default:

		// Do not display any errors
		$config['default']['debug'] = false;
		$config['default']['strict'] = false;
}

$config['default']['autoinit'] = true;

$config['default']['charset'] = 'utf8';
$config['default']['collation'] = 'utf8_unicode_ci';

$config['default']['cache'] = false;
$config['default']['cache_path'] = '';
