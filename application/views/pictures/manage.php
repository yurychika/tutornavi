<? view::load('header'); ?>

<section class="plugin-pictures pictures-manage">

	<? if ( $pictures ): ?>

		<?=form_helper::openForm()?>

			<ul class="unstyled content-list <?=text_helper::alternate()?>">

				<? foreach ( $pictures as $picture ): ?>

					<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-picture-<?=$picture['picture_id']?>">

						<article class="item">

							<figure class="image pictures-image">
								<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't', $picture['file_modify_date'])?>');">
									<?=html_helper::anchor(storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext']), '<span class="name">'.$picture['data_description'].'</span>', array('data-role' => 'modal', 'class' => 'image'))?>
								</div>
							</figure>

							<fieldset class="form <?=text_helper::alternate()?>">

								<? foreach ( $fields as $field ): ?>

									<? if ( $field['keyword'] == 'description' ) $field['type'] = 'textarea'; ?>
									<? $keyword = (isset($field['system']) ? 'data_' : '').$field['keyword']; $field['keyword'] .= '_'.$picture['picture_id']; ?>

									<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_<?=(isset($field['system']) ? 'data_' : '')?><?=$field['keyword']?>">

										<label for="input_edit_picture_<?=(isset($field['system']) ? 'data_' : '')?><?=$field['keyword']?>">
											<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
										</label>

										<div class="field">

											<? view::load('system/elements/field/edit', array(
												'prefix' => 'picture',
												'field' => $field,
												'value' => array((isset($field['system']) ? 'data_' : '').$field['keyword'] => $picture[$keyword]),
											)) ?>

										</div>

									</div>

								<? endforeach; ?>

								<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_picture_delete">

									<div class="field">

										<? view::load('system/elements/field/edit', array(
											'prefix' => 'picture',
											'field' => array(
												'name' => __('picture_delete_check', 'pictures'),
												'keyword' => 'delete_'.$picture['picture_id'],
												'type' => 'checkmark',
											),
											'value' => '',
										)) ?>

									</div>

								</div>

							</fieldset>

						</article>

					</li>

				<? endforeach; ?>

			</ul>

			<fieldset class="form">

				<div class="row actions">
					<? view::load('system/elements/button', array('value' => __('update', 'system'))); ?>
				&nbsp;
				<?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('cancel', 'system'))?>
				</div>

			</fieldset>

		<?=form_helper::closeForm(array('do_save_pictures' => 1))?>

	<? endif; ?>

</section>

<? view::load('footer');
