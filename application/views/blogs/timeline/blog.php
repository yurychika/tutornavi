<div class="item-article">

	<div class="action-header">
		<?=__('blog_post', 'timeline', array('[name]' => users_helper::anchor($user)))?>
	</div>

	<div class="target-article article">
		<div class="target-header">
			<?=html_helper::anchor('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), $blog['data_title'])?>
		</div>
		<?=text_helper::truncate($blog['data_body'], 310)?>
	</div>

</div>
