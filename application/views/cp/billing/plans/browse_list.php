<?=form_helper::openForm()?>

	<table class="grid">
		<tr>
			<th><?=__('name', 'system')?></th>
		</tr>
		<tr class="plain">
			<td>
				<ul id="plans_sortable" class="unstyled sortable-grid">
					<? foreach ( $plans as $plan ): ?>
						<li>
							<span class="handle"></span>
							<?=$plan['name']?>
							<?=form_helper::hidden('plan[]', $plan['plan_id'], array('class' => 'plan_ids'))?>
						</li>
					<? endforeach; ?>
				</ul>
			</td>
		</tr>
	</table>

<?=form_helper::closeForm(array('do_save_order' => 1))?>
