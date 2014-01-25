<? view::load('header'); ?>

<section class="plugin-users settings-account">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $settings as $setting ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_<?=$setting['keyword']?>">

					<label for="input_edit_user_account_<?=$setting['keyword']?>">
						<?=$setting['name']?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_account',
							'field' => $setting,
							'value' => isset($setting['value']) ? array($setting['keyword'] => $setting['value']) : array(),
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

<? view::load('footer');
