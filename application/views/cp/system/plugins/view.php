<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system plugins-settings">

	<table class="data <?=text_helper::alternate()?>">

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_plugin_name">
			<td class="name">
				<?=__('name', 'system')?>
			</td>
			<td class="value">
				<?=$plugin['name']?>
			</td>
		</tr>

		<? if ( $manifest['description'] ): ?>
			<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_plugin_description">
				<td class="name">
					<?=__('description', 'system')?>
				</td>
				<td class="value">
					<?=nl2br($manifest['description'])?>
				</td>
			</tr>
		<? endif; ?>

		<? if ( $manifest['author'] || $manifest['website'] ): ?>
			<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_plugin_description">
				<td class="name">
					<?=__('plugin_author', 'system_plugins')?>
				</td>
				<td class="value">
					<? if ( $manifest['author'] && $manifest['website'] ): ?>
						<?=html_helper::anchor($manifest['website'], $manifest['author'], array('target' => '_blank'))?>
					<? elseif ( $manifest['website'] ): ?>
						<?=html_helper::anchor($manifest['website'], text_helper::entities(str_replace(array('http://www.', 'http://'), '', $manifest['website'])), array('target' => '_blank'))?>
					<? elseif ( $manifest['author'] ): ?>
						<?=$manifest['author']?>
					<? endif; ?>
				</td>
			</tr>
		<? endif; ?>

		<? if ( isset($plugin['version']) ): ?>
			<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_plugin_version">
				<td class="name">
					<?=__('plugin_version', 'system_plugins')?>
				</td>
				<td class="value">
					<?=$plugin['version']?>
					<? if ( $plugin['version'] < $manifest['version'] ): ?> -
						<?=__('plugin_new_version', 'system_plugins', array('%version' => $manifest['version']))?>
						<?=html_helper::anchor('cp/system/plugins/update/'.$plugin['keyword'], __('plugin_update', 'system_plugins'), array('class' => 'label success small'))?>
					<? endif; ?>
				</td>
			</tr>
		<? endif; ?>

	</table>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
