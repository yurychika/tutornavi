<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-blogs blog-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form break <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_blog_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">

						<label for="input_edit_blog_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'blog',
								'field' => $field,
								'value' => $blog,
							)) ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

		</fieldset>

		<? if ( $privacy ): ?>

			<fieldset class="form break grid <?=text_helper::alternate()?>">

				<? view::load('system/elements/field/section', array('name' => __('options_privacy', 'system'), 'type' => 'privacy')) ?>

				<? foreach ( $privacy as $option ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_blog_<?=$option['keyword']?>">

						<label for="input_edit_blog_<?=$option['keyword']?>">
							<?=text_helper::entities($option['name'])?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'blog',
								'field' => $option,
								'value' => isset($blog[$option['keyword']]) ? $blog : $option,
							)) ?>

						</div>

					</div>

				<? endforeach; ?>

			</fieldset>

		<? endif; ?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? view::load('system/elements/field/section', array('name' => __('options_general', 'system'), 'type' => 'options')) ?>

			<? foreach ( $options as $option ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_blog_<?=$option['keyword']?>">

					<label for="input_edit_blog_<?=$option['keyword']?>">
						<?=text_helper::entities($option['name'])?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'blog',
							'field' => $option,
							'value' => isset($blog[$option['keyword']]) ? $blog : $option,
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_blog' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
