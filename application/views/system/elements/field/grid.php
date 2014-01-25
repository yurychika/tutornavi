<? $section = false; ?>

<? foreach ( $fields as $index => $field ): ?>

	<? if ( isset($overview) && $overview && !$section && $field['type'] == 'section' ): $section = true; ?>
			<dd class="toggle-more">
				<?=html_helper::anchor('', __('show_full_grid', 'system'), array('onclick' => "\$('#grid_details_".$field['keyword']."').toggle();return false;"))?>
			</dd>
		</dl>
		<dl class="content-grid" id="grid_details_<?=$field['keyword']?>" style="display:none">
	<? endif; ?>

	<? if ( $field['type'] == 'section' ): ?>

		<dt class="legend">
			<span><?=$field['name']?></span>
		</dt>

	<? elseif ( !isset($skip) || !in_array($field['keyword'], $skip) ): ?>

		<? if ( !empty($data['data_'.$field['keyword']]) ): ?>

			<dt class="<?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?>">
				<?=$field['name']?>
			</dt>
			<dd class="<?=( isset($field['class']) && $field['class'] ? $field['class'] : '' )?>">

				<? if ( isset($data['data_'.$field['keyword']]) ): ?>

					<? if ( $field['type'] == 'location' ): ?>

						<? if ( isset($data['data_'.$field['keyword'].'_city']) && $data['data_'.$field['keyword'].'_city'] ): ?>
							<?=current($data['data_'.$field['keyword'].'_city'])?>,
						<? endif; ?>

						<? if ( isset($data['data_'.$field['keyword'].'_state']) && $data['data_'.$field['keyword'].'_state'] ): ?>
							<?=current($data['data_'.$field['keyword'].'_state'])?>,
						<? endif; ?>

						<?=current($data['data_'.$field['keyword']])?>

					<? elseif ( is_array($data['data_'.$field['keyword']]) ): ?>

						<?=implode(', ', $data['data_'.$field['keyword']])?>

					<? elseif ( $field['type'] == 'birthday' ): ?>

						<?=date_helper::getYearsDiff($data['data_'.$field['keyword']])?>

					<? elseif ( $field['type'] == 'website' ): ?>

						<?=html_helper::anchor($data['data_'.$field['keyword']], $data['data_'.$field['keyword']], array('target' => '_blank'))?>

					<? elseif ( $field['type'] == 'textarea' ): ?>

						<?=nl2br($data['data_'.$field['keyword']])?>

					<? else: ?>

						<?=$data['data_'.$field['keyword']]?>

					<? endif; ?>

				<? endif; ?>

			</dd>

		<? endif; ?>

	<? endif; ?>

<? endforeach; ?>
