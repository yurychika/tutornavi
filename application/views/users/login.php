<? view::load('header'); ?>

<section class="plugin-users login">

	<? if ( config::item('auth_methods', 'users', 'default') ): ?>

		<?=form_helper::openForm()?>

			<fieldset class="form grid <?=text_helper::alternate()?>">

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_email">

					<label for="input_edit_user_login_email">
						<?=__('email', 'users')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_login',
							'field' => array(
								'keyword' => 'email',
								'type' => 'text',
								'class' => 'input-xlarge email',
							),
							'value' => '',
						)) ?>

					</div>

				</div>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_password">

					<label for="input_edit_user_login_password">
						<?=__('password', 'users')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_login',
							'field' => array(
								'keyword' => 'password',
								'type' => 'password',
								'class' => 'input-xlarge password',
								'maxlength' => 128,
							),
							'value' => '',
						)) ?>

					</div>

				</div>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_login_remember">

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_login',
							'field' => array(
								'name' => __('remember_me', 'users'),
								'keyword' => 'remember',
								'type' => 'checkmark',
							),
							'value' => '',
						)) ?>

					</div>

				</div>

				<div class="row actions">
					<? view::load('system/elements/button', array('value' => __('login', 'system_navigation'))); ?>
				</div>

				<div class="row actions">
					<?=html_helper::anchor('users/login/lostpass', __('lost_password', 'system_navigation'))?>
					<? if ( config::item('signup_email_verify', 'users') ): ?> |
						<?=html_helper::anchor('users/login/resend', __('resend_activation', 'system_navigation'))?>
					<? endif; ?>
				</div>

			</fieldset>

		<?=form_helper::closeForm(array('do_login' => 1))?>

	<? endif; ?>

	<div class="remote-connect <?=(config::item('auth_methods', 'users', 'default') && count(config::item('auth_methods', 'users')) > 1 ? 'extra' : '')?>">
		<? foreach ( users_helper::authButtons('login') as $button ): ?>
			<?=$button?>
		<? endforeach; ?>
	</div>

</section>

<? view::load('footer');
