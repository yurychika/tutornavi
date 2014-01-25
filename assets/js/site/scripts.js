$(document).ready(function() {
	$('form').submit(function() {
		var error = '';

		// step5
		if (window.location.href.indexOf('users/signup/step5') >= 0) {
			if ($('#sec_code').val() !== $('#sec_code_input').val()) {
				error = false;
				alert('Wrong security code');
				return false;
			} else if (!$('#agree_term').is(':checked')) {
				error = false;
				alert('You must agree with the term and condition');
				return false;
			}
		}
		// step4
		if (window.location.href.indexOf('users/signup/step4') >= 0) {
			if(!validateLogin()){
				return false;
			}
		}
		
		return validate(this);
	
	});

});

function validateLogin(){
	if($('#username').val().length < 6){
		alert('Please confirm nickname has at least 6 characters');
		return false;
	}
	if ($('input#password').val() !== $('input#password2').val()) {
		alert('Please confirm password is the same!');
		return false;
	}
	if ($('input#password').val().length < 6) {
		alert('Please make sure password length is 6 minimum!');
		return false;
	}	
	return true;
}

function validate(node){
	var node = node? node: $('form')[0], error;
	$('input, textarea, select', node).each(function() {
		// console.log(this.value);
		//fields not empty
		if(!$(this).hasClass('noreq')){
			if (this.value === '') {
				console.log(this);
				error = 'empty';
				return;
			}
		}
		
		//validate numeric
		if($(this).hasClass('numeric') && this.value && !/^\d+$/.test(this.value)){
			error = 'phone';
			return;
		}
	});
	
	if (error) {
		switch(error){
			case 'empty':
				alert('Fields can not be empty');
				break;
			case 'phone':
				alert('Phonenumber must be numeric value!');
				break;
				
		}
		return false;
	}
	return true;
}
