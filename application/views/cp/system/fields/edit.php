<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system field-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid">

			<div class="row" id="input_row_system_field_name">
				<label for="input_edit_system_field_name_<?=session::item('language')?>"><?=__('name', 'system_fields')?> <span class="required">*</span></label>
				<div class="field">
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?> <?=(session::item('language') != $lang ? 'hidden' : '')?>">
							<?=form_helper::text('name_'.$lang, form_helper::setValue('name_'.$lang, ( isset($field['name_'.$lang]) ? $field['name_'.$lang] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_name_'.$lang)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?>">
							<?=form_helper::error('name_'.$lang)?>
						</div>
					<? endforeach; ?>
				</div>
			</div>

			<div class="row" id="input_row_system_field_vname">
				<label for="input_edit_system_field_vname_<?=session::item('language')?>"><?=__('name_view', 'system_fields')?></label>
				<div class="field">
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?> <?=(session::item('language') != $lang ? 'hidden' : '')?>">
							<?=form_helper::text('vname_'.$lang, form_helper::setValue('vname_'.$lang, ( isset($field['vname_'.$lang]) ? $field['vname_'.$lang] : '') ), array('class' => 'text input-xlarge vname', 'id' => 'input_edit_system_field_vname_'.$lang)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?>">
							<?=form_helper::error('vname_'.$lang)?>
						</div>
					<? endforeach; ?>
				</div>
			</div>

			<div class="row" id="input_row_system_field_sname">
				<label for="input_edit_system_field_sname_<?=session::item('language')?>"><?=__('name_search', 'system_fields')?></label>
				<div class="field">
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?> <?=(session::item('language') != $lang ? 'hidden' : '')?>">
							<?=form_helper::text('sname_'.$lang, form_helper::setValue('sname_'.$lang, ( isset($field['sname_'.$lang]) ? $field['sname_'.$lang] : '') ), array('class' => 'text input-xlarge sname', 'id' => 'input_edit_system_field_sname_'.$lang)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
						<div class="translate_item_<?=$lang?>">
							<?=form_helper::error('sname_'.$lang)?>
						</div>
					<? endforeach; ?>
				</div>
			</div>

			<? if ( isset($hidden['keyword']) ): ?>
				<?=form_helper::hidden('keyword', ( isset($field['keyword']) ? $field['keyword'] : $hidden['keyword'] ), array('id' => 'input_edit_system_field_keyword'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_keyword">
					<label for="input_edit_system_field_keyword"><?=__('keyword', 'system')?> <span class="required">*</span></label>
					<div class="field">
						<?=form_helper::text('keyword', form_helper::setValue('keyword', ( isset($field['keyword']) ? $field['keyword'] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_keyword')) ?>
						<?=form_helper::error('keyword')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['type']) ): ?>
				<?=form_helper::hidden('type', ( isset($field['type']) ? $field['type'] : $hidden['type'] ), array('id' => 'input_edit_system_field_type'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_type">
					<label for="input_edit_system_field_type"><?=__('field_type', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('type', $types, form_helper::setSelect('type', ( isset($field['type']) ? $field['type'] : '' )), array('class' => 'select', 'id' => 'input_edit_system_field_type', 'onchange' => 'switchType(this.value)')) ?>
						<?=form_helper::error('type')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['required']) ): ?>
				<?=form_helper::hidden('required', ( isset($field['required']) ? $field['required'] : $hidden['required'] ), array('id' => 'input_edit_system_field_required'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_required">
					<label for="input_edit_system_field_required"><?=__('required', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('required', array(1 => __('yes', 'system'), 0 => __('no', 'system')), form_helper::setSelect('required', isset($field['required']) ? $field['required'] : 0), array('class' => 'select', 'id' => 'input_edit_system_field_required')) ?>
						<?=form_helper::error('required')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['html']) ): ?>
				<?=form_helper::hidden('html', ( isset($field['config']['html']) ? $field['config']['html'] : $hidden['html'] ), array('id' => 'input_edit_system_field_html'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_html">
					<label for="input_edit_system_field_html"><?=__('config_html', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('html', array(1 => __('yes', 'system'), 0 => __('no', 'system')), form_helper::setSelect('html', isset($field['config']['html']) ? $field['config']['html'] : ''), array('class' => 'select', 'id' => 'input_edit_system_field_html')) ?>
						<?=form_helper::error('html')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['validate']) ): ?>
				<?=form_helper::hidden('validate', ( isset($field['validate']) ? $field['validate'] : $hidden['validate'] ), array('id' => 'input_edit_system_field_validate'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_validate">
					<label for="input_edit_system_field_validate"><?=__('validate', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::text('validate', form_helper::setValue('validate', ( isset($field['validate']) ? $field['validate'] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_validate')) ?>
						<?=form_helper::error('validate')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['validate_error']) ): ?>
				<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
					<?=form_helper::hidden('validate_error_'.$lang, ( isset($field['validate_error_'.$lang]) ? $field['validate_error_'.$lang] : $hidden['validate_error'] ), array('id' => 'input_edit_system_field_validate_error_'.$lang))?>
				<? endforeach; ?>
			<? else: ?>
				<div class="row" id="input_row_system_field_validate_error">
					<label for="input_edit_system_field_validate_error_<?=session::item('language')?>"><?=__('validate_error', 'system_fields')?></label>
					<div class="field">
						<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
							<div class="translate_item_<?=$lang?> <?=(session::item('language') != $lang ? 'hidden' : '')?>">
								<?=form_helper::text('validate_error_'.$lang, form_helper::setValue('validate_error_'.$lang, ( isset($field['validate_error_'.$lang]) ? $field['validate_error_'.$lang] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_validate_error_'.$lang)) ?>
								<?=form_helper::error('validate_error_'.$lang)?>
							</div>
						<? endforeach; ?>
					</div>
				</div>
			<? endif; ?>

			<div class="row" id="input_row_system_field_items">
				<label for="input_edit_system_field_items"><?=__('items', 'system_fields')?> <span class="required">*</span></label>
				<div class="field">
					<div class="wrap">
						<? foreach ( config::item('languages', 'core', 'keywords') as $lang ): ?>
							<div class="translate_item_<?=$lang?> <?=(session::item('language') != $lang ? 'hidden' : '')?>">
								<ul class="unstyled items items_sortable sortable-form" id="field_items_list__<?=$lang?>">
									<? if ( isset($field['items']) ): ?>
										<? foreach ( $field['items'] as $itemID => $item ): ?>
											<li id="items_list_item__<?=$lang?>__<?=$itemID?>" class="clearfix">
												<span class="handle"></span>
												<?=form_helper::text('items['.$lang.']['.$itemID.']', form_helper::setValue('items['.$lang.']['.$itemID.']', ( isset($item['name_'.$lang]) ? $item['name_'.$lang] : '' )), array('class' => 'text input-large')) ?>
												<?=form_helper::text('sitems['.$lang.']['.$itemID.']', form_helper::setValue('sitems['.$lang.']['.$itemID.']', ( isset($item['sname_'.$lang]) ? $item['sname_'.$lang] : '' )), array('class' => 'text input-large sitems', 'title' => __('search_name', 'system_fields'))) ?>
												<?=html_helper::anchor('#', __('items_add', 'system_fields'), array('onclick' => 'addFieldItem(' . $itemID . ');return false;', 'class' => 'icon-text icon-system-fields-items-new'))?>
												<?=html_helper::anchor('#', __('items_delete', 'system_fields'), array('onclick' => 'deleteFieldItem(' . $itemID . ');return false;', 'class' => 'icon-text icon-system-fields-items-delete'))?>
											</li>
										<? endforeach; ?>
									<? endif; ?>
								</ul>
							</div>
						<? endforeach; ?>
					</div>
					<?=html_helper::anchor('#', 'Add new', array('onclick' => 'addFieldItem(0);return false;', 'class' => 'icon-text icon-system-fields-items-new'))?><br/>
					<?=form_helper::error('items')?>
				</div>
			</div>

			<? foreach ( $properties as $type => $property ): ?>

				<? foreach ( $property as $option ): ?>

					<? if ( isset($hidden['config_'.$type.'_'.$option['keyword']]) ): ?>
						<?=form_helper::hidden('config_'.$type.'_'.$option['keyword'], $hidden['config_'.$type.'_'.$option['keyword']], array('id' => 'input_row_system_field_config_'.$type.'_'.$option['keyword']))?>
					<? else: ?>
						<div class="row <?=($type != 'custom' ? 'input_row_system_field_config' : '')?> input_row_system_field_config_<?=$type?>" id="input_row_system_field_config_<?=$type?>_<?=$option['keyword']?>">
							<label for="input_edit_system_field_config_<?=$type?>_<?=$option['keyword']?>"><?=$option['label']?> <?=(isset($option['required']) && $option['required'] ? '<span class="required">*</span>' :'')?></label>
							<div class="field">
								<? view::load('system/elements/field/edit', array(
									'name_prefix' => 'config_'.$type.'_',
									'prefix' => 'system_field',
									'field' => $option,
									'value' => array('config_'.$type.'_'.$option['keyword'] => isset($field['config'][$option['keyword']]) ? $field['config'][$option['keyword']] : ( isset($option['default']) ? $option['default'] : '' )),
									'error' => false,
								)) ?>
								<?=form_helper::error('config_'.$type.'_'.$option['keyword'])?>
							</div>
						</div>
					<? endif; ?>

				<? endforeach; ?>

			<? endforeach; ?>

			<? if ( isset($hidden['in_search']) ): ?>
				<?=form_helper::hidden('in_search', ( isset($field['config']['in_search']) ? $field['config']['in_search'] : $hidden['in_search'] ), array('id' => 'input_edit_system_field_in_search'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_in_search">
					<label for="input_edit_system_field_in_search"><?=__('config_in_search', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('in_search', array(1 => __('yes', 'system'), 0 => __('no', 'system')), form_helper::setSelect('in_search', isset($field['config']['in_search']) ? $field['config']['in_search'] : 0), array('class' => 'select', 'id' => 'input_edit_system_field_in_search', 'onchange' => 'switchSearch()')) ?>
						<?=form_helper::error('in_search')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['in_search_advanced']) ): ?>
				<?=form_helper::hidden('in_search_advanced', ( isset($field['config']['in_search_advanced']) ? $field['config']['in_search_advanced'] : $hidden['in_search_advanced'] ), array('id' => 'input_edit_system_field_in_search_advanced'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_in_search_advanced">
					<label for="input_edit_system_field_in_search_advanced"><?=__('config_in_search_advanced', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('in_search_advanced', array(1 => __('yes', 'system'), 0 => __('no', 'system')), form_helper::setSelect('in_search_advanced', isset($field['config']['in_search_advanced']) ? $field['config']['in_search_advanced'] : 0), array('class' => 'select', 'id' => 'input_edit_system_field_in_search_advanced', 'onchange' => 'switchSearch()')) ?>
						<?=form_helper::error('in_search_advanced')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['search_options']) ): ?>
				<?=form_helper::hidden('search_options', ( isset($field['config']['search_options']) ? $field['config']['search_options'] : $hidden['search_options'] ), array('id' => 'input_edit_system_field_search_options'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_search_options">
					<label for="input_edit_system_field_search_options"><?=__('config_search_options', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('search_options', array('' => __('none', 'system'), 'range' => __('config_search_range', 'system_fields'), 'multiple' => __('config_search_multiple', 'system_fields')), form_helper::setSelect('search_options', isset($field['config']['search_options']) ? $field['config']['search_options'] : ''), array('class' => 'select', 'id' => 'input_edit_system_field_search_options', 'onchange' => 'switchSearch()')) ?>
						<?=form_helper::error('search_options')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['columns_number']) ): ?>
				<?=form_helper::hidden('columns_number', ( isset($field['config']['columns_number']) ? $field['config']['columns_number'] : $hidden['columns_number'] ), array('id' => 'input_edit_system_field_columns_number'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_columns_number">
					<label for="input_edit_system_field_columns_number"><?=__('config_columns', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::select('columns_number', array(1 => 1, 2 => 2, 3 => 3, 4 => 4), form_helper::setSelect('columns_number', isset($field['config']['columns_number']) ? $field['config']['columns_number'] : 2), array('class' => 'select', 'id' => 'input_edit_system_field_columns_number')) ?>
						<?=form_helper::error('columns_number')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['class']) ): ?>
				<?=form_helper::hidden('class', ( isset($field['class']) ? $field['class'] : $hidden['class'] ), array('id' => 'input_edit_system_field_class'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_class">
					<label for="input_edit_system_field_class"><?=__('class', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::text('class', form_helper::setValue('class', ( isset($field['class']) ? $field['class'] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_class')) ?>
						<?=form_helper::error('class')?>
					</div>
				</div>
			<? endif; ?>

			<? if ( isset($hidden['style']) ): ?>
				<?=form_helper::hidden('style', ( isset($field['style']) ? $field['style'] : $hidden['style'] ), array('id' => 'input_edit_system_field_style'))?>
			<? else: ?>
				<div class="row" id="input_row_system_field_style">
					<label for="input_edit_system_field_style"><?=__('style', 'system_fields')?></label>
					<div class="field">
						<?=form_helper::text('style', form_helper::setValue('style', ( isset($field['style']) ? $field['style'] : '' )), array('class' => 'text input-xlarge', 'id' => 'input_edit_system_field_style')) ?>
						<?=form_helper::error('style')?>
					</div>
				</div>
			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_field' => 1))?>

</section>

<script type="text/javascript">
	var languages = ['<?=implode("','", config::item('languages', 'core', 'keywords'))?>'];
	var selectedLanguage = '<?=session::item('language')?>';
	var itemsLastID = '<?=($lastItemID+1)?>';
	function addFieldItem(after_id)
	{
		$(languages).each(function(index, language)
		{
			var element = '<li id="items_list_item__'+language+'__'+itemsLastID+'" class="clearfix">' +
				'<span class="handle"></span>' +
				'<input type="text" name="items['+language+']['+itemsLastID+']" value="" class="text input-large" /> ' +
				'<input type="text" name="sitems['+language+']['+itemsLastID+']" value="" class="text input-large sitems ' + ( $('#input_edit_system_field_in_search').val() == '0' && $('#input_edit_system_field_in_search_advanced').val() == '0' ? 'hidden' : '' )  + '" title="<?=__('search_name', 'system_fields')?>" /> ' +
				'<a href="#" onclick="addFieldItem('+itemsLastID+');return false;" class="icon-text icon-system-fields-items-new"><?=__('items_add', 'system_fields')?></a> ' +
				'<a href="#" onclick="deleteFieldItem('+itemsLastID+');return false;" class="icon-text icon-system-fields-items-delete"><?=__('items_delete', 'system_fields')?></a>' +
				'</li>';

			if ( after_id == 0 )
			{
				$('#field_items_list__'+language).append(element);
			}
			else
			{
				$('#items_list_item__'+language+'__'+after_id).after(element);
			}
		});
		itemsLastID++;
		$('.items_sortable').sortable({handle: 'span.handle'});
	}
	function deleteFieldItem(item_id)
	{
		$(languages).each(function(index, language)
		{
			$('#items_list_item__'+language+'__'+item_id).remove();
		});
		$('.items_sortable').sortable({handle: 'span.handle'});
	}
	function updateFieldItemOrder()
	{
		$('#field_items_list__'+selectedLanguage+' > li').each(function(index, item)
		{
			$(languages).each(function(index, language)
			{
				if ( language != selectedLanguage )
				{
					var item_id = $(item).attr('id');
					item_id = item_id.split('__');
					item_id = item_id[2];

					var element = $('#items_list_item__'+language+'__'+item_id);
					$('#items_list_item__'+language+'__'+item_id).remove();
					$('#field_items_list__'+language).append(element);
				}
			});
		});

		$('.items_sortable').sortable({handle: 'span.handle'});
	}
	function switchType(type)
	{
		var elements = ['required', 'items', 'validate', 'validate_error', 'columns_number'];
		var show = [];

		switch ( type )
		{
			case 'text':
			case 'website':
			case 'textarea':
				var show = ['required', 'validate', 'validate_error'];
				break;

			case 'select':
				var show = ['required', 'items'];
				break;

			case 'radio':
				var show = ['required', 'items'];
				break;

			case 'checkbox':
				var show = ['required', 'items', 'columns_number'];
				break;

			case 'country':
			case 'location':
				var show = ['required'];
				break;

			case 'price':
				var show = ['required'];
				break;

			case 'birthday':
				var show = ['required'];
				break;
		}

		$(elements).each(function(index, item){
			if ( show.indexOf(item) != -1 )
			{
				$('#input_row_system_field_'+item).show();
			}
			else
			{
				$('#input_row_system_field_'+item).hide();
			}
		});

		$('.input_row_system_field_config').hide();
		$('.input_row_system_field_config_'+type).show();
		switchSearch();
	}
	function switchSearch()
	{
		if ( $('#input_edit_system_field_in_search').val() == '1' || $('#input_edit_system_field_in_search_advanced').val() == '1' )
		{
			$('#input_row_system_field_sname').show();
			$('#input_row_system_field_items .sitems').show();
		}
		else
		{
			$('#input_row_system_field_sname').hide();
			$('#input_row_system_field_items .sitems').hide();
		}

		if ( !$('#field_items_list__'+selectedLanguage).hasClass('hidden') )
		{
			if ( $('#input_edit_system_field_in_search').val() == '1' || $('#input_edit_system_field_in_search_advanced').val() == '1' )
			{
				$('#field_items_list__'+selectedLanguage+' .item_search').removeClass('hidden');
			}
			else
			{
				$('#field_items_list__'+selectedLanguage+' .item_search').addClass('hidden');
			}
		}

		if ( ( $('#input_edit_system_field_type').val() == 'select' || $('#input_edit_system_field_type').val() == 'radio' || $('#input_edit_system_field_type').val() == 'price' || $('#input_edit_system_field_type').val() == 'number' ) && ( $('#input_edit_system_field_in_search').val() == '1' || $('#input_edit_system_field_in_search_advanced').val() == '1' ) )
		{
			if ( $('#input_edit_system_field_type').val() == 'price' || $('#input_edit_system_field_type').val() == 'number' )
			{
				$('#input_row_system_field_search_options').hide();
				$('#input_edit_system_field_search_options').val('range');
			}
			else
			{
				$('#input_row_system_field_search_options').show();

				if ( $('#input_edit_system_field_search_options').val() == 'multiple' )
				{
					$('#input_row_system_field_columns_number').show();
				}
				else
				{
					$('#input_row_system_field_columns_number').hide();
				}
			}
		}
		else
		{
			$('#input_row_system_field_search_options').hide();

			if ( $('#input_edit_system_field_type').val() != 'checkbox' )
			{
				$('#input_row_system_field_columns_number').hide();
			}
		}
	}
	$(function(){
		switchType($('#input_edit_system_field_type').val());
		switchLanguage('<?=session::item('language')?>');
		head(function(){
			$('.items_sortable').sortable({handle: 'span.handle'}).bind('sortupdate', function() {
				updateFieldItemOrder();
			});
		});
	});
</script>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
