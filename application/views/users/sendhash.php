<? view::load('header'); ?>

<section class="plugin-users signup-sendhash">

	<?=form_helper::openForm()?>

		<fieldset class="form grid">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_sendhash_email">

				<label for="input_edit_user_sendhash_email">
					<?=__('email', 'users')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'user_sendhash',
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

	<?=form_helper::closeForm(array('do_send_hash' => 1))?>

</section>

<? view::load('footer');
