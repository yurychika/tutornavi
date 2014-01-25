<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-reports reports-actions">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_report_action">

				<label for="input_edit_report_action">
					<?=__('report_action_select', 'reports')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'actions',
						'field' => array(
							'keyword' => 'action',
							'type' => 'select',
							'items' => $actions
						),
						'value' => array(),
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_report_delete">

				<label for="input_edit_report_actions">
					<?=__('report_delete', 'reports')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'actions',
						'field' => array(
							'keyword' => 'dismiss',
							'type' => 'boolean',
						),
						'value' => array(),
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_apply_actions' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer');
