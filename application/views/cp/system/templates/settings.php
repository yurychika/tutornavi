<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system template-settings">

	<script type="text/javascript">var currentTab = '';</script>

	<?=form_helper::openForm('', array('data-role' => 'tabs-frames'))?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<? foreach ( $manifest['settings'] as $groupID => $group ): ?>

				<? foreach ( $group['settings'] as $settingID => $setting ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_template_setting_<?=$groupID?>" data-role="frame" data-frame="<?=$groupID?>">

						<label for="input_edit_template_setting_<?=$setting['keyword']?>">
							<?=text_helper::entities($setting['name'])?> <? if ( isset($setting['required']) && $setting['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => 'template_setting',
								'field' => $setting,
								'value' => $template['settings'],
								'error' => false,
							)) ?>

							<? if ( form_helper::error($setting['keyword']) ): ?>
								<?=form_helper::error($setting['keyword'])?>
								<script type="text/javascript">var currentTab = '<?=$groupID?>';</script>
							<? endif; ?>

						</div>

					</div>

				<? endforeach; ?>

			<? endforeach; ?>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</div>

	<?=form_helper::closeForm(array('do_save_settings' => 1))?>

</section>

<script type="text/javascript">
$(function(){
	$("#tabs ul").tabs({current:currentTab});
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
