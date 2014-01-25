<?=form_helper::openForm('', array('class' => 'break', 'id' => $type.'-search', 'style' => ( !isset($show) || !$show ? 'display: none' : '' )))?>

	<fieldset class="form <?=( !isset($grid) || $grid ? 'grid' : '' )?> <?=text_helper::alternate()?>">

		<? foreach ( $fields as $index => $field ): ?>

			<? if ( isset($field['type']) && $field['type'] == 'section' ): ?>

				<? view::load('system/elements/field/section', array('name' => text_helper::entities($field['name']))); ?>

			<? elseif ( is_numeric($index) ): ?>

				<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_<?=$type?>_<?=( isset($field['system']) ? 'data_' : '' )?><?=( isset($field['category_id']) && $field['category_id'] ? $field['category_id'].'_' : '' )?><?=$field['keyword']?>">

					<label for="input_search_<?=$type?>_<?=( isset($field['system']) ? 'data_' : '' )?><?=( isset($field['category_id']) && $field['category_id'] ? $field['category_id'].'_' : '' )?><?=$field['keyword']?>">
						<?=text_helper::entities($field['name'])?>
					</label>
					<div class="field">
						<? view::load('system/elements/field/search', array(
							'prefix' => $type,
							'field' => $field,
							'value' => isset($values) ? $values : array(),
						)) ?>
					</div>

				</div>

			<? elseif ( isset($fields['types']) && $fields['types'] ): ?>

				<? foreach ( $fields['types'] as $typeID => $types ): ?>

					<fieldset class="form grid search-types" id="search-types-<?=$typeID?>" style="display: none;">

						<? foreach ( $types as $tfield ): ?>

							<? if ( $tfield['type'] == 'section' ): ?>

								<? view::load('system/elements/field/section', array('name' => text_helper::entities($tfield['name']))); ?>

							<? else: ?>

								<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_<?=$type?>_<?=( isset($tfield['category_id']) && $tfield['category_id'] ? $tfield['category_id'].'_' : '' )?><?=$tfield['keyword']?>">

									<label for="input_search_<?=$type?>_<?=( isset($tfield['system']) ? 'data_' : '' )?><?=( isset($tfield['category_id']) && $tfield['category_id'] ? $tfield['category_id'].'_' : '' )?><?=$tfield['keyword']?>">
										<?=text_helper::entities($tfield['name'])?>
									</label>
									<div class="field">
										<? view::load('system/elements/field/search', array(
											'prefix' => $type,
											'field' => $tfield,
											'value' => isset($values) ? $values : array(),
										)) ?>
									</div>

								</div>

							<? endif; ?>

						<? endforeach; ?>

					</fieldset>

				<? endforeach; ?>

			<? endif; ?>

		<? endforeach; ?>

		<div class="row actions">
			<? view::load('system/elements/button', isset($button) && is_array($button) ? $button : array()); ?>
		</div>

	</fieldset>

<?=form_helper::closeForm(array('do_search' => 1))?>
