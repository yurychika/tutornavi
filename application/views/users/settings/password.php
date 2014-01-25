<? view::load('header'); ?>

<section class="plugin-users settings-account-password">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_password">

				<label for="input_edit_user_account_password">
					<?=__('password_new', 'users')?>
				</label>
				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_account',
						'field' => array(
							'keyword' => 'password',
							'type' => 'password',
							'maxlength' => 128,
						),
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_password2">

				<label for="input_edit_user_account_password2">
					<?=__('password_confirm_new', 'users')?>
				</label>
				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_account',
						'field' => array(
							'keyword' => 'password2',
							'type' => 'password',
							'maxlength' => 128,
						),
					)) ?>

				</div>

			</div>

			<? if ( session::item('password') ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_old_password">

					<label for="input_edit_user_account_old_password">
						<?=__('password_current', 'users')?>
					</label>
					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_account',
							'field' => array(
								'keyword' => 'old_password',
								'type' => 'password',
								'maxlength' => 128,
							),
						)) ?>

					</div>

				</div>

			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_password' => 1))?>

</section>

<? view::load('footer');
