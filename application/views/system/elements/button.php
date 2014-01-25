<?=form_helper::submit(
	isset($name) ? $name : 'submit',
	isset($value) ? $value : __('submit', 'system'),
	array(
		'onclick' => ( isset($onclick) ? $onclick : '' ),
		'class' => 'button submit '.(isset($class) ? $class : ''),
	)
)?>
