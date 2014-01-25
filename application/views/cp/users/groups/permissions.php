<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users group-permissions">

	<script type="text/javascript">var currentTab = window.location.hash ? window.location.hash.slice(1) : false;</script>

	<?=form_helper::openForm('', array('data-role' => 'tabs-frames'))?>

		<? foreach ( current($permissions) as $section => $data ): ?>

			<fieldset class="form grid <?=text_helper::alternate()?>" data-role="frame" data-frame="<?=$section?>">

				<? if ( count($groups) > 1 ): ?>

						<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_permission_group_name">

							<label for="input_edit_permission_group_name">
								&nbsp;
							</label>

							<div class="field">

								<table style="width:100%">
									<tr>
										<?  foreach ( $groups as $groupID => $group ): ?>
											<td style="width:<?=floor(100/count($permissions))?>%">
												<?=$group['name']?>
											</td>
										<? endforeach; ?>
									</tr>
								</table>

							</div>

						</div>

				<? endif; ?>

				<? foreach ( $data as $permissionID => $permission ): ?>

					<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_permission_<?=$permission['keyword']?>">

						<label for="input_edit_permission_<?=$permission['keyword']?>">
							<?=text_helper::entities($permission['name'])?> <? if ( isset($permission['required']) && $permission['required'] ): ?><span class="required">*</span><? endif; ?>
						</label>

						<div class="field" <?=(config::item('devmode', 'system') == 2 ? 'style="position:relative"' : '')?>>

							<table style="width:100%">
								<tr>
									<?  foreach ( $permissions as $groupID => $group ): ?>
										<td style="width:<?=floor(100/count($permissions))?>%">

											<? if ( $section == 'cp' && !$groups[$groupID]['cp'] || !$permission['guests'] && $groupID == config::item('group_guests_id', 'users') ) : ?>

												<span class="icon icon-system-cancel"></span>

											<? else: ?>

												<? view::load('system/elements/field/edit', array(
													'name_suffix' => '_' . $groupID,
													'field' => $group[$section][$permissionID],
													'value' => array($group[$section][$permissionID]['keyword'] . '_' . $groupID => $group[$section][$permissionID]['value']),
													'error' => false,
												)) ?>

											<? endif; ?>

										</td>
									<? endforeach; ?>

								</tr>
							</table>

							<? if ( config::item('devmode', 'system') == 2 ): ?>
								<?=form_helper::text($permission['keyword'].'___order', $permission['order_id'], array('class' => 'text', 'style' => 'width:25px;position:absolute;right:-55px;top:0;')) ?>
								<?=form_helper::text($permission['keyword'].'___key', $permission['keyword'], array('class' => 'text', 'style' => 'width:130px;position:absolute;right:-200px;top:0;')) ?>
							<? endif; ?>

						</div>
					</div>

				<? endforeach; ?>

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
				</div>

			</fieldset>

		<? endforeach; ?>

	<?=form_helper::closeForm(array('do_save_permissions' => 1))?>

</section>

<script type="text/javascript">
$(function(){
	$("#tabs ul").tabs();
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
