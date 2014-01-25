<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system storage-settings">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $manifest['settings'] as $setting ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_storage_setting_<?=$setting['keyword']?>">

					<label for="input_edit_storage_setting_<?=$setting['keyword']?>">
						<?=text_helper::entities($setting['name'])?> <? if ( isset($setting['required']) && $setting['required'] ): ?><span class="required">*</span><? endif; ?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'storage_setting',
							'field' => $setting,
							'value' => $service['settings'],
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_settings' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
