<? view::load('header'); ?>

<section class="plugin-reports report-index">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_report_edit_subject">

				<label for="input_edit_report_edit_subject">
					<?=__('report_subject', 'reports')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'report_edit',
						'field' => array(
							'keyword' => 'subject',
							'type' => 'select',
							'items' => $subjects,
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_report_edit_message">

				<label for="input_edit_report_edit_message">
					<?=__('report_message', 'reports')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'report_edit',
						'field' => array(
							'keyword' => 'message',
							'type' => 'textarea',
							'class' => 'input-wide',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_submit_report' => 1))?>

</section>

<? view::load('footer');
