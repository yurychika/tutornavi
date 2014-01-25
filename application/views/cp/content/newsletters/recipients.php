<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-newsletters newsletter-recipients">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'users', 'fields' => $filters, 'values' => $values, 'show' => true, 'button' => array('value' => __('next', 'system')))); ?>
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

</section>

<? view::load('cp/system/elements/template/footer'); ?>
