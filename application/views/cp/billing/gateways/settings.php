<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-billing gateway-settings">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_gateway_setting_name">

				<label for="input_edit_gateway_setting_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'gateway_setting',
						'field' => array(
							'keyword' => 'name',
							'type' => 'text',
							'maxlength' => 128,
							'class' => 'input-xlarge',
						),
						'value' => $gateway,
					)) ?>

				</div>

			</div>

			<? foreach ( $settings as $setting ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_gateway_setting_<?=$setting['keyword']?>">

					<label for="input_edit_gateway_setting_<?=$setting['keyword']?>">
						<?=text_helper::entities($setting['name'])?> <? if ( isset($setting['required']) && $setting['required'] ): ?><span class="required">*</span><? endif; ?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'gateway_setting',
							'field' => $setting,
							'value' => $gateway['settings'],
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_gateway_setting_active">

				<label for="input_edit_gateway_setting_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'gateway_setting',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $gateway,
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
