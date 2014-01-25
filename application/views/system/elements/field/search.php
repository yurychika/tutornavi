<?
$name = ( isset($name_prefix) ? $name_prefix : '' ) . ( isset($field['system']) ? 'data_' : '' ) . $field['keyword'] . ( isset($field['category_id']) && $field['category_id'] ? '_' . $field['category_id'] : '' ) . ( isset($name_suffix) ? $name_suffix : '' );
$id = 'input_search_' . ( isset($prefix) ? $prefix . '_' : '' ) . $name . ( isset($field['category_id']) && $field['category_id'] ? '_' . $field['category_id'] : '' ) . ( isset($suffix) ? $suffix : '' );
?>
<? switch ( $field['type'] ):

	case 'number': ?>

		<?=form_helper::text($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-small' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id
		)) ?>
		<? break; ?>

	<? case 'price': ?>

		<?=form_helper::text($name.'__from', form_helper::setValue($name.'__from', ( isset($value[$name.'__from']) ? $value[$name.'__from'] : '' )), array(
			'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-small' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'__from'
		)) ?>
		-
		<?=form_helper::text($name.'__to', form_helper::setValue($name.'__to', ( isset($value[$name.'__to']) ? $value[$name.'__to'] : '' )), array(
			'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-small' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'__to'
		)) ?>
		<? break; ?>

	<? case 'boolean': ?>

		<?=form_helper::select($name, (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + array(1 => __('yes', 'system'), 0 => __('no', 'system'))),
			form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id
			)) ?>
		<? break; ?>

	<? case 'location': ?>

		<?=form_helper::select($name.'[country]', (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + geo_helper::getCountries()), form_helper::setSelect($name.'[country]', ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'_country',
			'onchange' => "geo('any',this.id,this.value)",
		)) ?>

		<? if ( isset($value[$name]['country']) && $value[$name]['country'] ): ?>

			<?=form_helper::select($name.'[state]', (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + geo_helper::getStates($value[$name]['country'])), form_helper::setSelect($name.'[state]', ( isset($value[$name]['state']) ? $value[$name]['state'] : '' )), array(
				'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_country_state',
				'onchange' => "geo('any',this.id,null,this.value)",
			)) ?>

			<? if ( isset($value[$name]['state']) && $value[$name]['state'] ): ?>

				<?=form_helper::select($name.'[city]', (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + geo_helper::getCities($value[$name]['state'])), form_helper::setSelect($name.'[city]', ( isset($value[$name]['city']) ? $value[$name]['city'] : '' )), array(
					'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
					'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
					'id' => $id.'_country_state_city',
				)) ?>

			<? endif; ?>

		<? endif; ?>

		<span class="icon icon-system-ajax geo-ajax ajax hidden" id="ajax-data-<?=$id.'_country'?>"></span>
		<span class="icon icon-system-ajax geo-ajax ajax hidden" id="ajax-data-<?=$id.'_country_state'?>"></span>

		<? if ( !isset($error) || $error ): ?>
			<? if ( form_helper::error($name.'[country]') ): ?>
				<?=form_helper::error($name.'[country]')?>
			<? elseif ( form_helper::error($name.'[state]') ): ?>
				<?=form_helper::error($name.'[state]')?>
			<? elseif ( form_helper::error($name.'[city]') ): ?>
				<?=form_helper::error($name.'[city]')?>
			<? endif; ?>
		<? endif; ?>

		<? break; ?>

	<? case 'country': ?>

		<?=form_helper::select($name, (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + geo_helper::getCountries()), form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
		)) ?>

		<? break; ?>

	<? case 'birthday': ?>

		<?=form_helper::select($name.'__from', (array('' => __('range_from', 'system')) + array_helper::buildArray(( isset($field['config']['min_age']) ? $field['config']['min_age'] : 18 ), ( isset($field['config']['max_age']) ? $field['config']['max_age'] : 99 ))),
			form_helper::setSelect($name.'__from', ( isset($value[$name.'__from']) ? $value[$name.'__from'] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'__from'
			)) ?>
		-
		<?=form_helper::select($name.'__to', (array('' => __('range_to', 'system')) + array_helper::buildArray(( isset($field['config']['min_age']) ? $field['config']['min_age'] : 18 ), ( isset($field['config']['max_age']) ? $field['config']['max_age'] : 99 ))),
			form_helper::setSelect($name.'__to', ( isset($value[$name.'__to']) ? $value[$name.'__to'] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'__to'
			)) ?>
		<? break; ?>

	<? case 'select': ?>

		<? if ( isset($field['config']['search_options']) && $field['config']['search_options'] == 'multiple' ): ?>
			<div class="checkbox <?=( isset($field['config']['columns_number']) && $field['config']['columns_number'] ? 'inline'.$field['config']['columns_number'] : 'inline1' )?> <?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?> clearfix" <?=( isset($field['style']) && $field['style'] ? 'style="'.$field['style'].'"' : '' )?>>
				<? foreach ( $field['items'] as $itemID => $itemName ): ?>
					<label>
						<?=form_helper::checkbox($name.'[]', $itemID, form_helper::setCheckbox($name, $itemID, ( isset($value[$name]) && is_array($value[$name]) && $value[$name] ? array_values($value[$name]) : array() )), array(
							'class' => 'checkbox '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
							'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
							'id' => $id.'_'.$itemID
						))?>
						<?=$itemName?>
					</label>
				<? endforeach; ?>
			</div>
		<? elseif ( isset($field['config']['search_options']) && $field['config']['search_options'] == 'range' ): ?>
			<?=form_helper::select($name.'__from', (array('' => __('range_from', 'system')) + $field['items']), form_helper::setSelect($name.'__from', ( isset($value[$name.'__from']) ? $value[$name.'__from'] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'__from'
			)) ?>
			-
			<?=form_helper::select($name.'__to', (array('' => __('range_to', 'system')) + $field['items']), form_helper::setSelect($name.'__to', ( isset($value[$name.'__to']) ? $value[$name.'__to'] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'__to'
			)) ?>
		<? else: ?>
			<?=form_helper::select($name, (array('' => isset($field['select']) && $field['select'] ? __('select', 'system') : __('any', 'system')) + $field['items']), form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id
			)) ?>
		<? endif; ?>
		<? break; ?>

	<? case 'checkmark': ?>

		<div class="checkbox <?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?>" <?=( isset($field['style']) && $field['style'] ? 'style="'.$field['style'].'"' : '' )?>>
			<label>
				<?=form_helper::checkbox($name, 1, form_helper::setCheckbox($name, 1, array(( isset($value[$name]) ? $value[$name] : '' ))), array(
					'class' => 'checkbox',
					'id' => $id
				))?>
			</label>
		</div>
		<? break; ?>

	<? case 'radio': ?>
	<? case 'checkbox': ?>

		<div class="<?=($field['type'] == 'checkbox' ? 'checkbox' : 'radio')?> <?=( isset($field['config']['columns_number']) && $field['config']['columns_number'] ? 'inline'.$field['config']['columns_number'] : 'inline1' )?> <?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?> clearfix" <?=( isset($field['style']) && $field['style'] ? 'style="'.$field['style'].'"' : '' )?>>
			<? foreach ( $field['items'] as $itemID => $itemName ): ?>
				<label>
					<? if ( $field['type'] == 'checkbox' || isset($field['config']['search_options']) && $field['config']['search_options'] == 'multiple' ): ?>
						<?=form_helper::checkbox($name.'[]', $itemID, form_helper::setCheckbox($name, $itemID, ( isset($value[$name]) && is_array($value[$name]) && $value[$name] ? array_values($value[$name]) : array() )), array(
							'class' => 'checkbox',
							'id' => $id.'_'.$itemID
						))?>
					<? else: ?>
						<?=form_helper::radio($name, $itemID, form_helper::setRadio($name, $itemID, ( isset($value[$name]) ? $value[$name] : '' )), array(
							'class' => 'radio',
							'id' => $id.'_'.$itemID
						))?>
					<? endif; ?>
					<?=$itemName?>
				</label>
			<? endforeach; ?>
		</div>
		<? break; ?>

	<? default: ?>

		<?=form_helper::text($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'text input-xlarge',
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
		)) ?>
		<? break; ?>

<? endswitch; ?>

<? if ( isset($errors) && $errors && ( !isset($error) || $error ) ): ?>
	<?=form_helper::error($name)?>
<? endif;
