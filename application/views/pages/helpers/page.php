<? if ( $page ): ?>

	<div class="content-box helper-page">

		<? if ( $title ): ?>
			<h2><?=$page['data_title']?></h2>
		<? endif; ?>

		<?=$page['data_body']?>

	</div>

<? endif; ?>
