<? if ( $pagination->getTotalPages() > 1 ): ?>

	<div class="pagination clearfix">
		<span class="blurb">
			<?=$pagination->getBlurb()?>
		</span>
		<ul class="unstyled links">
			<?=($pagination->getFirstPage() != 1 ? '<li>'.$pagination->getFirstPageLink().'</li>' : '')?>
			<?=$pagination->getLinks('<li>', '</li>')?>
			<?=($pagination->getLastPage() != $pagination->getTotalPages() ? '<li>'.$pagination->getLastPageLink().'</li>' : '')?>
		</ul>

		<? if ( isset($type) && $type ): ?>
			<span class="icon icon-system-ajax ajax hidden" id="pagination-<?=$type?><?=(isset($item) ? '-'.$item : '')?>"></span>
		<? endif; ?>
	</div>

<? endif; ?>
