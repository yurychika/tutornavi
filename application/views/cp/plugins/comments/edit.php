<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-comments comment-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_page_<?=$field['keyword']?>">

						<label for="input_edit_page_<?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'page',
								'field' => $field,
								'value' => $comment,
							)) ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_comment' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
