<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-newsletters newsletter-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_subject">

				<label for="input_edit_newsletter_subject">
					<?=__('newsletter_subject', 'newsletters')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'newsletter',
						'field' => array(
							'keyword' => 'subject',
							'type' => 'text',
							'multilang' => true,
						),
						'error' => false,
						'value' => $newsletter,
					)) ?>
					<?=form_helper::error('subject')?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_message_html">

				<label for="input_edit_newsletter_message_html">
					<?=__('newsletter_message_html', 'newsletters')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'newsletter',
						'field' => array(
							'keyword' => 'message_html',
							'type' => 'textarea',
							'multilang' => true,
							'config' => array(
								'html' => true,
							),
						),
						'error' => false,
						'value' => $newsletter,
					)) ?>
					<?=form_helper::error('message_html')?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_message_text">

				<label for="input_edit_newsletter_message_text">
					<?=__('newsletter_message_text', 'newsletters')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'newsletter',
						'field' => array(
							'keyword' => 'message_text',
							'type' => 'textarea',
							'multilang' => true,
						),
						'error' => false,
						'value' => $newsletter,
					)) ?>
					<?=form_helper::error('message_text')?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button', array('value' => __('next', 'system'))); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_newsletter' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
