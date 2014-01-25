<?php

class CP_System_Fields_System_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->fieldsdb_model = loader::model('system/fieldsdb', array(), null);
	}

	protected function browseFields($plugin, $table, $categoryID = 0)
	{
		// Did we submit the form?
		if ( input::post('action') == 'reorder' && input::post('ids') )
		{
			$this->_reorderFields($plugin, $table, $categoryID);
		}

		// Get fields
		if ( !( $fields = $this->fields_model->getFields($plugin, $categoryID, 'grid') ) )
		{
			view::setInfo(__('no_fields', 'system_fields'));
		}

		// Field types
		$types = $this->fieldsdb_model->getTypes(true);

		// Create table grid
		$grid = array(
			'uri' => 'cp/usersgroups/browse',
			'keyword' => 'usersgroups',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'keyword' => array(
					'html' => __('keyword', 'system'),
					'class' => 'keyword',
				),
				'type' => array(
					'html' => __('field_type', 'system_fields'),
					'class' => 'type',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $fields as $field )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/fields/' . $plugin . '/edit/' . $field['category_id'] . '/' . $field['field_id'], text_helper::entities($field['name'])),
					'class' => $field['type'],
				),
				'keyword' => array(
					'html' => $field['keyword'],
				),
				'type' => array(
					'html' => '<span>' . $types[$field['type']] . '</span>',
					'class' => $field['type'],
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/system/fields/' . $plugin . '/edit/' . $field['category_id'] . '/' . $field['field_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/system/fields/' . $plugin . '/delete/' . $field['category_id'] . '/' . $field['field_id'], __('delete', 'system'), array('data-html' => __('delete_field?', 'system_fields'), 'data-role' => 'confirm', 'class' => 'delete')),
					)
				),
			);
		}

		// Filter hooks
		hook::filter('cp/usersgroups/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Assign vars
		view::assign(array('plugin' => $plugin, 'categoryID' => $categoryID, 'fields' => $fields, 'types' => $types));

		// Assign actions
		view::setAction('cp/system/fields/' . $plugin . '/edit' . ( $categoryID ? '/' . $categoryID : '' ), __('new_field', 'system_fields'), array('class' => 'icon-text icon-system-fields-new'));
		view::setAction('#', __('done', 'system'), array('class' => 'icon-text icon-system-done', 'onclick' => 'saveSortable();return false;', 'id' =>'actions_link_save'));
		view::setAction('#', __('cancel', 'system'), array('class' => 'icon-text icon-system-cancel', 'onclick' => 'cancelSortable();return false;', 'id' => 'actions_link_cancel'));
		view::setAction('#', __('reorder', 'system'), array('class' => 'icon-text icon-system-sort', 'onclick' => 'switchSortable();return false;', 'id' => 'actions_link_reorder'));

		// Include sortable vendor files
		view::includeJavascript('externals/html5sortable/html5sortable.js');
		view::includeStylesheet('externals/html5sortable/style.css');

		// Load view
		if ( input::isAjaxRequest() )
		{
			view::load('cp/system/fields/browse_' . ( input::post('view') == 'list' ? 'list' :'grid' ));
		}
		else
		{
			view::load('cp/system/fields/browse');
		}
	}

	protected function _reorderFields($plugin, $table, $categoryID)
	{
		// Check if demo mode is enabled
		if ( input::demo(0) ) return false;

		// Get submitted field IDs
		$fields = input::post('ids');

		// Do we have any field IDs?
		if ( $fields && is_array($fields) )
		{
			// Loop through field IDs
			$orderID = 1;
			foreach ( $fields as $fieldID )
			{
				// Update field ID
				$this->fieldsdb_model->saveFieldOrderID($fieldID, $orderID);
				$orderID++;
			}
		}
	}

	protected function editField($plugin, $table, $categoryID, $fieldID, $config = array(), $hidden = array())
	{
		// Get field
		$field = array();
		if ( $fieldID && !( $field = $this->fields_model->getField($fieldID) ) )
		{
			view::setError(__('no_field', 'system_fields'));
			router::redirect('cp/system/fields/' . $plugin . '/browse' . ( $categoryID ? '/' . $categoryID : '' ));
		}

		// Field types
		$types = $this->fieldsdb_model->getTypes(false, isset($hidden['system_types']) && is_array($hidden['system_types']) && $hidden['system_types'] ? $hidden['system_types'] : array());

		// Field properties
		$properties = $this->fieldsdb_model->getFieldProperties();
		$properties['custom'] = $config;

		// Get total and max items
		if ( input::post('do_save_field') )
		{
			//$totalItems = input::post('items') ? count(current(input::post('items'))) : 0;
			$lastItemID = input::post('items') ? max(array_keys(current(input::post('items')))) : 0;
		}
		else
		{
			//$totalItems = isset($field['items']) && $field['items'] ? count($field['items']) : 0;
			$lastItemID = isset($field['items']) && $field['items'] ? max(array_keys($field['items'])) : 0;
		}

		// Assign vars
		view::assign(array(
			'fieldID' => $fieldID,
			'categoryID' => $categoryID,
			'field' => $field,
			'hidden' => $hidden,
			'types' => $types,
			'properties' => $properties,
			'lastItemID' => $lastItemID
		));

		// Process form values
		if ( input::post('do_save_field') )
		{
			$this->_saveField($plugin, $table, $categoryID, $fieldID, $field, $properties, $hidden);
		}

		// Set trail
		view::setTrail('cp/system/fields/' . $plugin . '/edit/' . $categoryID . '/' . ($fieldID ? $fieldID : ''), ( $fieldID ? __('edit_field', 'system_fields') . ' - ' . text_helper::entities($field['name_' . session::item('language')]) : __('new_field', 'system_fields') ));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Include sortable vendor files
		view::includeJavascript('externals/html5sortable/html5sortable.js');
		view::includeStylesheet('externals/html5sortable/style.css');

		// Load view
		view::load('cp/system/fields/edit');
	}

	protected function _saveField($plugin, $table, $categoryID, $fieldID, $fieldOld, $configs, $hidden)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Rules array
		$rules = array();

		// Data array
		$inputData = array('keyword', 'type', 'style', 'class', 'required', 'system', 'multilang');

		// Name
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $lang )
		{
			$rules['name_' . $lang] = array(
				'label' => __('name', 'system_fields') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 255)
			);
			$rules['vname_' . $lang] = array(
				'label' => __('name_view', 'system_fields') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'max_length' => 255)
			);
			$rules['sname_' . $lang] = array(
				'label' => __('name_search', 'system_fields') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'max_length' => 255)
			);
			$rules['validate_error_' . $lang] = array(
				'label' => __('validate_error', 'system_fields') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'max_length' => 255)
			);
			$inputData[] = 'name_' . $lang;
			$inputData[] = 'vname_' . $lang;
			$inputData[] = 'sname_' . $lang;
			$inputData[] = 'validate_error_' . $lang;
		}

		// Keyword
		$rules['keyword'] = array(
			'label' => __('keyword', 'system'),
			'rules' => array('trim', 'required', 'alpha_dash', 'max_length' => 128,
				'callback__is_unique_keyword' => array($plugin, $categoryID, $fieldID),
				'callback__is_system_field' => array(( $fieldID ? $fieldOld['keyword'] : '' ), ( $fieldID ? $fieldOld['system'] : '' )))
		);

		// Type
		$rules['type'] = array(
			'label' => __('field_type', 'system_fields'),
			'rules' => array('required', 'callback__is_system_field' => array(( $fieldID ? $fieldOld['type'] : '' ), ( $fieldID ? $fieldOld['system'] : '' )))
		);

		// Style value
		$rules['style'] = array(
			'label' => __('style', 'system_fields'),
			'rules' => array('trim')
		);

		// Class value
		$rules['class'] = array(
			'label' => __('class', 'system_fields'),
			'rules' => array('trim')
		);

		// Required
		$rules['required'] = array(
			'label' => __('required', 'system_fields'),
			'rules' => array('intval')
		);

		// Regular expression
		$rules['validate'] = array(
			'label' => __('validate', 'system_fields'),
			'rules' => array('trim')
		);
		$inputData[] = 'validate';

		// Configuration array
		$inputConfig = array();

		foreach ( array('custom', input::post('type')) as $conf )
		{
			if ( isset($configs[$conf]) )
			{
				foreach ( $configs[$conf] as $option )
				{
					$rules['config_' . $conf . '_' . $option['keyword']] = array(
						'label' => utf8::strtolower($option['label']),
						'rules' => isset($option['rules']) ? $option['rules'] : array(),
					);
					$inputConfig[$option['keyword']] = 'config_' . $conf . '_' . $option['keyword'];
				}
			}
		}

		// Add items rules
		$items = array();
		$oldItems = $fieldID ? $fieldOld['items'] : array();

		if ( $this->fields_model->isMultiValue(input::post('type')) )
		{
			$itemsPost = input::post('items');
			$sitemsPost = input::post('sitems');

			foreach ( config::item('languages', 'core', 'keywords') as $languageID => $lang )
			{
				$orderID = 1;
				if ( isset($itemsPost[$lang]) && is_array($itemsPost[$lang]) )
				{
					foreach ( $itemsPost[$lang] as $itemID => $itemName )
					{
						// Trim name
						$itemName = utf8::trim($itemName);

						// Assign item data
						$items[$itemID]['order_id'] = $orderID;
						$items[$itemID]['name_' . $lang] = $itemName;
						$items[$itemID]['sname_' . $lang] = $sitemsPost[$lang][$itemID];
						$orderID++;

						// Add rule
						$rules['items[' . $lang . '][' . $itemID . ']'] = array();

						if ( $itemName == '' )
						{
							validate::setRule('items', '', '');
							validate::setFieldError('items', __('empty_item', 'system_fields') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ));
						}
					}
				}
			}

			if ( !$items )
			{
				validate::setRule('items', '', '');
				validate::setFieldError('items', __('no_items', 'system_fields'));
			}

			view::assign(array('field' => array('items' => $items)));
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$fieldData = input::post($inputData);

		// Default data
		$fieldData['system'] = isset($hidden['system']) ? $hidden['system'] : 0;
		$fieldData['multilang'] = isset($hidden['multilang']) ? $hidden['multilang'] : 0;

		// Get config data
		$fieldData['config'] = array();
		foreach ( $inputConfig as $key => $val )
		{
			$fieldData['config'][$key] = input::post($val);
		}

		// Set additional config data
		$fieldData['config']['html'] = input::post('html') ? 1 : 0;
		$fieldData['config']['in_search'] = input::post('in_search') ? 1 : 0;
		$fieldData['config']['in_search_advanced'] = input::post('in_search_advanced') ? 1 : 0;
		if ( $fieldData['config']['in_search'] || $fieldData['config']['in_search_advanced'] )
		{
			$fieldData['config']['search_options'] = input::post('search_options') ? input::post('search_options') : '';
		}
		if ( input::post('type') == 'checkbox' || input::post('search_options') == 'multiple' )
		{
			$fieldData['config']['columns_number'] = input::post('columns_number') && input::post('columns_number') >= 1 && input::post('columns_number') <= 4 ? input::post('columns_number') : 1;
		}

		// Save field
		if ( !( $newFieldID = $this->fieldsdb_model->saveField($plugin, $table, $categoryID, $fieldID, $fieldData, $items) ) )
		{
			view::setError(__('db_no_alter', 'system_fields'));
			return false;
		}

		// Check if order of items have changed
		if ( $fieldID && $this->fields_model->isMultiValue(input::post('type')) && $this->fields_model->isValueColumn(input::post('type')) )
		{
			// Get old and new item IDs
			$itemsOldIDs = $itemsNewIDs = array();
			foreach ( $oldItems as $itemID => $item )
			{
				$itemsOldIDs[$itemID] = $item['order_id'];
			}
			foreach ( $items as $itemID => $item )
			{
				$itemsNewIDs[$itemID] = $item['order_id'];
			}

			// Do we have any differences?
			if ( array_diff_assoc($itemsOldIDs, $itemsNewIDs) )
			{
				// Update items IDs
				$this->fieldsdb_model->updateItemsIDs($table, $fieldData['keyword'], $itemsOldIDs, $itemsNewIDs);
			}
		}

		// Adjust table column
		$this->fieldsdb_model->adjustColumn($table, $fieldData['keyword'], $newFieldID, $fieldData);

		// Success
		view::setInfo(__('field_saved', 'system_fields'));

		router::redirect('cp/system/fields/' . $plugin . '/edit/' . $categoryID . '/' . $newFieldID);
	}

	protected function deleteField($plugin, $table, $categoryID, $fieldID)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/fields/' . $plugin . '/browse' . ( $categoryID ? '/' . $categoryID : '' )) ) return false;

		// Get field
		if ( !( $field = $this->fields_model->getField($fieldID) ) )
		{
			view::setError(__('no_field', 'system_fields'));
			router::redirect('cp/system/fields/' . $plugin . '/browse/' . $categoryID);
		}

		// Is this a system field?
		if ( $field['system'] )
		{
			view::setError(__('no_system_delete', 'system_fields'));
			router::redirect('cp/system/fields/' . $plugin . '/browse/' . $categoryID);
		}

		// Delete field
		if ( !( $this->fieldsdb_model->deleteField($plugin, $table, $fieldID, $field) ) )
		{
			view::setError(__('db_no_column_drop', 'system_fields'));
			router::redirect('cp/system/fields/' . $plugin . '/browse/' . $categoryID);
		}

		// Success
		view::setInfo(__('field_deleted', 'system_fields'));

		router::redirect('cp/system/fields/' . $plugin . '/browse' . ( $categoryID ? '/' . $categoryID : '' ));
	}

	public function _is_system_field($value, $newValue, $system)
	{
		if ( $system && strcasecmp($value, $newValue) != 0 )
		{
			validate::setError('_is_system_field', __('no_system_change', 'system_fields'));
			return false;
		}

		return true;
	}

	public function _is_unique_keyword($keyword, $plugin, $categoryID, $fieldID)
	{
		// Get fields
		$fields = $this->fields_model->getFields($plugin, $categoryID, 'edit');

		// Check that keyword is unique
		foreach ( $fields as $field )
		{
			if ( $field['keyword'] == $keyword && $field['field_id'] != $fieldID )
			{
				validate::setError('_is_unique_keyword', __('duplicate_keyword', 'system_fields'));
				return false;
			}
		}

		return true;
	}
}
