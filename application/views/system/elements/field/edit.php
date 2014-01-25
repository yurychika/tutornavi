<?
$name = ( isset($name_prefix) ? $name_prefix : '' ) . ( isset($field['system']) ? 'data_' : '' ) . $field['keyword'] . ( isset($name_suffix) ? $name_suffix : '' );
$id = 'input_edit_' . ( isset($prefix) ? $prefix . '_' : '' ) . $name . ( isset($suffix) ? '_' . $suffix : '' );
?>
<? switch ( $field['type'] ):

	case 'static': ?>

		<span class="static">
			<?=( isset($value[$name]) ? $value[$name] : '' )?>
		</span>
		<? break; ?>

	<? case 'number': ?>

		<?=form_helper::number($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-small' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
			'min' => ( isset($field['min']) && $field['min'] ? $field['min'] : '' ),
			'max' => ( isset($field['max']) && $field['max'] ? $field['max'] : '' ),
		)) ?>
		<? break; ?>

	<? case 'boolean': ?>

		<?=form_helper::select($name, array(1 => __('yes', 'system'), 0 => __('no', 'system')), form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
		)) ?>
		<? break; ?>

	<? case 'location': ?>

		<?=form_helper::select($name.'[country]', (array('' => __('select', 'system')) + geo_helper::getCountries()), form_helper::setSelect($name.'[country]', ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'_country',
			'onchange' => "geo('',this.id,this.value)",
		)) ?>

		<? if ( !input::postCount() ): ?>

			<? if ( isset($value[$name]) && $value[$name] ): ?>

				<?=form_helper::select($name.'[state]', (array('' => __('select', 'system')) + geo_helper::getStates($value[$name])), form_helper::setSelect($name.'[state]', ( isset($value[$name.'_state']) ? $value[$name.'_state'] : '' )), array(
					'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
					'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
					'id' => $id.'_country_state',
					'onchange' => "geo('',this.id,null,this.value)",
				)) ?>

				<? if ( isset($value[$name.'_state']) && $value[$name.'_state'] ): ?>

					<?=form_helper::select($name.'[city]', (array('' => __('select', 'system')) + geo_helper::getCities($value[$name.'_state'])), form_helper::setSelect($name.'[city]', ( isset($value[$name.'_city']) ? $value[$name.'_city'] : '' )), array(
						'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
						'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
						'id' => $id.'_country_state_city',
					)) ?>

				<? endif; ?>

			<? endif; ?>

		<? elseif ( ($location = input::post($name)) && isset($location['country']) && $location['country'] ): ?>

			<?=form_helper::select($name.'[state]', (array('' => __('select', 'system')) + geo_helper::getStates($location['country'])), form_helper::setSelect($name.'[state]', ( isset($location['state']) ? $location['state'] : '' )), array(
				'class' => 'select geo '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_country_state',
				'onchange' => "geo('',this.id,null,this.value)",
			)) ?>

			<? if ( isset($location['state']) && $location['state'] ): ?>

				<?=form_helper::select($name.'[city]', (array('' => __('select', 'system')) + geo_helper::getCities($location['state'])), form_helper::setSelect($name.'[city]', ( isset($location['city']) ? $location['city'] : '' )), array(
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

		<?=form_helper::select($name, (array('' => __('select', 'system')) + geo_helper::getCountries()), form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
		)) ?>

		<? break; ?>

	<? case 'birthday': ?>

		<? if ( config::item('time_euro', 'system') ): ?>
			<?=form_helper::select($name.'[day]', date_helper::days(true, true), form_helper::setSelect($name.'[day]', substr(( isset($value[$name]) ? $value[$name] : '' ), -2)), array(
				'class' => 'select date '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_day',
			)) ?>
			<?=form_helper::select($name.'[month]', date_helper::months(true, true), form_helper::setSelect($name.'[month]', substr(( isset($value[$name]) ? $value[$name] : '' ), 4, 2)), array(
				'class' => 'select date '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_month',
			)) ?>
		<? else: ?>
			<?=form_helper::select($name.'[month]', date_helper::months(true, true), form_helper::setSelect($name.'[month]', substr(( isset($value[$name]) ? $value[$name] : '' ), 4, 2)), array(
				'class' => 'select date '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_month',
			)) ?>
			<?=form_helper::select($name.'[day]', date_helper::days(true, true), form_helper::setSelect($name.'[day]', substr(( isset($value[$name]) ? $value[$name] : '' ), -2)), array(
				'class' => 'select date '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_day',
			)) ?>
		<? endif; ?>
		<?=form_helper::select($name.'[year]', date_helper::years(date('Y')-$field['config']['min_age'], date('Y')-$field['config']['max_age'], true), form_helper::setSelect($name.'[year]', substr(( isset($value[$name]) ? $value[$name] : '' ), 0, 4)), array(
			'class' => 'select date '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'_year',
		)) ?>

		<? if ( !isset($error) || $error ): ?>
			<? if ( form_helper::error($name.'[day]') ): ?>
				<?=form_helper::error($name.'[day]')?>
			<? elseif ( form_helper::error($name.'[month]') ): ?>
				<?=form_helper::error($name.'[month]')?>
			<? elseif ( form_helper::error($name.'[year]') ): ?>
				<?=form_helper::error($name.'[year]')?>
			<? endif; ?>
		<? endif; ?>

		<? break; ?>

	<? case 'date': ?>

		<? if ( session::item('time_euro') ): ?>
			<?=form_helper::select($name.'[day]', date_helper::days(true, true), form_helper::setSelect($name.'[day]', ( isset($value[$name]) && $value[$name] ? date('d', $value[$name]) : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_day',
			)) ?>
			<?=form_helper::select($name.'[month]', date_helper::months(true, true), form_helper::setSelect($name.'[month]', ( isset($value[$name]) && $value[$name] ? date('m', $value[$name]) : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_month',
			)) ?>
		<? else: ?>
			<?=form_helper::select($name.'[month]', date_helper::months(true, true), form_helper::setSelect($name.'[month]', ( isset($value[$name]) && $value[$name] ? date('m', $value[$name]) : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_month',
			)) ?>
			<?=form_helper::select($name.'[day]', date_helper::days(true, true), form_helper::setSelect($name.'[day]', ( isset($value[$name]) && $value[$name] ? date('d', $value[$name]) : '' )), array(
				'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id.'_day',
			)) ?>
		<? endif; ?>
		<?=form_helper::select($name.'[year]', date_helper::years(date('Y'), date('Y')+10, true), form_helper::setSelect($name.'[year]', ( isset($value[$name]) && $value[$name] ? date('Y', $value[$name]) : '' )), array(
			'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id.'_year',
		)) ?>
		<? break; ?>

	<? case 'checkmark': ?>

		<div class="checkbox inline1 <?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?>" <?=( isset($field['style']) && $field['style'] ? 'style="'.$field['style'].'"' : '' )?>>
			<label>
				<?=form_helper::checkbox($name, 1, form_helper::setCheckbox($name, 1, array(( isset($value[$name]) ? $value[$name] : '' ))), array(
					'class' => 'checkbox',
					'id' => $id
				))?>
				<?=$field['name']?>
			</label>
		</div>
		<? break; ?>

	<? case 'select': ?>

		<?=form_helper::select($name, (isset($field['select']) && $field['select'] ? array('' => __('select', 'system')) + $field['items'] : $field['items']), form_helper::setSelect($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
			'class' => 'select '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
			'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
			'id' => $id,
		)) ?>
		<? break; ?>

	<? case 'radio': ?>
	<? case 'checkbox': ?>

		<div class="<?=($field['type'] == 'checkbox' ? 'checkbox' : 'radio')?> <?=( isset($field['config']['columns_number']) && $field['config']['columns_number'] ? 'inline'.$field['config']['columns_number'] : 'inline1' )?> <?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?> clearfix" <?=( isset($field['style']) && $field['style'] ? 'style="'.$field['style'].'"' : '' )?>>
			<? foreach ( $field['items'] as $itemID => $itemName ): ?>
				<label>
					<? if ( $field['type'] == 'checkbox' ): ?>
						<?=form_helper::checkbox($name.'[]', $itemID, form_helper::setCheckbox($name, $itemID, ( isset($value[$name]) && is_array($value[$name]) && $value[$name] ? array_keys($value[$name]) : array() )), array(
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

	<? case 'image': ?>
	<? case 'music': ?>
	<? case 'video': ?>
	<? case 'file': ?>

		<? if ( !isset($value['file_service_id'], $value['file_replace']) || !$value['file_service_id'] || !$value['file_replace'] ): ?>
			<?=form_helper::upload($name, '', array(
				'class' => 'upload '.( isset($field['class']) && $field['class'] ? $field['class'] : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id
			))?>
		<? endif; ?>
		<? if ( isset($value['file_service_id']) && $value['file_service_id'] && ( isset($value['file_view']) && $value['file_view'] || isset($value['file_delete']) && $value['file_delete'] ) ): ?>
			<div class="actions">
				<? if ( isset($value['file_view']) && $value['file_view'] ): ?>
					<span class="view">
						<?=html_helper::anchor(
							storage_helper::getFileURL(
								( isset($value['file_service_id']) ? $value['file_service_id'] : '' ),
								( isset($value['file_path']) ? $value['file_path'] : '' ),
								( isset($value['file_name']) ? $value['file_name'] : '' ),
								( isset($value['file_ext']) ? $value['file_ext'] : '' ),
								( isset($value['file_suffix']) ? $value['file_suffix'] : '' )
							), __('view_file', 'system'), array('target' => '_blank'))?>
					</span>
				<? endif; ?>
				<? if ( isset($value['file_delete']) && $value['file_delete'] ): ?>
					<span class="delete">
						<label>
							<?=form_helper::checkbox($name.'__delete', 1, false, array('class' => 'checkbox'))?> <?=__('file_delete', 'system')?>
						</label>
					</span>
				<? endif; ?>
			</div>
			<div class="clearfix"></div>
		<? endif; ?>
		<? break; ?>

	<? case 'textarea': ?>

		<? if ( !isset($wrap) || $wrap ): ?>
			<div class="input-wrap">
		<? endif; ?>
			<?=form_helper::textarea($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
				'class' => 'textarea '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-wide input-xlarge-y' ).( isset($field['config']['html']) && $field['config']['html'] ? ' html ckeditor' : '' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'placeholder' => ( isset($field['placeholder']) && $field['placeholder'] ? $field['placeholder'] : '' ),
				'id' => $id,
			)) ?>
		<? if ( !isset($wrap) || $wrap ): ?>
			</div>
		<? endif; ?>
		<? break; ?>

	<? case 'password': ?>

		<? if ( !isset($wrap) || $wrap ): ?>
			<div class="input-wrap">
		<? endif; ?>
			<?=form_helper::password($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
				'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-xlarge' ),
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'id' => $id,
				'maxlength' => ( isset($field['maxlength']) && $field['maxlength'] ? $field['maxlength'] : '' ),
			)) ?>
		<? if ( !isset($wrap) || $wrap ): ?>
			</div>
		<? endif; ?>
		<? break; ?>

	<? case 'captcha': ?>

		<?=form_helper::captcha($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' ))) ?>
		<? break; ?>

	<? default: ?>

		<? if ( !isset($wrap) || $wrap ): ?>
			<div class="input-wrap">
		<? endif; ?>
			<?=form_helper::text($name, form_helper::setValue($name, ( isset($value[$name]) ? $value[$name] : '' )), array(
				'class' => 'text '.( isset($field['class']) && $field['class'] ? $field['class'] : 'input-wide' ),
				'id' => $id,
				'style' => ( isset($field['style']) && $field['style'] ? $field['style'] : '' ),
				'placeholder' => ( isset($field['placeholder']) && $field['placeholder'] ? $field['placeholder'] : '' ),
				'maxlength' => ( isset($field['config']['max_length']) && $field['config']['max_length'] ? $field['config']['max_length'] : ( isset($field['max_length']) && $field['max_length'] ? $field['max_length'] : '255' )),
			)) ?>
		<? if ( !isset($wrap) || $wrap ): ?>
			</div>
		<? endif; ?>
		<? break; ?>

<? endswitch; ?>

<? if ( !isset($error) || $error ): ?>
	<?=form_helper::error($name)?>
<? endif;
