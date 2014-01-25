<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users group-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_group_name">

				<label for="input_edit_user_group_name">
					<?=__('name', 'system')?>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_group',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
									'class' => 'input-xlarge',
								),
								'error' => false,
								'value' => $group,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<? if ( !$groupID ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_user_group_copy_id">

					<label for="input_edit_user_group_copy_id">
						<?=__('group_copy', 'users_groups')?>
					</label>

					<div class="field">

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_group',
							'field' => array(
								'keyword' => 'copy',
								'type' => 'select',
								'items' => $groups,
							),
							'value' => '',
						)) ?>

					</div>

				</div>

			<? endif; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_group' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
