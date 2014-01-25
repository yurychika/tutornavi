<? if ( isset($name) && $name ): ?>
	<dt class="name"><?=__('name', 'system')?></dt>
	<dd class="name"><?=$user['name']?><?=($user['group_id'] == config::item('group_cancelled_id', 'users') || !$user['verified'] || !$user['active'] ? ' ('.__('account_inactive', 'users').')' : '')?></dd>
<? endif; ?>

<? if ( $user['group_id'] != config::item('group_cancelled_id', 'users') && $user['verified'] && $user['active'] ): ?>
	<? view::load('system/elements/field/grid', array('skip' => array(config::item('usertypes', 'core', 'fields', $user['type_id'], 1), config::item('usertypes', 'core', 'fields', $user['type_id'], 2)), 'fields' => $fields, 'data' => $user, 'overview' => isset($overview) ? $overview : false)); ?>
<? endif; ?>
