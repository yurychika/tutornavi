<? view::load('header'); ?>

<section class="plugin-users search-index">

	<? view::load('system/elements/search', array('type' => 'users', 'fields' => $filters, 'values' => $values, 'show' => true)); ?>

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

</section>

<? view::load('footer');
