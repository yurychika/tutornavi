<? view::load('header'); ?>

<section class="plugin-pictures picture-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

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

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
				&nbsp;
				<?=html_helper::anchor('pictures/view/'.$picture['picture_id'].'/'.text_helper::slug($picture['data_description'], 100), __('cancel', 'system'))?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_picture' => 1))?>

</section>

<? view::load('footer');
