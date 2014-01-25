<?php

class System_Fields_Model extends Model
{
	public function getFields($plugin, $categoryID = 0, $type = 'view', $config = '', $search = false)
	{
		if ( !( $fields = $this->cache->item('db_fields_' . $plugin . '_' . session::item('language') . '_' . $categoryID . '_' . $type . '_' . $config . '_' . ( $search ? 1 : 0 )) ) && !is_array($fields) )
		{
			$fields = $fieldIDs = array();

			// Get fields
			$qfields = $this->db->query("SELECT * FROM `:prefix:core_fields` WHERE `plugin`=? AND `category_id`=? ORDER BY `order_id` ASC", array($plugin, $categoryID))->result();

			foreach ( $qfields as $field )
			{
				// Set index
				$index = $field['field_id'];

				// Decode configuration array
				$field['config'] = $field['config'] ? @json_decode($field['config'], true) : array();
				if ( !is_array($field['config']) )
				{
					$field['config'] = array();
				}

				// Is configuration item present?
				if ( $config == '' || $config == 'all' || isset($field['config'][$config]) && $field['config'][$config] )
				{
					$fields[$index] = array();

					$fields[$index]['keyword'] = $field['keyword'];
					$fields[$index]['multilang'] = $field['multilang'];
					$fields[$index]['class'] = $field['class'];
					$fields[$index]['style'] = $field['style'];
					if ( isset($field['config']['html']) && $field['config']['html'] )
					{
						$fields[$index]['html'] = 1;
					}

					// Basic field data
					if ( $type == 'query' )
					{
						if ( $this->isMultiValue($field['type']) )
						{
							$fields[$index]['items'] = array();
						}
					}
					else
					{
						if ( $search && $field['sname_' . session::item('language')] != '' )
						{
							$fields[$index]['name'] = $field['sname_' . session::item('language')];
						}
						else
						{
							$fields[$index]['name'] = $type == 'view' && $field['vname_' . session::item('language')] ? $field['vname_' . session::item('language')] : $field['name_' . session::item('language')];
						}

						$fields[$index]['type'] = $field['type'];
					}

					// Data for edit and grid/browse type of pages
					if ( $type == 'full' || $type == 'edit' || $type == 'grid' )
					{
						$fields[$index]['field_id'] = $field['field_id'];
						$fields[$index]['plugin'] = $field['plugin'];
						$fields[$index]['category_id'] = $field['category_id'];
						$fields[$index]['required'] = $field['required'];
						$fields[$index]['system'] = $field['system'];
						$fields[$index]['order_id'] = $field['order_id'];
					}

					// Data for edit type of pages
					if ( $type == 'full' || $type == 'edit' )
					{
						$fields[$index]['validate'] = $field['validate'];
						$fields[$index]['validate_error'] = $field['validate_error_' . session::item('language')];
						$fields[$index]['config'] = $field['config'];

						// Check field's type
						if ( $this->isMultiValue($field['type']) )
						{
							$fields[$index]['items'] = array();
						}
					}

					// Check field's type
					if ( $this->isMultiValue($field['type']) )
					{
						// Store field ID
						$fieldIDs[$field['field_id']] = $this->getValueFormat($field['type']);
					}
				}
			}

			// Do we have any field IDs
			if ( $fieldIDs )
			{
				// Get items
				$items = $this->db->query("SELECT * FROM `:prefix:core_fields_items` WHERE `field_id` IN (" . implode(',', array_keys($fieldIDs)) . ") ORDER BY `order_id` ASC")->result();

				// Assign items to the field
				foreach ( $items as $item )
				{
					$id = $fieldIDs[$item['field_id']] == 'multiple' ? $item['item_id'] : $item['order_id'];
					if ( $search && $item['sname_' . session::item('language')] != '' )
					{
						$fields[$item['field_id']]['items'][$id] = $item['sname_' . session::item('language')];
					}
					else
					{
						$fields[$item['field_id']]['items'][$id] = $item['name_' . session::item('language')];
					}
				}
			}

			$this->cache->set('db_fields_' . $plugin . '_' . session::item('language') . '_' . $categoryID . '_' . $type . '_' . $config . '_' . ( $search ? 1 : 0 ), $fields, 60*60*24*30);
		}

		if ( $type == 'edit' && !$search && input::isCP() )
		{
			foreach ( $fields as $field )
			{
				if ( $field['type'] == 'textarea' && isset($field['config']['html']) && $field['config']['html'] )
				{
					view::includeJavascript('externals/ckeditor/ckeditor.js');
				}
			}
		}
		elseif ( $config == 'in_view' )
		{
			$names = array();
			foreach ( $fields as $field )
			{
				$names[$field['keyword']] = $field['name'];
			}
			config::set('fields_' . $plugin, $names, 'core');
		}

		return $fields;
	}

	public function getField($fieldID)
	{
		// Get field
		$field = $this->db->query("SELECT * FROM `:prefix:core_fields` WHERE `field_id`=? ORDER BY `order_id` ASC", array($fieldID))->row();

		// Does the field exist?
		if ( $field )
		{
			// Decode configuration data
			$field['config'] = $field['config'] ? @json_decode($field['config'], true) : array();
			if ( !is_array($field['config']) )
			{
				$field['config'] = array();
			}

			// Set items index
			$field['items'] = array();

			// Get items
			$qitems = $this->db->query("SELECT * FROM `:prefix:core_fields_items` WHERE `field_id`=? ORDER BY `order_id` ASC", array($field['field_id']))->result();

			foreach ( $qitems as $item )
			{
				$field['items'][$item['item_id']] = $item;
			}
		}

		return $field;
	}

	public function getValueFormat($type)
	{
		switch ( $type )
		{
			case 'select':
			case 'radio':
			case 'number':
				$format = 'number';
				break;

			case 'price':
				$format = 'double';
				break;

			case 'birthday':
				$format = 'birthday';
				break;

			case 'location':
				$format = 'location';
				break;

			case 'country':
				$format = 'country';
				break;

			case 'checkbox':
				$format = 'multiple';
				break;

			case 'section':
				$format = 'info';
				break;

			default: $format = 'text';
		}

		return $format;
	}

	public function isValueColumn($type)
	{
		switch ( $type )
		{
			case 'checkbox':
			case 'section':
				$retval = false;
				break;

			default: $retval = true;
		}

		return $retval;
	}

	public function isMultiValue($type)
	{
		switch ( $type )
		{
			case 'select':
			case 'radio':
			case 'checkbox':
				$multiple = true;
				break;

			default: $multiple = false;
		}

		return $multiple;
	}

	public function getQueryTableColumns($table, $prefix = '')
	{
		if ( $table == 'users' )
		{
			return "`u`.*";
		}

		if ( !( $columns = $this->cache->item('db_columns_' . $table . '_' . $prefix) ) )
		{
			$columns = array();

			$result = $this->db->query("SHOW COLUMNS FROM `:prefix:" . $table . "`")->result();

			foreach ( $result as $column )
			{
				if ( strpos($column['Field'], 'data_') !== 0 )
				{
					$columns[] = '`' . ( $prefix ? $prefix : $table ) . '`.`' . $column['Field'] . '`';
				}
			}

			$columns = implode(',', $columns);

			$this->cache->set('db_columns_' . $table . '_' . $prefix, $columns, 60*60*24*30);
		}

		return $columns;
	}

	public function getQueryDataColumns($fields, $table = 'd', $multilang = false, &$joins = '')
	{
		$queryFields = '';
		$queryItems = array();
		$geoCounter = 1;

		foreach ( $fields as $fieldID => $field )
		{
			// Is this a single-value field?
			if ( $this->getValueFormat($field['type']) != 'multiple' )
			{
				if ( $this->getValueFormat($field['type']) != 'info' )
				{
					if ( $multilang && $field['multilang'] )
					{
						foreach ( config::item('languages', 'core', 'keywords') as $language )
						{
							$queryFields .= ',`' . $table . '`.`data_' . $field['keyword'] . '_' . $language . '`';
						}
					}
					else
					{
						$queryFields .= ',`' . $table . '`.`data_'.$field['keyword'] . ( $field['multilang'] ? '_' . session::item('language') : '' ) . '`' . ( $field['multilang'] ? ' AS `data_' . $field['keyword'] . '`' : '' );
					}

					if ( $this->getValueFormat($field['type']) == 'location' )
					{
						$queryFields .= ',`' . $table . '`.`data_' . $field['keyword'] . '_state`';
						$queryFields .= ',`' . $table . '`.`data_' . $field['keyword'] . '_city`';

						$queryFields .= ',`geo_s' . $geoCounter . '`.`name` AS `data_' . $field['keyword'] . '_state_name`';
						$queryFields .= ',`geo_c' . $geoCounter . '`.`name` AS `data_' . $field['keyword'] . '_city_name`';

						$joins .= ' LEFT JOIN `:prefix:geo_states` AS `geo_s' . $geoCounter . '` ON `' . $table . '`.`data_' . $field['keyword'] . '_state`=`geo_s' . $geoCounter . '`.`state_id`';
						$joins .= ' LEFT JOIN `:prefix:geo_cities` AS `geo_c' . $geoCounter . '` ON `' . $table . '`.`data_' . $field['keyword'] . '_city`=`geo_c' . $geoCounter . '`.`city_id`';

						$geoCounter++;
					}
				}
			}
			// This is a multi-value field
			else
			{
				$queryItems[$fieldID] = array(
					'keyword' => $field['keyword'],
					'items' => isset($field['items']) ? $field['items'] : array(),
				);
			}
		}

		return array($queryFields, $queryItems);
	}

	public function parseFields($fields, $data, $multilang = false, $escape = false)
	{
		// Loop through fields
		foreach ( $fields as $fieldID => $field )
		{
			// Do we have a single value field?
			if ( $this->getValueFormat($field['type']) != 'multiple' && $field['type'] != 'section' )
			{
				$keyword = 'data_'.$field['keyword'].( $multilang && $field['multilang'] ? '_'.session::item('language') : '' );
				$value = isset($data[$keyword]) ? $data[$keyword] : '';

				// Do we have multiple option field?
				if ( $this->isMultiValue($field['type']) )
				{
					if ( isset($field['items'][$value]) )
					{
						$data[$keyword] = array($value => $escape ? text_helper::entities($field['items'][$value]) : $field['items'][$value]);
					}
					else
					{
						$data[$keyword] = '';
					}
				}
				// Geo field
				elseif ( $this->getValueFormat($field['type']) == 'location' )
				{
					if ( $value )
					{
						$data[$keyword] = array($value => ( $escape && ( !isset($field['html']) || !$field['html'] ) ? text_helper::entities(geo_helper::getCountry($value)) : geo_helper::getCountry($value) ));
					}
					if ( $data['data_' . $field['keyword'] . '_state'] )
					{
						$data[$keyword . '_state'] = array($data['data_' . $field['keyword'] . '_state'] => ( $escape && ( !isset($field['html']) || !$field['html'] ) ? text_helper::entities($data['data_' . $field['keyword'] . '_state_name']) : $data['data_' . $field['keyword'] . '_state_name'] ));
					}
					if ( $data['data_' . $field['keyword'] . '_city'] )
					{
						$data[$keyword . '_city'] = array($data['data_' . $field['keyword'] . '_city'] => ( $escape && ( !isset($field['html']) || !$field['html'] ) ? text_helper::entities($data['data_' . $field['keyword'] . '_city_name']) : $data['data_' . $field['keyword'] . '_city_name'] ));
					}
				}
				// Country field
				elseif ( $this->getValueFormat($field['type']) == 'country' )
				{
					if ( $value )
					{
						$data[$keyword] = array($value => ( $escape && ( !isset($field['html']) || !$field['html'] ) ? text_helper::entities(geo_helper::getCountry($value)) : geo_helper::getCountry($value) ));
					}
				}
				// Single option field
				else
				{
					$data[$keyword] = $escape && ( !isset($field['html']) || !$field['html'] ) ? text_helper::entities($value) : $value;
				}
			}
		}

		return $data;
	}

	public function parseValues($fields, $data, $multilang = false, $escape = false)
	{
		$values = array();

		// Loop through fields
		foreach ( $fields as $fieldID => $field )
		{
			if ( $field['type'] != 'section' )
			{
				// Set keyword and value
				$keyword = 'data_'.$field['keyword'].( $multilang && $field['multilang'] ? '_'.session::item('language') : '' );
				$value = isset($data[$keyword]) ? $data[$keyword] : '';

				// Do we have a single value field?
				if ( $this->getValueFormat($field['type']) != 'multiple' )
				{
					// Do we have multiple option field?
					if ( $this->isMultiValue($field['type']) )
					{
						$values[$keyword] = $value;
					}
					// Do we have a birthday field?
					elseif ( $this->getValueFormat($field['type']) == 'birthday' )
					{
						$values[$keyword] = $value['year'].$value['month'].$value['day'];
					}
					// Do we have a location field?
					elseif ( $this->getValueFormat($field['type']) == 'location' )
					{
						$values[$keyword] = isset($value['country']) && $value['country'] ? $value['country'] : 0;
						$values[$keyword.'_state'] = isset($value['state']) && $value['state'] ? $value['state'] : 0;
						$values[$keyword.'_city'] = isset($value['city']) && $value['city'] ? $value['city'] : 0;
					}
					// Single option field
					else
					{
						$values[$keyword] = $escape ? text_helper::entities($value) : $value;
					}
				}
				// This is a multi-value field
				else
				{
					$values[$keyword] = is_array($value) ? array_combine($value, $value) : array();
				}
			}
		}

		return $values;
	}

	public function parseSearch($resource, $fields, $params = array())
	{
		// Get resource
		$resource = config::item('resources', 'core', $resource);

		$columns = $items = $values = array();

		if ( isset($fields['types']) && isset($params['type_id']) && $params['type_id'] )
		{
			foreach ( $fields['types'] as $typeField => $type )
			{
				if ( $typeField == $params['type_id'] )
				{
					list($typeColumns, $typeItems, $typeValues) = $this->parseSearch($resource['keyword'], $type, $params);
					$columns = $columns + $typeColumns;
					$items = $items + $typeItems;
					$values = $values + $typeValues;
				}
			}
		}
		else
		{
			// Loop through fields
			foreach ( $fields as $index => $field )
			{
				// Is this a data field?
				if ( isset($field['system']) )
				{
					// Get post/get value
					$keyword = 'data_' . $field['keyword'] . ( isset($field['category_id']) && $field['category_id'] ? '_' . $field['category_id'] : '' );
					$value = input::post_get($keyword);

					// Is this a checkbox?
					if ( $this->getValueFormat($field['type']) == 'multiple' )
					{
						// Do we have an array?
						if ( !is_array($value) )
						{
							$value = array($value);
						}

						// Make sure only existing item IDs are present
						$value = array_intersect($value, array_keys($field['items']));

						// Do we have any IDs?
						if ( $value )
						{
							$values[$keyword] = array_map('intval', $value);
							$items[$field['field_id']] = array_map('intval', $value);
						}
					}
					// This is a single value field
					else
					{
						// Is this a multi-value type of field?
						if ( $this->isMultiValue($field['type']) )
						{
							// Do we have a ranged search option
							if ( isset($field['config']['search_options']) && $field['config']['search_options'] == 'range' )
							{
								// Set new values
								$from = input::post_get($keyword . '__from');
								$to = input::post_get($keyword . '__to');

								// Make sure only existing item IDs are present
								if ( $from && $to && isset($field['items'][$from]) && isset($field['items'][$to]) )
								{
									// Switch values if $from is larger than $to
									if ( $from > $to )
									{
										$temp = $from;
										$from = $to;
										$to = $temp;
									}
									$values[$keyword . '__from'] = $from;
									$values[$keyword . '__to'] = $to;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "` BETWEEN " . $from . " AND " . $to;
								}
								elseif ( $from && isset($field['items'][$from]) )
								{
									$values[$keyword.'__from'] = $from;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "`>=" . $from;
								}
								elseif ( $to && isset($field['items'][$to]) )
								{
									$values[$keyword.'__to'] = $to;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "`<=" . $to;
								}
							}
							// Single value search option
							else
							{
								// Do we have an array?
								if ( !is_array($value) )
								{
									$value = array($value);
								}

								// Make sure only existing item IDs are present
								$value = array_intersect($value, array_keys($field['items']));

								// Do we have any IDs?
								if ( $value )
								{
									// Do we have a single ID?
									if ( count($value) == 1 )
									{
										$values[$keyword] = isset($field['config']['search_options']) && $field['config']['search_options'] == 'multiple' ? $value : current($value);
										$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . '`=' . current($value);
									}
									// We have multiple IDs
									else
									{
										$values[$keyword] = $value;
										$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . '` IN (' . implode(',', $value) . ')';
									}
								}
							}
						}
						// Is this a birthday field?
						elseif ( $this->getValueFormat($field['type']) == 'birthday' )
						{
							// Set new values
							$from = (int)input::post_get($keyword . '__from');
							$to = (int)input::post_get($keyword . '__to');

							// Make sure only existing item IDs are present
							if ( $from > 0 && $to > 0 )
							{
								// Switch values if $from is bigger than $to
								if ( $from > $to )
								{
									$temp = $from;
									$from = $to;
									$to = $temp;
								}

								$values[$keyword . '__from'] = $from;
								$values[$keyword . '__to'] = $to;
								$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . '` BETWEEN ' . ( date('Y') - $to - 1 ) . date('md') . ' AND ' . ( date('Y') - $from ) . date('md');
							}
							elseif ( $to > 0 )
							{
								$values[$keyword . '__to'] = $to;
								$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . '`>= ' . ( date('Y') - $to - 1 ) . date('md');
							}
							elseif ( $from > 0 )
							{
								$values[$keyword . '__from'] = $from;
								$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . '`<=' . ( date('Y') - $from ) . date('md');
							}
						}
						// Is this a location field?
						elseif ( $this->getValueFormat($field['type']) == 'location' )
						{
							// Set country, state and city values
							$location = input::post_get($keyword);
							foreach ( array('country', 'state', 'city') as $key )
							{
								if ( isset($location[$key]) && is_numeric($location[$key]) && $location[$key] > 0 )
								{
									$values[$keyword][$key] = $location[$key];
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . ( $key != 'country' ? '_' . $key : '' ) . '`=' . (int)$location[$key];
								}
							}
						}
						// This is a single-value type of field
						else
						{
							// Do we have a ranged search option
							if ( ( $this->getValueFormat($field['type']) == 'number' || $this->getValueFormat($field['type']) == 'double' ) && isset($field['config']['search_options']) && $field['config']['search_options'] == 'range' )
							{
								// Set new values
								$from = input::post_get($keyword . '__from');
								$to = input::post_get($keyword . '__to');

								// Make sure only existing item IDs are present
								if ( $from != '' && $to != '' && is_numeric($from) && is_numeric($to) )
								{
									// Switch values if $from is larger than $to
									if ( $from > $to )
									{
										$temp = $from;
										$from = $to;
										$to = $temp;
									}
									$values[$keyword . '__from'] = $from;
									$values[$keyword . '__to'] = $to;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "` BETWEEN " . $from . " AND " . $to;
								}
								elseif ( $from != '' && is_numeric($from) )
								{
									$values[$keyword.'__from'] = $from;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "`>=" . $from;
								}
								elseif ( $to != '' && is_numeric($to) )
								{
									$values[$keyword.'__to'] = $to;
									$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . "`<=" . $to;
								}
							}
							else
							{
								// Trim value
								$value = utf8::trim($value);

								// Do we have a value?
								if ( $value != '' )
								{
									$values[$keyword] = $value;

									// Is this a numeric value?
									if ( is_numeric($value) )
									{
										$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . ( isset($params['multilang']) && $params['multilang'] && $field['multilang'] ? '_' . session::item('language') : '' ) . '`=' . $value;
									}
									// This is a text value
									else
									{
										$columns[] = "`" . $resource['prefix'] . "`.`data_" . $field['keyword'] . ( isset($params['multilang']) && $params['multilang'] && $field['multilang'] ? '_' . session::item('language') : '' ) . "` LIKE '%" . trim($this->db->escapeLike($value), "'") . "%'";
									}
								}
							}
						}
					}
				}
			}
		}

		return array($columns, $items, $values);
	}

	public function validateValues($fields, $rules = array())
	{
		// Loop through fields
		foreach ( $fields as $field )
		{
			// Is this a data field?
			if ( isset($field['system']) )
			{
				// Is this a multi language field?
				if ( $field['multilang'] )
				{
					$languages = array();
					foreach ( config::item('languages', 'core', 'keywords') as $languageID => $languageKey )
					{
						$languages[] = array(
							'keyword' => '_' . $languageKey,
							'language' => config::item('languages', 'core', 'names', $languageID),
						);
					}
				}
				else
				{
					$languages = array(
						array(
							'keyword' => '',
							'language' => '',
						)
					);
				}

				// Loop through fields
				foreach ( $languages as $param )
				{
					// Is this a section divider?
					if ( $field['type'] != 'section' )
					{
						$keyword = 'data_' . $field['keyword'] . $param['keyword'];

						// Create basic rule
						$rules[$keyword] = array(
							'label' => text_helper::entities($field['name']) . ( $param['language'] ? ( count($languages) > 1 ? ' [' . $param['language'] . ']' : '' ) : '' ),
							'rules' => array()
						);

						// Required
						if ( $field['required'] )
						{
							if ( $field['type'] == 'birthday' )
							{
								foreach ( array('day', 'month', 'year') as $index )
								{
									$rules['data_' . $field['keyword'] . $param['keyword'] . '[' . $index . ']']['label'] = $field['name'];
									$rules['data_' . $field['keyword'] . $param['keyword'] . '[' . $index . ']']['rules'][] = 'required';
								}

								$value = input::post($keyword);
							}
							elseif ( $field['type'] == 'location' )
							{
								foreach ( array('country', 'state', 'city') as $index )
								{
									$rules['data_' . $field['keyword'] . $param['keyword'] . '[' . $index . ']']['label'] = $field['name'];
									$rules['data_' . $field['keyword'] . $param['keyword'] . '[' . $index . ']']['rules'][] = 'required';
								}

								$value = input::post($keyword);
							}
							else
							{
								$rules[$keyword]['rules'][] = 'required';
							}
						}

						// Field types
						if ( $this->getValueFormat($field['type']) == 'text' )
						{
							$rules[$keyword]['rules'][] = 'is_string';
						}
						elseif ( $this->getValueFormat($field['type']) == 'number' )
						{
							$rules[$keyword]['rules'][] = 'intval';
							$rules[$keyword]['rules'][] = 'is_numeric';
						}
						elseif ( $this->getValueFormat($field['type']) == 'double' )
						{
							$rules[$keyword]['rules'][] = 'is_numeric';
						}

						if ( $field['type'] == 'website' )
						{
							$rules[$keyword]['rules']['valid_url'] = array(array('http://', 'https://'));
						}

						// Min/max rules
						foreach ( array('min_length', 'max_length', 'min_value', 'max_value') as $rule )
						{
							if ( isset($field['config'][$rule]) && $field['config'][$rule] )
							{
								$rules[$keyword]['rules'][$rule] = array($field['config'][$rule]);
							}
						}

						// Validation
						if ( isset($field['validate']) && $field['validate'] )
						{
							$rules[$keyword]['rules']['regex'] = array($field['validate']);
							validate::setError('data_' . $field['keyword'] . '_regex', $field['validate_error'], $field['keyword'] . $param['keyword']);
						}
					}
				}
			}
		}

		// Set rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		return true;
	}

	public function saveValues($resource, $dataID, $dataOld, $fields, $data, $static = false, $table = '', $suffix = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		$items = array();

		// Loop through fields
		foreach ( $fields as $field )
		{
			// Is this a data field?
			if ( isset($field['system']) && $field['type'] != 'section' )
			{
				// Is this a multi language field?
				$params = array('');
				if ( $field['multilang'] )
				{
					$params = array();
					foreach ( config::item('languages', 'core', 'keywords') as $languageKey )
					{
						$params[] = '_'.$languageKey;
					}
				}

				// Loop through fields
				foreach ( $params as $param )
				{
					// Get form value
					if ( $static )
					{
						$value = isset($dataOld['data_'.$field['keyword'].$param]) ? $dataOld['data_'.$field['keyword'].$param] : false;
					}
					else
					{
						$value = input::post('data_'.$field['keyword'].$param);
					}

					// Is this a check box?
					if ( $this->getValueFormat($field['type']) == 'multiple' )
					{
						$items[$field['field_id']] = $value;
					}
					// Is this a single value integer?
					elseif ( $this->getValueFormat($field['type']) == 'number' )
					{
						$data['data_'.$field['keyword'].$param] = $value ? (int)$value : 0;
					}
					// Is this a birthday?
					elseif ( $this->getValueFormat($field['type']) == 'birthday' )
					{
						if ( $static )
						{
							$data['data_'.$field['keyword']] = $value;
						}
						else
						{
							$data['data_'.$field['keyword']] = $value['year'].$value['month'].$value['day'];
						}
					}
					// Is this a location?
					elseif ( $this->getValueFormat($field['type']) == 'location' )
					{
						if ( $static )
						{
							$data['data_'.$field['keyword']] = isset($dataOld['data_' . $field['keyword']]) && $dataOld['data_' . $field['keyword']] ? $dataOld['data_' . $field['keyword']] : 0;
							$data['data_'.$field['keyword'] . '_state'] = isset($dataOld['data_' . $field['keyword'] . '_state']) && $dataOld['data_' . $field['keyword'] . '_state'] ? $dataOld['data_' . $field['keyword'] . '_state'] : 0;
							$data['data_'.$field['keyword'] . '_city'] = isset($dataOld['data_' . $field['keyword'] . '_city']) && $dataOld['data_' . $field['keyword'] . '_city'] ? $dataOld['data_' . $field['keyword'] . '_city'] : 0;
						}
						else
						{
							$data['data_'.$field['keyword']] = isset($value['country']) && $value['country'] ? $value['country'] : 0;
							$data['data_'.$field['keyword'] . '_state'] = isset($value['state']) && $value['state'] ? $value['state'] : 0;
							$data['data_'.$field['keyword'] . '_city'] = isset($value['city']) && $value['city'] ? $value['city'] : 0;
						}
					}
					else
					{
						$data['data_'.$field['keyword'].$param] = $value;
					}
				}
			}
		}

		// Is this an existing record?
		if ( $dataID )
		{
			// Update fields
			$this->db->update($table . ( $suffix ? '_' . $suffix : '' ), $data, array($column => $dataID), 1);

			// Action hook
			hook::action('system/fields/' . $resource . '/update', $dataID, $data, $items, $fields);
		}
		else
		{
			// Insert fields
			$dataID = $this->db->insert($table . ( $suffix ? '_' . $suffix : '' ), $data);

			// Action hook
			hook::action('system/fields/' . $resource . '/insert', $dataID, $data, $items, $fields);
		}

		// Do we have any check box items?
		if ( $items )
		{
			// Delete existing items
			$this->db->query("DELETE FROM `:prefix:" . $table . "_items` WHERE `data_id`=? AND `field_id` IN (" . implode(',', array_keys($items)) . ")", array($dataID));

			// Loop through new items
			foreach ( $items as $fieldID => $item )
			{
				if ( $item && is_array($item) )
				{
					foreach ( $item as $itemID )
					{
						// Insert items
						$this->db->insert($table . '_items', array('data_id' => $dataID, 'field_id' => $fieldID, 'item_id' => $itemID));
					}
				}
			}
		}

		return $dataID;
	}

	public function deleteValues($resource, $dataID, $limit = 1, $table = '', $column = '')
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get table and column names
		$table = $table ? $table : config::item('resources', 'core', $resource, 'table');
		$column = $column ? $column : config::item('resources', 'core', $resource, 'column');

		// Delete data
		$retval = $this->db->delete($table, array($column => $dataID), $limit);
		if ( $retval )
		{
			// Delete items
			$this->db->delete($table . '_items', array('data_id' => $dataID));

			// Action hook
			hook::action('system/fields/' . $resource . '/delete', $dataID);
		}

		return $retval;
	}

	public function deleteEmptySections(&$fields, $data)
	{
		$sectionID = '';
		$sectionEmpty = true;
		foreach ( $fields as $fieldID => $field )
		{
			if ( $field['type'] != 'section' )
			{
				if ( isset($data['data_' . $field['keyword']]) && $data['data_' . $field['keyword']] != '' )
				{
					$sectionEmpty = false;
				}
				else
				{
					unset($fields[$fieldID]);
				}
			}
			elseif ( $fieldID != $sectionID )
			{
				if ( $sectionID && $sectionEmpty )
				{
					unset($fields[$sectionID]);
				}

				$sectionID = $fieldID;
				$sectionEmpty = true;
			}
		}
		if ( $sectionID && $sectionEmpty )
		{
			unset($fields[$sectionID]);
		}
	}

	public function countRows($resource, $users, $columns, $items = array(), $params = array())
	{
		// Get resource ID
		$resource = config::item('resources', 'core', $resource);

		// Join columns
		$joinColumns = '';
		if ( $columns )
		{
			$joinColumns = implode(' AND ', $columns);
		}

		// Join items
		$joinItems = '';
		if ( $items )
		{
			foreach ( $items as $fieldID => $itemID )
			{
				$joinItems .= " INNER JOIN `:prefix:" . $resource['table'] . "_items` AS `i" . $fieldID . "` ON `i" . $fieldID . "`.`data_id`=`" . $resource['prefix'] . "`.`" . $resource['column'] . "` AND
					`i" . $fieldID . "`.`field_id`=" . $fieldID . " AND `i" . $fieldID . "`.`item_id` IN (" . implode(',', $itemID) . ")";
			}
		}

		$row = $this->db->query("SELECT COUNT(" . ( $joinItems ? "DISTINCT(`" . $resource['prefix'] . "`.`" . $resource['column'] . "`)" : "`" . $resource['prefix'] . "`.`" . $resource['column'] . "`" ) . ") AS `totalrows`
			FROM `:prefix:" . $resource['table'] . ( isset($params['table_type']) ? '_' . $params['table_type'] : '' ) . "` AS `" . $resource['prefix'] . "`
			" . ( $users ? " INNER JOIN `:prefix:users` AS `u` ON `" . $resource['prefix'] . "`.`" . $resource['user'] . "`=`u`.`user_id` " : "" ) . " " . ( isset($params['join_tables']) ? $params['join_tables'] : '' ) . "
			" . ( $joinItems ? $joinItems : "") . ( $joinColumns ? " WHERE " . $joinColumns : "" ))->row();

		return $row['totalrows'];
	}

	public function getRow($resource, $dataID, $fields, $params = array())
	{
		// Get resource ID
		$resource = config::item('resources', 'core', $resource);

		// Do we have fields?
		if ( $fields )
		{
			// Do we have only the fields config value?
			if ( is_string($fields) )
			{
				// Get fields
				$fields = $this->getFields($resource['plugin'], ( isset($params['type_id']) ? $params['type_id'] : 0 ), 'view', $fields);
			}

			// Get dynamic columns
			list($tableColumns, $tableItems) = $this->getQueryDataColumns($fields, $resource['prefix'], ( isset($params['multilang']) ? $params['multilang'] : false ), $joins);

			if ( $joins )
			{
				if ( isset($params['join_tables']) )
				{
					$params['join_tables'] .= $joins;
				}
				else
				{
					$params['join_tables'] = $joins;
				}
			}
		}

		// Get basic columns
		$tableColumns = $this->getQueryTableColumns($resource['table'] . ( isset($params['table_type']) ? '_' . $params['table_type'] : '' ), $resource['prefix']) . ( isset($tableColumns) ? $tableColumns : '' );

		$row = $this->db->query("SELECT " . $tableColumns . " " . ( isset($params['select_columns']) ? ',' . $params['select_columns'] : '' ) . "
			FROM `:prefix:" . $resource['table'] . ( isset($params['table_type']) ? '_' . $params['table_type'] : '' ) . "` AS `" . $resource['prefix'] . "` " . ( isset($params['join_tables']) ? $params['join_tables'] : '' ) . "
			WHERE `" . $resource['prefix'] . "`.`" . ( isset($params['condition_column']) ? $params['condition_column'] : $resource['column'] ) . "`=?
			LIMIT 1", array($dataID))->row();

		// Do we have result?
		if ( $row && $fields )
		{
			// Parse row fields
			if ( !isset($params['parse']) || $params['parse'] )
			{
				$row = $this->parseFields($fields, $row, ( isset($params['multilang']) ? $params['multilang'] : false ), ( isset($params['escape']) ? $params['escape'] : true ));
			}

			// Do we have field items?
			if ( $tableItems && ( $tableItems = $this->getDataItems($dataID, $resource['table'], $tableItems, ( isset($params['escape']) ? $params['escape'] : true )) ) )
			{
				$row = array_merge($row, $tableItems);
			}
		}

		return $row;
	}

	public function getRows($resource, $users, $fields, $columns, $items, $order, $limit, $params = array())
	{
		// Get resource ID
		$resource = config::item('resources', 'core', $resource);

		// Sorting
		if ( $order )
		{
			$order = is_array($order) ? '`' . ( isset($params['prefix_order']) ? $params['prefix_order'] : $resource['prefix'] ) . '`.`' . key($order) . '` ' . current($order) : $order;
		}
		elseif ( $order !== false )
		{
			$order = '`' . ( isset($params['prefix_order']) ? $params['prefix_order'] : $resource['prefix'] ) . '`.`' . $resource['orderby'] . '` ' . $resource['orderdir'] . '';
		}

		// Join columns
		if ( $columns )
		{
			$joinColumns = implode(' AND ', $columns);
		}
		else
		{
			$joinColumns = '';
		}

		// Join items
		if ( $items )
		{
			$joinItems = '';
			foreach ( $items as $fieldID => $itemID )
			{
				$joinItems .= " INNER JOIN `:prefix:" . $resource['table'] . "_items` AS `i" . $fieldID . "` ON `i" . $fieldID . "`.`data_id`=`" . $resource['prefix'] . "`.`" . $resource['column'] . "` AND
					`i" . $fieldID . "`.`field_id`=" . $fieldID . " AND `i" . $fieldID . "`.`item_id` IN (" . implode(',', $itemID) . ")";
			}
		}
		else
		{
			$joinItems = '';
		}

		// Do we have fields?
		if ( $fields )
		{
			// Do we have only the fields config value?
			if ( is_string($fields) )
			{
				// Get fields
				$fields = $this->getFields($resource['plugin'], ( isset($params['type_id']) ? $params['type_id'] : 0 ), 'view', ( is_string($fields) ? $fields : '' ));
			}

			// Get dynamic columns
			list($tableColumns, $tableItems) = $this->getQueryDataColumns($fields, $resource['prefix'], ( isset($params['multilang']) ? $params['multilang'] : false ), $joins);

			if ( $joins )
			{
				if ( isset($params['join_tables']) )
				{
					$params['join_tables'] .= $joins;
				}
				else
				{
					$params['join_tables'] = $joins;
				}
			}
		}

		// Get basic columns
		$tableColumns = $this->getQueryTableColumns($resource['table'] . ( isset($params['table_type']) ? '_' . $params['table_type'] : '' ), $resource['prefix']) . ( isset($tableColumns) ? $tableColumns : '' );

		$rows = $userIDs = array();

		$result = $this->db->query("SELECT " . ( $joinItems ? "DISTINCT(`" . $resource['prefix'] . "`.`" . $resource['column'] . "`), " : "" ) . $tableColumns . "
				" . ( isset($params['select_columns']) ? ',' . $params['select_columns'] : '' ) . "
			FROM `:prefix:" . $resource['table'] . ( isset($params['table_type']) ? '_' . $params['table_type'] : '' ) . "` AS `" . $resource['prefix'] . "` " .
				( $users ? "INNER JOIN `:prefix:users` AS `u` ON `" . $resource['prefix'] . "`.`" . $resource['user'] . "`=`u`.`user_id` " : "" ) . "
				" . ( isset($params['join_tables']) ? $params['join_tables'] : '' ) . "
				" . ( $joinItems ? $joinItems : "") . ( $joinColumns ? " WHERE " . $joinColumns : "" ) . "
				" . ( $order ? " ORDER BY $order " : "" ) . ( $limit ? " LIMIT $limit " : "" ))->result();

		// Do we have anything?
		if ( $result )
		{
			// Loop through result set
			foreach ( $result as $row )
			{
				// Do we need to fetch users?
				if ( $users )
				{
					$userIDs[$row[$resource['user']]] = true;
				}

				// Parse fields
				if ( $resource['column'] )
				{
					$rows[$row[$resource['column']]] = $fields ? $this->parseFields($fields, $row, ( isset($params['multilang']) ? $params['multilang'] : false ), ( isset($params['escape']) ? $params['escape'] : true )) : $row;
				}
				else
				{
					$rows[] = $fields ? $this->parseFields($fields, $row, ( isset($params['multilang']) ? $params['multilang'] : false ), ( isset($params['escape']) ? $params['escape'] : true )) : $row;
				}
			}

			// Do we have field items?
			if ( isset($tableItems) && $tableItems && ( $tableItems = $this->getDataItems(array_keys($rows), $resource['table'], $tableItems, ( isset($params['escape']) ? $params['escape'] : true )) ) )
			{
				foreach ( $tableItems as $rowID => $item )
				{
					if ( isset($rows[$rowID]) )
					{
						$rows[$rowID] = array_merge($rows[$rowID], $item);
					}
				}
			}

			// Do we need to fetch users?
			if ( $rows && $users && $userIDs && $resource['keyword'] != 'profile' )
			{
				// Get users
				$users = $this->users_model->getUsers('in_list', 0, array('`u`.`user_id` IN (' . implode(',', array_keys($userIDs)) . ')'), array(), false, count($userIDs));

				// Loop through rows
				foreach ( $rows as $rowID => $row )
				{
					// Does user ID exist?
					if ( isset($users[$row[$resource['user']]]) )
					{
						// Set user fields
						$rows[$rowID]['user'] = $users[$row[$resource['user']]];
					}
				}
			}
		}

		return $rows;
	}

	public function getDataItems($dataID, $table, $items, $escape = true)
	{
		$data = array();

		// Loop through item IDs
		$qitems = $this->db->query("SELECT `data_id`, `field_id`, `item_id` FROM `:prefix:" . $table . "_items` WHERE `data_id` IN (?) AND `field_id` IN (?)", array($dataID, array_keys($items)))->result();
		foreach ( $qitems as $item )
		{
			$value = isset($items[$item['field_id']]['items'][$item['item_id']]) ? $items[$item['field_id']]['items'][$item['item_id']] : '';
			if ( is_array($dataID) )
			{
				$data[$item['data_id']]['data_'.$items[$item['field_id']]['keyword']][$item['item_id']] = $escape ? text_helper::entities($value) : $value;
			}
			else
			{
				$data['data_'.$items[$item['field_id']]['keyword']][$item['item_id']] = $escape ? text_helper::entities($value) : $value;
			}
		}

		return $data;
	}
}
