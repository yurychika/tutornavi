<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users user-login">

	<? if ( uri::segment(4) == 'license' && !input::demo(0, '', false) ): ?>

		<div class="break">
			<?=__('license_login', 'system_license', array(), array('%' => html_helper::anchor('cp/users/login', '\1')))?>
		</div>

	<? endif; ?>

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_email">

				<label for="input_edit_user_login_email">
					<?=__('email', 'users')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_login',
						'field' => array(
							'keyword' => 'email',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => ( input::demo(0, '', false) && !input::postCount() ? array('email' => 'admin@demo.com') : '' ),
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_password">

				<label for="input_edit_user_login_password">
					<?=__('password', 'users')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_login',
						'field' => array(
							'keyword' => 'password',
							'type' => 'password',
							'maxlength' => 128,
							'class' => 'input-xlarge',
						),
						'value' => ( input::demo(0, '', false) && !input::postCount() ? array('password' => 'demo') : '' ),
					)) ?>

				</div>

			</div>

			<? if ( uri::segment(4) == 'license' && !input::demo(0, '', false) ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_license">

					<label for="input_edit_user_login_license">
						<?=__('license_new', 'system_license')?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_login',
							'field' => array(
								'keyword' => 'license',
								'type' => 'text',
								'class' => 'input-xlarge',
							),
							'value' => '',
						)) ?>

					</div>

				</div>

			<? else: ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_remember">

					<label for="input_edit_user_login_remember">
						<?=__('remember_me', 'users')?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_login',
							'field' => array(
								'keyword' => 'remember',
								'type' => 'boolean',
							),
							'value' => '',
						)) ?>

					</div>

				</div>

			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_login' => 1))?>

</div>

<? view::load('cp/system/elements/template/footer'); ?>
