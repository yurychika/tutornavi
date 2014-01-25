<?=form_helper::openForm()?>

	<table class="grid">
		<tr>
			<th><?=__('name', 'system')?></th>
		</tr>
		<tr class="plain">
			<td>
				<ul id="plans_sortable" class="unstyled sortable-grid">
					<? foreach ( $fields as $field ): ?>
						<li class="<?=$field['type']?>">
							<span class="handle"></span>
							<?=text_helper::entities($field['name'])?>
							<?=form_helper::hidden('field[]', $field['field_id'], array('class' => 'field_ids'))?>
						</li>
					<? endforeach; ?>
				</ul>
			</td>
		</tr>
	</table>

<?=form_helper::closeForm(array('do_save_order' => 1))?>
