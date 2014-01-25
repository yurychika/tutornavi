function startAjax(spinner, spinnerClass)
{
	if ( typeof(spinnerClass) == 'undefined' || spinnerClass == null || spinnerClass == '' )
	{
		$('#' + spinner).show();
	}
	else
	{
		$('#' + spinner).addClass(spinnerClass);
	}
}

function stopAjax(spinner, spinnerClass)
{
	if ( typeof(spinnerClass) == 'undefined' || spinnerClass == null || spinnerClass == '' )
	{
		$('#' + spinner).hide();
	}
	else
	{
		$('#' + spinner).removeClass(spinnerClass);
	}
}

function runAjax(target, params, onComplete, onCompleteArgs, spinner, spinnerClass, onError, onErrorArgs, onStart, onStartArgs)
{
	if ( typeof(spinner) != 'undefined' )
	{
		startAjax(spinner, spinnerClass);
	}

	if ( typeof(onStart) != 'undefined' )
	{
		if ( typeof(onStart) == 'function' )
		{
			onStart(onStartArgs);
		}
		else
		{
			window[onStart](onStartArgs);
		}
	}

	$.ajax({
		type: 'POST',
		url: target,
		data: params,
		dataType: 'json',
		success: function(data)
		{
			if ( typeof(spinner) != 'undefined' )
			{
				stopAjax(spinner, spinnerClass);
			}

			if ( data == null )
			{
				alert('Invalid response received (#1011)');
				return;
			}

			if ( typeof(data.status) == 'undefined' || typeof(data.code) == 'undefined' || typeof(data.message) == 'undefined' )
			{
				alert('Response is missing parameters (#1012)');
				return;
			}

			if ( data.status == 'error' )
			{
				if ( typeof(onError) != 'undefined' )
				{
					if ( typeof(onError) == 'function' )
					{
						onError(data.code, data.message, onErrorArgs);
					}
					else
					{
						window[onError](data.code, data.message, onErrorArgs);
					}
				}
				else
				{
					alert('Error occurred processing request: ' + data.message + ' (#' + data.code + ')');
				}
				return;
			}

			if ( typeof(onComplete) != 'undefined' )
			{
				if ( typeof(onComplete) == 'function' )
				{
					onComplete(data.message, onCompleteArgs);
				}
				else
				{
					window[onComplete](data.message, onCompleteArgs);
				}
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown)
		{
			if ( typeof(spinner) != 'undefined' )
			{
				stopAjax(spinner, spinnerClass);
			}

			//alert('Invalid response received: ' + textStatus + ' (#1013)');
		}
	});
}

function replaceContent(output, container)
{
	$('#' + container).replaceWith(output);
}
