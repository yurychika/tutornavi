<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system metatags-edit">

	<script type="text/javascript">var currentTab = '';</script>

	<?=form_helper::openForm('', array('data-role' => 'tabs-frames'))?>

		<? $prefixes = array(); foreach ( $tags as $keyword => $group ): $prefixes[] = 'input_edit_meta_tag_'.$keyword; ?>

			<fieldset class="form grid <?=text_helper::alternate()?>" data-role="frame" data-frame="<?=$keyword?>">

				<? foreach ( array('title', 'description', 'keywords') as $index ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_meta_tag_<?=$keyword?>_<?=$index?>">

						<label for="input_edit_meta_tag_<?=$keyword?>_<?=$index?>">
							<?=__('meta_'.$index, 'system_metatags')?>
						</label>

						<div class="field">

							<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
								<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
									<? view::load('system/elements/field/edit', array(
										'prefix' => 'meta_tag',
										'name_suffix' => '_' . $language,
										'language' => $language,
										'field' => array(
											'keyword' => $keyword.'_'.$index,
											'type' => 'text',
											'multilang' => true,
										),
										'error' => false,
										'value' => array($keyword.'_'.$index.'_'.$language => $group['meta_'.$index.'_'.$language]),
									)) ?>
								</div>
							<? endforeach; ?>
							<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
								<?=form_helper::error($keyword.'_'.$index.'_'.$language)?>
							<? endforeach; ?>

						</div>

					</div>

				<? endforeach; ?>

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
				</div>

			</fieldset>

		<? endforeach; ?>

	<?=form_helper::closeForm(array('do_save_meta_tags' => 1))?>

</section>

<script type="text/javascript">
$(function(){
	$("#tabs ul").tabs({current:currentTab});
});
</script>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer'); ?>
