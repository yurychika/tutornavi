<?php

class CP_System_Languages_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('languages_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/languages', 'items'));

		loader::model('system/languages');

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/languages/', __('system_languages', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get languages
		if ( !( $languages = $this->languages_model->scanLanguages() ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/languages',
			'keyword' => 'languages',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'translation' => array(
					'html' => __('language_translation_pct', 'system_languages'),
					'class' => 'translation',
				),
				'status' => array(
					'html' => __('default', 'system'),
					'class' => 'status',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $languages as $language )
		{
			$actions = $status = array();
			if ( isset($language['language_id']) && $language['language_id'] )
			{
				// Get default language data
				if ( !( $default = $this->languages_model->getLanguageData('system') ) )
				{
					view::setError(__('no_language', 'system_languages'));
					router::redirect('cp/system/languages');
				}

				// Get language data
				if ( !( $data = $this->languages_model->getLanguageData($language['keyword']) ) )
				{
					view::setError(__('no_language', 'system_languages'));
					router::redirect('cp/system/languages');
				}

				// Set language sections
				$translated = $total = 0;
				foreach ( $data as $plugin => $langs )
				{
					foreach ( $langs as $section => $groups )
					{
						foreach ( $groups as $group => $types )
						{
							foreach ( $types as $type => $items )
							{
								foreach ( $items as $index => $value )
								{
									if ( utf8::strcasecmp($default[$plugin][$section][$group][$type][$index], $value) )
									{
										$translated++;
									}
									$total++;
								}
							}
						}
					}
				}
				$translated = $translated ? round($translated / $total * 100) : 0;
				$translated = $translated > 100 ? 100 : $translated;

				$name['html'] = html_helper::anchor('cp/system/languages/plugins/' . $language['keyword'], text_helper::entities($language['name']));
				$translation['html'] = ( $language['keyword'] == 'english' ? '100' : $translated ) . '%';
				$status['html'] = $language['default'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : html_helper::anchor('cp/system/languages/setdefault/' . $language['keyword'], __('no', 'system'), array('class' => 'label important small'));
				$actions['html']['plugins'] = html_helper::anchor('cp/system/languages/plugins/' . $language['keyword'], __('language_translate', 'system_languages'), array('class' => 'translate'));
				$actions['html']['edit'] = html_helper::anchor('cp/system/languages/edit/' . $language['keyword'], __('edit', 'system'), array('class' => 'edit'));
				$actions['html']['import'] = html_helper::anchor('cp/system/languages/import/' . $language['keyword'], __('language_import', 'system_languages'), array('data-html' => __('language_import?', 'system_languages'), 'data-role' => 'confirm', 'class' => 'import'));
				$actions['html']['export'] = html_helper::anchor('cp/system/languages/export/' . $language['keyword'], __('language_export', 'system_languages'), array('data-html' => __('language_export?', 'system_languages'), 'data-role' => 'confirm', 'class' => 'export'));
				$actions['html']['uninstall'] = html_helper::anchor('cp/system/languages/uninstall/' . $language['keyword'], __('uninstall', 'system'), array('data-html' => __('language_uninstall?', 'system_languages'), 'data-role' => 'confirm', 'class' => 'uninstall'));
			}
			else
			{
				$name['html'] = text_helper::entities($language['name']);
				$translation['html'] = __('language_translation_not_installed', 'system_languages');
				$status['html'] = '<span class="label important small">' . __('no', 'system') . '</span>';
				$actions['html']['install'] = html_helper::anchor('cp/system/languages/install/' . $language['keyword'], __('install', 'system'), array('class' => 'install'));
				if ( $language['keyword'] != 'english' )
				{
					$actions['html']['delete'] = html_helper::anchor('cp/system/languages/delete/' . $language['keyword'], __('delete', 'system'), array('data-html' => __('language_delete?', 'system_languages'), 'data-role' => 'confirm', 'class' => 'delete'));
				}
			}

			$grid['content'][] = array(
				'name' => $name,
				'translation' => $translation,
				'status' => $status,
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/languages/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_languages_manage', 'system_navigation'));

		// Assign actions
		view::setAction('cp/system/languages/edit', __('language_new', 'system_languages'), array('class' => 'icon-text icon-system-languages-new'));

		// Load view
		view::load('cp/system/languages/browse');
	}

	public function edit()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		$language = array();
		if ( $keyword && !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Assign vars
		view::assign(array('keyword' => $keyword, 'language' => $language));

		// Process form values
		if ( input::post('do_save_language') )
		{
			$this->_saveLanguage($keyword ? $language['keyword'] : '');
		}

		// Set title
		view::setTitle($keyword ? __('language_edit', 'system_languages') : __('language_new', 'system_languages'));

		// Set trail
		view::setTrail('cp/system/languages/edit/' . ( $keyword ? $keyword : ''), ( $keyword ? __('language_edit', 'system_languages') . ' - ' . text_helper::entities($language['name']) : __('language_new', 'system_languages')));

		// Load view
		view::load('cp/system/languages/edit');
	}

	protected function _saveLanguage($languageID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Rules array
		$rules = array();

		// Keyword and name fields
		$rules = array(
			'name' => array(
				'label' => __('name', 'system'),
				'rules' => array('required', 'max_length' => 128)
			),
			'keyword' => array(
				'label' => __('keyword', 'system'),
				'rules' => array('required', 'max_length' => 128, 'alpha_dash', 'strtolower', 'callback__is_unique_keyword' => $languageID)
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$name = utf8::trim(input::post('name'));
		$keyword = trim(input::post('keyword'));

		// Save language pack
		if ( !$this->languages_model->saveLanguage($languageID, $name, $keyword) )
		{
			return false;
		}

		// Success
		view::setInfo(__('language_saved', 'system_languages'));

		router::redirect($languageID ? 'cp/system/languages/edit/' . $keyword : '/cp/system/languages');
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		if ( !$keyword || ( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			router::redirect('cp/system/languages');
		}

		// Is this English language pack?
		if ( $keyword == 'english' )
		{
			view::setError(__('language_english', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Uninstall language
		$this->languages_model->delete($keyword);

		view::setInfo(__('language_deleted', 'system_languages'));
		router::redirect('cp/system/languages');
	}

	public function setDefault()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		if ( !$keyword || !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Is this a default language?
		if ( !$language['default'] )
		{
			// Set default language
			$this->languages_model->setDefault($language['language_id'], $language);
		}

		router::redirect('cp/system/languages');
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get languages
		if ( !$keyword || !( $languages = $this->languages_model->scanLanguages() ) )
		{
			view::setError(__('no_languages', 'system_languages'));
			router::redirect('cp/system/config/system');
		}

		// Does language exist and is it installed?
		if ( !isset($languages[$keyword]) || isset($languages[$keyword]['language_id']) )
		{
			router::redirect('cp/system/languages');
		}

		// Install language
		$this->languages_model->install($keyword);

		view::setInfo(__('language_installed', 'system_languages'));
		router::redirect('cp/system/languages');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		if ( !$keyword || !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Is this a system language?
		if ( $language['default'] )
		{
			view::setError(__('language_default', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Save message before uninstalling
		$message = __('language_uninstalled', 'system_languages');

		// Uninstall language
		$this->languages_model->uninstall($language['language_id'], $language);

		view::setInfo($message);
		router::redirect('cp/system/languages');
	}

	public function export()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		if ( !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Export language
		$this->languages_model->export($keyword);

		// Success
		view::setInfo(__('language_exported', 'system_languages', array('%path' => DOCPATH . 'languages/' . $keyword . '/')));

		router::redirect('cp/system/languages');
	}

	public function import()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/languages') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get language
		if ( !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Import language
		$this->languages_model->import($keyword);

		// Success
		view::setInfo(__('language_imported', 'system_languages', array('%path' => DOCPATH . 'languages/' . $keyword . '/')));

		router::redirect('cp/system/languages');
	}

	public function plugins()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Is this a system language?
		if ( $keyword == 'system' )
		{
			$language = array(
				'keyword' => 'system',
				'name' => 'System',
			);
		}
		// Get language
		elseif ( !( $language = $this->languages_model->getLanguage($keyword) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/languages/plugins/' . $keyword,
			'keyword' => 'languages',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'translation' => array(
					'html' => __('language_translation_pct', 'system_languages'),
					'class' => 'translation',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( config::item('plugins', 'core') as $plugin )
		{
			// Get default language data
			if ( !( $default = $this->languages_model->getLanguageData('system', $plugin['keyword']) ) )
			{
				view::setError(__('no_language', 'system_languages'));
				router::redirect('cp/system/languages');
			}

			// Get language data
			if ( !( $data = $this->languages_model->getLanguageData($keyword, $plugin['keyword']) ) )
			{
				view::setError(__('no_language', 'system_languages'));
				router::redirect('cp/system/languages');
			}

			// Set language sections
			$translated = $total = 0;
			foreach ( $data as $section => $groups )
			{
				foreach ( $groups as $group => $types )
				{
					foreach ( $types as $type => $items )
					{
						foreach ( $items as $index => $value )
						{
							if ( utf8::strcasecmp($default[$section][$group][$type][$index], $value) )
							{
								$translated++;
							}
							$total++;
						}
					}
				}
			}
			$translated = $translated ? round($translated / $total * 100) : 0;
			$translated = $translated > 100 ? 100 : $translated;

			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/languages/translate/' . $plugin['keyword'] . '/' . $language['keyword'], text_helper::entities($plugin['name'])),
				),
				'translation' => array(
					'html' => ($language['keyword'] == 'english' ? '100' : $translated) . '%',
				),
				'actions' => array(
					'html' => html_helper::anchor('cp/system/languages/translate/' . $plugin['keyword'] . '/' . $language['keyword'], __('edit', 'system'), array('class' => 'edit')),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/languages/plugins/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('plugin_select', 'system'));

		// Set trail
		view::setTrail('cp/system/languages/plugins/' . $keyword, __('language_translate', 'system_languages').' - ' . text_helper::entities($language['name']));

		// Load view
		view::load('cp/system/languages/plugins');
	}

	public function translate()
	{
		// Get URI vars
		$plugin = uri::segment(5);
		$language = uri::segment(6);

		// Get plugin
		if ( !config::item('plugins', 'core', $plugin) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/languages/plugins/' . $language);
		}

		// Is this a system language?
		if ( $language == 'system' )
		{
			$language = array(
				'keyword' => 'system',
				'name' => 'System',
			);
		}
		// Get language
		elseif ( !( $language = $this->languages_model->getLanguage($language) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Get default language data
		if ( !( $default = $this->languages_model->getLanguageData('system', $plugin) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Get language data
		if ( !( $data = $this->languages_model->getLanguageData($language['keyword'], $plugin, true) ) )
		{
			view::setError(__('no_language', 'system_languages'));
			router::redirect('cp/system/languages');
		}

		// Set language sections
		$sections = array();
		foreach ( $data as $section => $groups )
		{
			foreach ( $groups as $group => $types )
			{
				$translated = $total = 0;
				foreach ( $types as $type => $items )
				{
					foreach ( $items as $index => $value )
					{
						if ( utf8::strcasecmp($default[$section][$group][$type][$index], $value) )
						{
							$translated++;
						}
						$total++;
					}
				}
				$translated = $translated ? round($translated / $total * 100) : 0;
				$translated = $translated > 100 ? 100 : $translated;

				if ( __('language_' . $group, $plugin . '_config') !== false )
				{
					$name = __('language_' . $group, $plugin . '_config');
				}
				elseif ( __('language_' . $group, 'users_config') !== false )
				{
					$name = __('language_' . $group, 'users_config');
				}
				elseif ( __('language_' . $group, 'system_config') !== false )
				{
					$name = __('language_' . $group, 'system_config');
				}
				elseif ( $section == $group )
				{
					$name = __('language_system', 'system_config');
				}
				else
				{
					$name = '!' . $group;
				}
				$sections[$section . '_' . $group] = '[' . config::item('plugins', 'core', $section, 'name') . '] ' . $name . ( $language['keyword'] != 'english' ? ' - ' . $translated . '%' : '') . ( config::item('devmode', 'system') == 2 ? ' [' . $group . ']' : '' );
			}
		}
		asort($sections);

		// Assign vars
		view::assign(array('plugin' => $plugin, 'default' => $default, 'sections' => $sections, 'language' => $data));

		// Process form values
		if ( input::post('do_save_language') )
		{
			$this->_saveLanguageData($plugin, $language['keyword'], $default);
		}

		// Set title
		view::setTitle(__('language_translate', 'system_languages'));

		// Set trail
		view::setTrail('cp/system/languages/plugins/' . $language['keyword'], __('language_translate', 'system_languages') . ' - ' . text_helper::entities($language['name']));
		view::setTrail('cp/system/languages/translate/' . $plugin . '/' . $language['keyword'], text_helper::entities(config::item('plugins', 'core', $plugin, 'name')));

		// Load view
		view::load('cp/system/languages/translate');
	}

	protected function _saveLanguageData($plugin, $language, $default)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();

		foreach ( $default as $section => $groups )
		{
			foreach ( $groups as $group => $types )
			{
				foreach ( $types as $type => $lang )
				{
					foreach ( $lang as $keyword => $name )
					{
						$rules[$group . '_' . $keyword] = array(
							'label' => '',
							'rules' => array('trim', 'required'),
						);
					}
				}
			}
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get language data
		$languageData = array();
		foreach ( $default as $section => $groups )
		{
			foreach ( $groups as $group => $types )
			{
				foreach ( $types as $type => $lang )
				{
					foreach ( $lang as $keyword => $name )
					{
						$cp = $type == 'cp' ? 1 : 0;

						// Set language data
						$data = array(
							'value_' . $language => input::post($group . '_' . $keyword),
						);

						// Save language string
						$this->languages_model->saveLanguageData($plugin, $section, $group, $keyword, $data);
					}
				}
			}
		}

		// Recompile language pack
		$this->languages_model->compile($language);

		// Success
		view::setInfo(__('language_saved', 'system_languages'));

		router::redirect('cp/system/languages/translate/' . $plugin . '/' . $language);
	}

	public function _is_unique_keyword($keyword, $language)
	{
		// Get languages
		$languages = $this->languages_model->scanLanguages();

		// Check if keyword already exists
		if ( $keyword != $language && isset($languages[$keyword]) )
		{
			validate::setError('_is_unique_keyword', __('duplicate_keyword', 'system_languages'));
			return false;
		}

		return true;
	}
}
