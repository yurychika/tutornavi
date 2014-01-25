<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users type-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_type_name">

				<label for="input_edit_user_type_name">
					<?=__('name', 'system')?>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_type',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
									'class' => 'input-xlarge',
								),
								'error' => false,
								'value' => $type,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_type_keyword">

				<label for="input_edit_user_type_keyword">
					<?=__('keyword', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_type',
						'field' => array(
							'keyword' => 'keyword',
							'type' => 'text',
							'maxlength' => '128',
							'class' => 'input-xlarge',
						),
						'value' => $type,
					)) ?>

				</div>

			</div>

			<? if ( $typeID ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_type_keyword">

					<label for="input_edit_user_type_field_name_1">
						<?=__('type_fields_name', 'users_types')?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_type',
							'field' => array(
								'keyword' => 'field_name_1',
								'type' => 'select',
								'items' => $fields,
							),
							'value' => $type,
							'error' => false,
						)) ?>

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_type',
							'field' => array(
								'keyword' => 'field_name_2',
								'type' => 'select',
								'items' => $fields,
							),
							'value' => $type,
							'error' => false,
						)) ?>

						<?=form_helper::error('field_name_1')?>
						<?=form_helper::error('field_name_2')?>

					</div>

				</div>

			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_type' => 1))?>

</section>

<script type="text/javascript">
function updateFieldNames()
{
	if ( $('#input_edit_user_type_field_name_1').val() == '' )
	{
		$('#input_edit_user_type_field_name_2').val('');
		$('#input_edit_user_type_field_name_2').hide();
	}
	else
	{
		$('#input_edit_user_type_field_name_2').show();
	}
}
$(function(){
	updateFieldNames();
	$('#input_edit_user_type_field_name_1').change(function(){
		updateFieldNames();
	});
});
</script>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
