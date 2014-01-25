<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-timeline message-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_message_edit_message">

				<label for="input_edit_message_edit_message">
					<?=__('message', 'timeline')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'message_edit',
						'field' => array(
							'keyword' => 'message',
							'type' => 'textarea',
						),
						'value' => $message,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_message' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
