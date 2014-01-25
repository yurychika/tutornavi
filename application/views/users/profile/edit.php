<? view::load('header'); ?>

<section class="plugin-users profile-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $fields as $field ): ?>

				<? if ( $field['type'] == 'section' ): ?>

					<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

				<? else: ?>

					<? if ( $field['type'] == 'select' ) $field['select'] = true; ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_profile_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">

						<label for="input_edit_user_profile_<?=( isset($field['system']) ? 'data_' : '' )?><?=$field['keyword']?>">
							<?=text_helper::entities($field['name'])?> <? if ( isset($field['required']) && $field['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $field,
								'value' => $profile,
							)) ?>

						</div>

					</div>

				<? endif; ?>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_profile' => 1))?>

</section>

<? view::load('footer');
