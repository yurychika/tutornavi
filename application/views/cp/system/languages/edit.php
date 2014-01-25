<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system languages-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_language_name">

				<label for="input_edit_language_name">
					<?=__('name', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'field' => array(
							'keyword' => 'name',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'error' => false,
						'value' => $language,
					)) ?>
					<?=form_helper::error('name')?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_language_keyword" <?=($keyword == 'english' ? 'style="display:none"' : '')?>>

				<label for="input_edit_language_keyword">
					<?=__('keyword', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'field' => array(
							'keyword' => 'keyword',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'error' => false,
						'value' => $language,
					)) ?>
					<?=form_helper::error('keyword')?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_language' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
