var base_url = '[conf.config.base_url]';
var site_url = '[conf.config.site_url]';
var cache = {};

$(function(){

	initTooltips();

    $('[data-dropdown]').each(function()
    {
        $(this).dropdown();
    })

	$('a[data-role="modal"]').click(function(e)
	{
		e.preventDefault();
		$(this).removeAttr('data-role');

		var cboxLocation = $(this).attr('href');
		var cboxTitle = $(this).data('title');
		var cboxDisplay = $(this).data('display') || 'inline';
		if ( cboxDisplay == 'iframe' )
		{
			var cboxWidth = $(this).data('width') || 600;
			$.colorbox({
				title: function(){
					return cboxTitle;
				},
				transition: 'none',
				opacity: 0.7,
				initialWidth: 150,
				initialHeight: 100,
				width: cboxWidth,
				height: 150,
				iframe: true,
				fastIframe: false,
				href: ( cboxLocation + '?modal=1' )
				//onComplete:function(){
				//	var cboxHeight = $('.cboxIframe').contents().find('body').height();
				//	$.colorbox.resize({
				//		innerHeight:cboxHeight
				//	});
				//}
			});
		}
		else if ( cboxDisplay == 'html' )
		{
			$.colorbox({
				title: function(){
					return cboxTitle;
				},
				transition: 'none',
				opacity: 0.7,
				initialWidth: 150,
				initialHeight: 100,
				width: 400,
				height: 300,
				html: $(this).data('html')
			});
		}
		else
		{
			$.colorbox({
				title: function(){
					return cboxTitle;
				},
				transition: 'none',
				opacity: 0.7,
				initialWidth: 150,
				initialHeight: 100,
				href: cboxLocation
			});
		}
	});

	$('a[data-role="confirm"]').click(function(e)
	{
		e.preventDefault();
		var cboxLocation = $(this).attr('href');
		var cboxMessage = $(this).data('html');
		$.colorbox({
			title: '[lang.system.confirm]',
			transition: 'none',
			initialWidth: 500,
			initialHeight: 100,
			innerWidth: 500,
			top: '20%',
			close: false,
			onComplete:function(){
				$('#cboxConfirm a.success').focus();
			},
			html: function(){
				return cboxMessage +
				'<div id="cboxConfirm">' +
					'<a href="#" onclick="window.location=\'' + cboxLocation + '\';return false;" class="button small success">[lang.system.continue]</a>' +
					'<a href="#" onclick="$.colorbox.close();return false;" class="button small important">[lang.system.cancel]</a>' +
				'</div>'; }
		});
	});
});

function initTooltips()
{
    $('[data-tooltip]').each(function(obj,obj2){
    	var style = $(this).data('tooltip') || 'default';
        $(this).tipsy({
			gravity: 's',
			prefix: style
		});
	});
}

function confirmLink(question, url)
{
	return false;

    var is_confirmed = confirm(question);

    if ( is_confirmed && url != '' )
    {
        window.location = url;
	}

    return is_confirmed;
}

function confirmForm(question, form)
{
    var is_confirmed = confirm(question);

    if ( is_confirmed && form != '' )
    {
        $('#' + form).submit();
	}

    return is_confirmed;
}
