<?=form_helper::openForm()?>

	<table class="grid">
		<tr>
			<th><?=__('name', 'system')?></th>
		</tr>
		<tr class="plain">
			<td>
				<ul id="items_sortable" class="unstyled sortable-grid">
					<? foreach ( $items as $item ): ?>
						<li>
							<span class="handle"></span>
							<?=$item['name']?>
							<?=form_helper::hidden('item[]', $item['item_id'], array('class' => 'item_ids'))?>
						</li>
					<? endforeach; ?>
				</ul>
			</td>
		</tr>
	</table>

<?=form_helper::closeForm(array('do_save_order' => 1))?>
