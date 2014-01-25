<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users users-browse">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'users', 'fields' => $filters, 'values' => $values)); ?>
		<script type="text/javascript">
			$(function(){
				$('#input_search_users_type_id').change(function() {
					$('.search-types').hide();
					$('#search-types-'+this.value).show();
				});
				if ( $('#input_search_users_type_id').val() )
				{
					$('#search-types-'+$('#input_search_users_type_id').val()).show();
				}
			});
		</script>
	<? endif; ?>

	<? view::load('cp/system/elements/grid', array('grid' => $grid, 'qstring' => $qstring, 'actions' => $actions, 'pagination' => $pagination)); ?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
