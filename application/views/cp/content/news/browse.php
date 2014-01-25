<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-news news-browse">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'news', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? view::load('cp/system/elements/grid', array('grid' => $grid, 'qstring' => $qstring, 'actions' => $actions, 'pagination' => $pagination)); ?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
