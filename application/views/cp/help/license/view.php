<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-billing transaction-view">

	<table class="data <?=text_helper::alternate()?>">

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_owner">
			<td class="name">
				<?=__('license_owner', 'system_license')?>
			</td>
			<td class="value">
				<?=text_helper::entities($license['owner'])?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_key">
			<td class="name">
				<?=__('license_key', 'system_license')?>
			</td>
			<td class="value">
				<?=$license['license']?> - <?=html_helper::anchor('cp/help/license/change', __('license_change', 'system_license'))?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_key">
			<td class="name">
				<?=__('status', 'system')?>
			</td>
			<td class="value">
				<span class="label <?=($license['status'] == 'Active' ? 'success' : 'important')?>">
					<?=$license['status']?>
				</span>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_ip">
			<td class="name">
				<?=__('license_ip', 'system_license')?>
			</td>
			<td class="value">
				<?=$license['ip']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_domain">
			<td class="name">
				<?=__('license_domain', 'system_license')?>
			</td>
			<td class="value">
				<?=$license['domain']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_license_folder">
			<td class="name">
				<?=__('license_folder', 'system_license')?>
			</td>
			<td class="value">
				<?=$license['folder']?>
			</td>
		</tr>

	</table>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
