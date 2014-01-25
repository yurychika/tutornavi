<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system email-template-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<? if ( !in_array($template['keyword'], array('header', 'footer')) ): ?>
				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_subject">

					<label for="input_edit_template_subject">
						<?=__('template_subject', 'system_email_templates')?> <span class="required">*</span>
					</label>

					<div class="field">

						<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
							<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'template',
									'name_suffix' => '_' . $language,
									'language' => $language,
									'field' => array(
										'keyword' => 'subject',
										'type' => 'text',
										'multilang' => true,
									),
									'error' => false,
									'value' => $template,
								)) ?>
							</div>
						<? endforeach; ?>
						<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
							<?=form_helper::error('subject_'.$language)?>
						<? endforeach; ?>

					</div>

				</div>
			<? endif; ?>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_message_html">

				<label for="input_edit_template_message_html">
					<?=__('template_message_html', 'system_email_templates')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'template',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'message_html',
									'type' => 'textarea',
									'multilang' => true,
									'config' => array(
										'html' => true,
									),
								),
								'error' => false,
								'value' => $template,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('message_html_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_message_text">

				<label for="input_edit_template_message_text">
					<?=__('template_message_text', 'system_email_templates')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'template',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'message_text',
									'type' => 'textarea',
									'multilang' => true,
								),
								'error' => false,
								'value' => $template,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('message_text_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_active" <?=($template['keyword'] == 'header' || $template['keyword'] == 'footer' ? 'style="display:none"' : '')?>>

				<label for="input_edit_template_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'template',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $template,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_template' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
