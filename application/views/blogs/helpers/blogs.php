<? if ( $blogs ): ?>

	<div class="content-box helper-blogs">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('blogs/user/'.$user['slug_id'], __('blogs', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('blogs/user/'.$user['slug_id'], __('blogs_num'.($user['total_blogs'] == 1 ? '_one' : ''), 'system_info', array('%blogs' => $user['total_blogs'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $blogs as $blog ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-blog-<?=$blog['blog_id']?>">

					<article class="item">

						<figure class="image users-image">
							<? view::load('users/profile/elements/picture', array_merge($blog['user'], array('picture_file_suffix' => 't'))); ?>
						</figure>

						<header class="item-header">
							<h3>
								<?=html_helper::anchor('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), $blog['data_title'])?>
							</h3>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($blog['data_body'], isset($params['truncate']) && $params['truncate'] ? $params['truncate'] : config::item('blogs_preview_chars', 'blogs'))?>
							<?=html_helper::anchor('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), '&raquo;')?>
						</div>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
