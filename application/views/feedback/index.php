<? view::load('header'); ?>

<section class="plugin-feedback feedback-index">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_feedback_edit_name">

				<label for="input_edit_feedback_edit_name">
					<?=__('name', 'feedback')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'feedback_edit',
						'field' => array(
							'keyword' => 'name',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_feedback_edit_email">

				<label for="input_edit_feedback_edit_email">
					<?=__('email', 'feedback')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'feedback_edit',
						'field' => array(
							'keyword' => 'email',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_feedback_edit_subject">

				<label for="input_edit_feedback_edit_subject">
					<?=__('subject', 'feedback')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'feedback_edit',
						'field' => array(
							'keyword' => 'subject',
							'type' => 'text',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_feedback_edit_message">

				<label for="input_edit_feedback_edit_message">
					<?=__('message', 'feedback')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'feedback_edit',
						'field' => array(
							'keyword' => 'message',
							'type' => 'textarea',
							'class' => 'input-wide input-large-y',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<? if ( config::item('feedback_captcha', 'feedback') == 1 || config::item('feedback_captcha', 'feedback') == 2 && !users_helper::isLoggedin() ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_feedback_edit_captcha">

					<label for="input_edit_feedback_edit_captcha">
						<?=__('captcha', 'system')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'feedback_edit',
							'field' => array(
								'keyword' => 'captcha',
								'type' => 'captcha',
							),
							'value' => '',
						)) ?>

					</div>

				</div>

			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button', array('value' => __('send', 'system'))); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_send_feedback' => 1))?>

</section>

<? view::load('footer');
