<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-classifieds picture-edit">

	<figure class="image">
		<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't')?>');">
			<?=html_helper::anchor(storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext']), '<span></span>', array('data-role' => 'modal', 'class' => 'image'))?>
		</div>
	</figure>

	<?=form_helper::openForm()?>

		<fieldset class="form break <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>
					<? if ( $field['keyword'] == 'description' ) $field['type'] = 'textarea'; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">

						<label for="input_edit_picture_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'picture',
								'field' => $field,
								'value' => $picture,
							)) ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

		</fieldset>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? view::load('system/elements/field/section', array('name' => __('options_general', 'system'), 'type' => 'options')) ?>

			<? foreach ( $options as $option ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_<?=$option['keyword']?>">

					<label for="input_edit_picture_<?=$option['keyword']?>">
						<?=text_helper::entities($option['name'])?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'picture',
							'field' => $option,
							'value' => isset($picture[$option['keyword']]) ? $picture : $option,
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_picture' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
