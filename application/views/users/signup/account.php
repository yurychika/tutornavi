<? view::load('header'); ?>

<section class="plugin-users signup-account">

	<? if ( session::item('connection', 'remote_connect') || config::item('auth_methods', 'users', 'default') ): ?>

		<?=form_helper::openForm()?>

			<fieldset class="form grid <?=text_helper::alternate()?>">

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_email" <?=(session::item('account', 'remote_connect', 'email') && !validate::getErrors('email') ? 'style="display:none"' : '')?>>

					<label for="input_edit_user_signup_email">
						<?=__('email', 'users')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_signup',
							'field' => array(
								'keyword' => 'email',
								'type' => 'text',
								'class' => 'input-xlarge',
							),
							'value' => array('email' => session::item('account', 'signup', 'email')),
						)) ?>

					</div>

				</div>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_password" <?=(session::item('connection', 'remote_connect') ? 'style="display:none"' : '')?>>

					<label for="input_edit_user_signup_password">
						<?=__('password', 'users')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_signup',
							'field' => array(
								'keyword' => 'password',
								'type' => 'password',
								'maxlength' => 128,
							),
							'value' => array('password' => session::item('account', 'signup', 'password')),
						)) ?>

					</div>

				</div>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_password2" <?=(session::item('connection', 'remote_connect') ? 'style="display:none"' : '')?>>

					<label for="input_edit_user_signup_password2">
						<?=__('password_confirm', 'users')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_signup',
							'field' => array(
								'keyword' => 'password2',
								'type' => 'password',
								'maxlength' => 128,
							),
							'value' => array('password2' => session::item('account', 'signup', 'password')),
						)) ?>

					</div>

				</div>

				<? if ( config::item('user_username', 'users') ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_username">

						<label for="input_edit_user_signup_username">
							<?=__('username', 'users')?> <span class="required">*</span>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_signup',
								'field' => array(
									'keyword' => 'username',
									'type' => 'text',
									'maxlength' => 128,
									'class' => 'input-xlarge',
								),
								'value' => array('username' => session::item('account', 'signup', 'username')),
							)) ?>

						</div>

					</div>

				<? endif; ?>

				<? if ( count(config::item('usertypes', 'core', 'names')) > 1 ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_type_id">

						<label for="input_edit_user_signup_type_id">
							<?=__('user_type', 'users')?> <span class="required">*</span>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_signup',
								'field' => array(
									'keyword' => 'type_id',
									'type' => 'select',
									'select' => __('select', 'system'),
									'items' => config::item('usertypes', 'core', 'names'),
								),
								'value' => array('type_id' => session::item('account', 'signup', 'type_id')),
							)) ?>

						</div>

					</div>

				<? endif; ?>

				<? if ( !session::item('connection', 'remote_connect') && config::item('signup_captcha', 'users') ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_edit_captcha">

						<label for="input_edit_user_edit_captcha">
							<?=__('captcha', 'system')?> <span class="required">*</span>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_edit',
								'field' => array(
									'keyword' => 'captcha',
									'type' => 'captcha',
								),
								'value' => '',
							)) ?>

						</div>

					</div>

				<? endif; ?>

				<? if ( config::item('signup_require_terms', 'users') ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_signup_terms">

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_signup',
								'field' => array(
									'name' => __('terms_accept', 'users_signup', array(), array('%' => html_helper::anchor('site/terms', '\1', array('target' => '_blank')))),
									'keyword' => 'terms',
									'plain' => true,
									'type' => 'checkmark',
								),
								'value' => array('terms' => session::item('account', 'signup') ? 1 : 0),
							)) ?>

						</div>

					</div>

				<? endif; ?>

				<div class="row actions">
					<? view::load('system/elements/button', array('value' => __('next', 'system'))); ?>
				</div>

			</fieldset>

		<?=form_helper::closeForm(array('do_save_account' => 1))?>

	<? endif; ?>

	<? if ( !session::item('connection', 'remote_connect') ): ?>

		<div class="remote-connect <?=(config::item('auth_methods', 'users', 'default') && count(config::item('auth_methods', 'users')) > 1 ? 'extra' : '')?>">
			<? foreach ( users_helper::authButtons('signup') as $button ): ?>
				<?=$button?>
			<? endforeach; ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
