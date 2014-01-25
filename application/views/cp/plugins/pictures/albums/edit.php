<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-pictures album-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=($privacy ? 'break' : '')?> <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>
					<? if ( $field['keyword'] == 'description' ) $field['type'] = 'textarea'; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_album_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">

						<label for="input_edit_picture_album_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'picture_album',
								'field' => $field,
								'value' => $album,
							)) ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

			<? if ( !$privacy ): ?>

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
				</div>

			<? endif; ?>

		</fieldset>

		<? if ( $privacy ): ?>

			<fieldset class="form grid <?=text_helper::alternate()?>">

				<? view::load('system/elements/field/section', array('name' => __('options_privacy', 'system'), 'type' => 'privacy')) ?>

				<? foreach ( $privacy as $option ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_album_<?=$option['keyword']?>">

						<label for="input_edit_picture_album_<?=$option['keyword']?>">
							<?=text_helper::entities($option['name'])?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'picture_album',
								'field' => $option,
								'value' => isset($album[$option['keyword']]) ? $album : $option,
							)) ?>

						</div>

					</div>

				<? endforeach; ?>

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
				</div>

			</fieldset>

		<? endif; ?>

	<?=form_helper::closeForm(array('do_save_album' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
