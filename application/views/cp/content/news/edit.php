<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-news news-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form break <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_news_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">

						<label for="input_edit_news_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? if ( isset($field['multilang']) && $field['multilang'] ): ?>
								<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
									<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
										<? view::load('system/elements/field/edit', array(
											'prefix' => 'news',
											'name_suffix' => '_' . $language,
											'language' => $language,
											'field' => $field,
											'error' => false,
											'value' => $news,
										)) ?>
									</div>
								<? endforeach; ?>
								<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
									<?=form_helper::error('data_'.$field['keyword'].'_'.$language)?>
								<? endforeach; ?>
							<? else: ?>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'news',
									'field' => $field,
									'value' => $news,
								)) ?>
							<? endif; ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

		</fieldset>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? view::load('system/elements/field/section', array('name' => __('options_general', 'system'), 'type' => 'options')) ?>

			<? foreach ( $options as $option ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_news_<?=$option['keyword']?>">

					<label for="input_edit_news_<?=$option['keyword']?>">
						<?=text_helper::entities($option['name'])?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'news',
							'field' => $option,
							'value' => isset($news[$option['keyword']]) ? $news : $option,
						)) ?>

					</div>

				</div>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_news' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
