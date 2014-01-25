<? if ( $grid['content'] ): ?>

	<?=form_helper::openForm($grid['uri'] .
		( isset($qstring['url']) && $qstring['url'] || isset($qstring['page']) && $qstring['page'] ? '?' : '' ) .
		( isset($qstring['url']) && $qstring['url'] ? $qstring['url'] : '' ) .
		( isset($qstring['page']) && $qstring['page'] ? 'page=' . $qstring['page'] : '' ),
		array('id' => 'form_grid_' . $grid['keyword']))?>

		<table class="grid">

			<tr <?=text_helper::alternate()?>>

				<? foreach ( $grid['header'] as $keyword => $column ): ?>

					<th<?=( isset($column['id']) ? ' id="' . $column['id'] . '"' : '' )?><?=( isset($column['style']) ? ' style="' . $column['style'] . '"' : '' )?><?=( isset($column['class']) ? ' class="' . $column['class'] . '"' : '' )?>>

						<? if ( $keyword == 'check' ): ?>

							<?=form_helper::checkbox($column['html'] . '[]', 0, '',
								array('class' => 'checkbox', 'id' => 'input_edit_' . $column['html'] . '_0', 'onclick' => "\$('.item_" . $column['html'] . "_checkbox').prop('checked', this.checked)"))?>

						<? elseif ( isset($column['sortable']) && $column['sortable'] ): ?>

							<?=html_helper::anchor($grid['uri'] . '?' . ( isset($qstring['search_id']) && $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) . 'o=' . $keyword . '&d=' . ( $keyword == $qstring['orderby'] && $qstring['orderdir'] == 'asc' ? 'desc' : 'asc' ),
								$column['html'],
								array('class' => ( $keyword == $qstring['orderby'] && ( $qstring['orderdir'] == 'asc' || $qstring['orderdir'] == 'desc' ) ? $qstring['orderdir'] : '' )))?>

						<? else: ?>

							<?=$column['html']?>

						<? endif; ?>

					</th>

				<? endforeach; ?>

			</tr>

			<? foreach ( $grid['content'] as $row ): ?>

				<tr class="<?=text_helper::alternate('odd','even')?>">

					<? foreach ( $row as $keyword => $column ): ?>

						<td<?=( isset($grid['header'][$keyword]['class']) || isset($column['class']) ? ' class="' . ( isset($grid['header'][$keyword]['class']) ? $grid['header'][$keyword]['class'] : '' ) . ( isset($column['class']) ? ' ' . $column['class'] : '' ) . '"' : '' )?>>

							<? if ( $keyword == 'check' ): ?>

								<?=form_helper::checkbox($grid['header'][$keyword]['html'] . '[]', $column['html'], '',
									array('class' => 'checkbox item_' . $grid['header'][$keyword]['html'] . '_checkbox', 'id' => 'input_edit_' . $grid['header'][$keyword]['html'] . '_' . $column['html']))?>

							<? elseif ( $keyword == 'actions' && is_array($column['html']) ): ?>

								<ul class="unstyled actions">
									<? foreach ( $column['html'] as $class => $item ): ?>
										<li class="<?=$class?>"><?=$item?></li>
									<? endforeach; ?>
								</ul>

							<? else: ?>

								<?=$column['html']?>

							<? endif; ?>

						</td>

					<? endforeach; ?>

				</tr>

			<? endforeach; ?>

		</table>

		<? if ( isset($pagination) && $pagination || isset($actions) && $actions ): ?>

			<div class="footer-section clearfix">

				<? if ( isset($pagination) && $pagination ): ?>
					<? view::load('cp/system/elements/pagination', array('pagination' => $pagination)); ?>
				<? endif; ?>

				<? if ( isset($actions) && $actions ): ?>
					<? view::load('cp/system/elements/form-actions', array('form' => 'form_grid_' . $grid['keyword'], 'actions' => $actions)); ?>
				<? endif; ?>

			</div>

		<? endif; ?>

	<?=form_helper::closeForm(array('do_action' => 1))?>

<? endif;