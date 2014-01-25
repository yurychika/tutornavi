<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-messages template-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_name">

				<label for="input_edit_template_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'template',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
								),
								'error' => false,
								'value' => $template,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_subject">

				<label for="input_edit_template_subject">
					<?=__('template_subject', 'messages_templates')?> <span class="required">*</span>
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

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_message">

				<label for="input_edit_template_message">
					<?=__('template_message', 'messages_templates')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'template',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'message',
									'type' => 'textarea',
									'multilang' => true,
								),
								'error' => false,
								'value' => $template,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('message_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_active">

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
