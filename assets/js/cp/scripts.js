$(function(){
	$('ul.gallery > li').hover(
		function() {
			$('.actions', this).show();
			$('.check', this).show();
			return false;
		},
		function() {
			$('.actions', this).hide();
			$('.check', this).not('.checked').hide();
			return false;
		}
	);

	$('ul.gallery div.check').click(function(e){
		$(this).siblings('div.image').click();
	});

	$('ul.gallery div.image').click(
		function(e) {
			e.preventDefault();
			var checkbox = $('input.checkbox', $(this).parent());
			if ( checkbox.attr('checked') )
			{
				checkbox.removeAttr('checked');
				$(this).siblings('.check').removeClass('checked');
			}
			else
			{
				checkbox.attr('checked', 'checked');
				$(this).siblings('.check').addClass('checked');
			}
			return true;
	});
});

function removeLink(question, container, url, reorder)
{
	if ( confirm(question) == true ) {
		$.post(url, {},
			function(response) {
				$('#'+container).remove();
			}
		);
	}

	if ( typeof(reorder) != 'undefined' && reorder ) {
		applyOrder();
	}

	return false;
}
