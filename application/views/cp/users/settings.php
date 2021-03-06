<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users user-privacy">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $settings as $setting ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_settings_<?=$setting['keyword']?>">

					<label for="input_edit_user_settings_<?=$setting['keyword']?>">
						<?=$setting['name']?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_settings',
							'field' => $setting,
							'value' => isset($setting['value']) ? $setting['value'] : array(),
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
