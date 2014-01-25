<? view::load('header'); ?>

<section class="plugin-users settings-account-email">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_email">

				<label for="input_edit_user_account_email">
					<?=__('email_new', 'users')?>
				</label>
				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_account',
						'field' => array(
							'keyword' => 'email',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => array('email' => session::item('email')),
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_account_password">

				<label for="input_edit_user_account_password">
					<?=__('password_current', 'users')?>
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

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_email' => 1))?>

</section>

<? view::load('footer');
