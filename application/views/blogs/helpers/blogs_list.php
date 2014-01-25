<? if ( $blogs ): ?>

	<div class="content-box helper-blogs blogs-list">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('blogs/user/'.$user['slug_id'], __('blogs', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('blogs/user/'.$user['slug_id'], __('blogs_num'.($user['total_blogs'] == 1 ? '_one' : ''), 'system_info', array('%blogs' => $user['total_blogs'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled item-list icon-list narrow arrow <?=text_helper::alternate()?>">

			<? foreach ( $blogs as $blog ): ?>

				<li class="<?=text_helper::alternate('odd','even')?> nowrap nooverflow" id="row-helper-blog-<?=$blog['blog_id']?>">
					<?=html_helper::anchor('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), $blog['data_title'], array('title' => $blog['data_title']))?>
				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
