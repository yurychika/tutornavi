<?php defined('SYSPATH') || die('No direct script access allowed.');

if ( !is_file(DOCPATH . 'config.php') ) {
	die('System does not appear to be installed. Run <a href="install">installation wizard</a> to install it now.');
}

include DOCPATH . 'config.php';

if ( !defined('INSTALLATION') && ( !isset($config) || !is_array($config) || !$config ) )
{
	die('System does not appear to be installed. Run <a href="install">installation wizard</a> to install it now.');
}

$config['charset'] = 'UTF-8';

$config['language'] = 'english';

$config['permitted_uri_chars'] = 'a-z 0-9~%.,:_\-';

$config['folder_chmod'] = '0777';
$config['file_chmod'] = '0644';

$config['cache_driver'] = 'file';
$config['cache_path'] = '';

$config['session_driver'] = 'native';

$config['csrf_protection'] = false;
$config['csrf_name'] = 'csrf_token';

$config['time_reference'] = 'gmt';

$config['cookie_prefix'] = 'cb_';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_salt'] = 'kzmfi82nz';

switch ( defined('ENVIRONMENT') ? ENVIRONMENT : 'production' )
{
	case 'development':

		$config['error_show'] = true;
		$config['error_log'] = true;

		break;

	case 'production':
	default:

		$config['error_show'] = false;
		$config['error_log'] = false;
}
