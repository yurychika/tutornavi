<? view::load('header'); ?>

<section class="homepage">

    <? if ( config::item('home_users', 'template') ): ?>
    <div class="home-box users">
        <h3><?= __('users_new', 'system_navigation') ?></h3>
        <?= users_helper::getUsers(array('join_columns' => array('`u`.`picture_id`!=0', '`u`.`picture_active`=1'), 'limit' => 10)) ?>
    </div>
    <div class="section-break"></div>
    <? endif; ?>

    <div class="clearfix">
        <? $i = 0; foreach ( array('blogs', 'news', 'pictures', 'videos') as $item ): ?>

        <? if ( config::item($item.'_active', $item) && config::item('home_'.$item, 'template') ): $i++; ?>

        <div class="home-box home-box-<?= ($i == 1 || $i == 3 ? 'left' : 'right') ?> <?= $item ?>">

            <h3><?= __($item . '_new', 'system_navigation') ?></h3>

            <? loader::helper($item.'/'.$item); ?>

            <? if ( $item == 'blogs' ): ?>
            <?= blogs_helper::getBlogs(array('join_columns' => array(), 'limit' => 5, 'truncate' => 200)) ?>
            <? elseif ( $item == 'news' ): ?>
            <?= news_helper::getNews(array('join_columns' => array(), 'limit' => 5, 'truncate' => 200)) ?>
            <? elseif ( $item == 'pictures' ): ?>
            <?= pictures_helper::getAlbums(array('join_columns' => array('`a`.`picture_id`!=0', '`a`.`total_pictures`>0'), 'limit' => 20)) ?>
            <? elseif ( $item == 'videos' ): ?>
            <?= videos_helper::getVideos(array('join_columns' => array(), 'limit' => 20)) ?>
            <? endif; ?>

        </div>

        <?= ($i == 2 ? '</div><div class="section-break"></div><div class="clearfix">' : '') ?>
        <? endif; ?>

        <? endforeach; ?>
    </div>

</section>

<? view::load('footer');
