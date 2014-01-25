<? view::load('header'); ?>

<section class="plugin-users login-lostpass">

	<?=form_helper::openForm()?>

		<fieldset class="form grid">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_lostpass_email">

				<label for="input_edit_user_lostpass_email">
					<?=__('email', 'users')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_lostpass',
						'field' => array(
							'keyword' => 'email',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
				&nbsp;
				<?=html_helper::anchor('users/login', __('cancel', 'system'))?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_lost_pass' => 1))?>

</section>

<? view::load('footer');
