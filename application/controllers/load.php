<?php

class Load_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('file');
	}

	public function index()
	{
	}

	public function javascript()
	{
		$output = $this->getJavascripts(strtolower(uri::segment(3)) == 'cp' ? true : false);

		codebreeder::setHeader('Content-Type: application/javascript');

		echo $output;
		exit;
	}

	protected function getJavascripts($cp = false)
	{
		// Do we have javascript cached?
		if ( ( $output = $this->cache->item('asset_js_' . ( $cp ? 'cp' : 'fe' ) . '_' . session::item('language')) ) === false )
		{
			// Load required javascripts
			$output = file_get_contents(BASEPATH . 'externals/headjs/head.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/jquery/jquery.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/colorbox/colorbox.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/dropdown/dropdown.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/tabs/tabs.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/tipsy/tipsy.min.js');
			$output .= file_get_contents(BASEPATH . 'externals/rating/rating.min.js');
			// $output .= file_get_contents(BASEPATH . 'assets/js/scripts.js');
			

			// Search paths
			$paths = array(BASEPATH . 'assets/js/shared', BASEPATH . 'assets/js' . ( $cp ? '/cp' : '' ));

			// Scan for .js files
			foreach ( $paths as $path )
			{
				$files = file_helper::scanFileNames($path);

				foreach ( $files as $file )
				{
					// Do we have a valid file?
					if ( is_file($path . '/' . $file) && strtolower(substr($file, -7)) != '.min.js' && strtolower(substr($file, -3)) == '.js' )
					{
						// Do we have a minified version of the file?
						if ( is_file($path . '/' . strtolower(substr($file, 0, -3)) . '.min.js') )
						{
							$file = strtolower(substr($file, 0, -3)) . '.min.js';
						}

						// Read css file
						$output .= file_get_contents($path . '/' . $file) . "\n";
					}
				}
			}

			// Parse javascript file
			$output = $this->parseJavascript($output);

			// Cache css output
			$this->cache->set('asset_js_' . ( $cp ? 'cp' : 'fe' ) . '_' . session::item('language'), $output, 60*60*24*30);
		}

		return $output;
	}

	protected function parseJavascript($str)
	{
		// Replace configuration tags
		$str = $this->replaceSettings($str);

		// Replace language tags
		$str = $this->replaceLanguage($str);

		return $str;
	}

	public function css()
	{
		// echo 123;
		// exit;
		$template = strtolower(uri::segment(3));

		if ( $template != 'cp' && !in_array($template, config::item('templates', 'core', 'keywords')) )
		{
			error::show404();
		}

		$output = $this->getStylesheets($template, ( $template == 'cp' ? true : false ));

		codebreeder::setHeader('Content-Type: text/css');

		echo $output;
		exit;
	}

	protected function getStylesheets($template, $cp = false)
	{
		// Do we have css cached?
		if ( ( $output = $this->cache->item('asset_css_' . ( $cp ? 'cp' : 'fe' ) . '_' . session::item('language') . '_' . $template) ) === false )
		{
			$output = $this->readStylesheet(BASEPATH . 'assets/css/cp/system', 'reset.css');
			$output .= $this->readStylesheet(BASEPATH . 'externals/colorbox', 'style.css');
			$output .= $this->readStylesheet(BASEPATH . 'externals/tipsy', 'style.css');

			// Scan plugins
			foreach ( config::item('plugins', 'core') as $plugin )
			{
				if ( $plugin['keyword'] != 'system' )
				{
					$path = BASEPATH . 'assets/css/' . ( $cp ? 'cp/' : '' ) . $plugin['keyword'];

					if ( is_dir($path) )
					{
						$files = file_helper::scanFileNames($path);

						// Scan css files
						foreach ( $files as $file )
						{
							// Do we have a valid css file?
							if ( strtolower(substr($file, -4)) == '.css' && file_exists($path . '/' . $file) )
							{
								// Read css file
								$output .= $this->readStylesheet($path, $file);
							}
						}
					}
				}
			}

			// Is this a control panel css?
			if ( $cp )
			{
				$output .= $this->readStylesheet(BASEPATH . 'assets/css/cp/system', 'style.css');
			}
			// This is a front end
			else
			{
				// $output .= $this->readStylesheet(BASEPATH . 'templates/' . $template . '/css', 'style.css');
			}

			// Cache css output
			$this->cache->set('asset_css_' . ( $cp ? 'cp' : 'fe' ) . '_' . session::item('language') . '_' . $template, $output, 60*60*24*30);
		}

		return $output;
	}

	protected function readStylesheet($path, $file, $level = 1)
	{
		// Trim paths
		$path = rtrim($path, '/');
		$file = ltrim($file, '/');

		// Can we read css file?
		if ( ($content = @file_get_contents($path . '/' . $file)) === false )
		{
			error::show('Could not read style sheet file: ' . $path . '/' . $file);
		}

		// Parse css file
		$output = $this->parseStylesheet($content);

		// Find all @import tags
		preg_match_all('/@import url\(["\']?([^"\']+)["\']?\);/i', $output, $imports);

		// Do we have any @import tags?
		if ( isset($imports[1]) && $imports[1] && $level <= 5 )
		{
			// Loop through @import tags
			foreach ( $imports[1] as $index => $import )
			{
				// Read imported css file
				$content = $this->readStylesheet($path, $import, ( $level + 1 ));

				// Replace @import tag with css output
				$output = str_replace($imports[0][$index], $content, $output);
			}
		}

		return trim($output);
	}

	protected function parseStylesheet($str)
	{
		// Replace basic tags
		$str = str_replace('[base_url]', config::item('index_page') ? '../../../' : '../../', $str);
		$str = str_replace('[site_url]', config::item('index_page') ? '../../../' . config::item('index_page') . '/' : '../../', $str);
		$str = str_replace('[template]', session::item('template'), $str);
		$str = str_replace('[language]', session::item('language'), $str);

		// Replace configuration tags
		$str = $this->replaceSettings($str);

		return $str;
	}

	protected function replaceSettings($str)
	{
		preg_match_all('/\[conf\.([a-z0-9\_]+)\.([a-z0-9\_]+)\]/i', $str, $matches);

		if ( isset($matches[0]) && $matches[0] )
		{
			foreach ( $matches[0] as $index => $key )
			{
				$str = str_replace($key, config::item($matches[2][$index], $matches[1][$index]), $str);
			}

		}

		return $str;
	}

	protected function replaceLanguage($str)
	{
		preg_match_all('/\[lang\.([a-z\_]+)\.([a-z\_]+)\]/i', $str, $matches);

		if ( isset($matches[0]) && $matches[0] )
		{
			foreach ( $matches[0] as $index => $key )
			{
				$str = str_replace($key, __($matches[2][$index], $matches[1][$index]), $str);
			}

		}

		return $str;
	}
}
