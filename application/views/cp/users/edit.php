<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users user-edit">

	<?=form_helper::openMultiForm()?>

		<? if ( $user && $user['picture_id'] ): ?>

			<fieldset class="form picture <?=text_helper::alternate()?>">

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_picture">

					<figure class="image users-image">
						<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($user['picture_file_service_id'], $user['picture_file_path'], $user['picture_file_name'], $user['picture_file_ext'], 'p')?>')">
							<?=html_helper::anchor(storage_helper::getFileURL($user['picture_file_service_id'], $user['picture_file_path'], $user['picture_file_name'], $user['picture_file_ext']), '<span></span>', array('data-role' => 'modal', 'class' => 'image'))?>
						</div>
					</figure>

				</div>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_picture_options">

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user',
							'field' => array(
								'keyword' => 'picture_options',
								'type' => 'checkbox',
								'items' => array(
									'picture_active' => __('active', 'system'),
									'picture_delete' => __('picture_delete', 'users_picture'),
								),
							),
							//'value' => $user,
							'value' => array(
								'picture_options' => $user['picture_active'] == 1 ? array('picture_active' => 1) : array(),
							),
						)) ?>

					</div>

				</div>

			</fieldset>

		<? endif; ?>

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

	<?=form_helper::closeForm(array('do_save_user' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
