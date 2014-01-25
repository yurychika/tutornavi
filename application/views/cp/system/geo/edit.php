<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system geo-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_name">

				<label for="input_edit_name">
					<? if ( isset($data) ): ?>
						<?=text_helper::entities($data['name'])?>
					<? elseif ( $stateID ): ?>
						<?=__('city_new', 'system_geo')?>
					<? elseif ( $countryID ): ?>
						<?=__('state_new', 'system_geo')?>
					<? else: ?>
						<?=__('country_new', 'system_geo')?>
					<? endif; ?>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'name',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
									'class' => 'input-xlarge',
								),
								'error' => false,
								'value' => isset($data) ? array('name_'.$language => $data['name_'.$language]) : array(),
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_geo' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
