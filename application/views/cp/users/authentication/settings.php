<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users authentication-settings">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $manifest['settings'] as $setting ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_auth_service_setting_<?=$setting['keyword']?>">

					<label for="input_edit_user_auth_service_setting_<?=$setting['keyword']?>">
						<?=text_helper::entities($setting['name'])?> <? if ( isset($setting['required']) && $setting['required'] ): ?><span class="required">*</span><? endif; ?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_auth_service_setting',
							'field' => $setting,
							'value' => $service['settings'],
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_auth_service_setting_active">

				<label for="input_edit_user_auth_service_setting_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_auth_service_setting',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $service,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_settings' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
