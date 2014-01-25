<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-reports subject-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_subject_name">

				<label for="input_edit_subject_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'subject',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
								),
								'error' => false,
								'value' => $subject,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_subject_active">

				<label for="input_edit_subject_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'subject',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $subject,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_subject' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>


<? view::load('cp/system/elements/template/footer');
