var geoCache = {'cities':{},'states':{}};

function geo(title, id, country, state, city)
{
	if ( typeof(country) != 'undefined' && country != null )
	{
		if ( typeof(geoCache['states'][country]) != 'undefined' )
		{
			geoElements(title, geoCache['states'][country], {'id':id,'type':'state','value':( typeof(state) != 'undefined' ? state : null )});

			if ( typeof($('#' + id + '_state_city').attr('id')) != 'undefined' )
			{
				$('#' + id + '_state_city').remove();
			}

			if ( typeof(city) != 'undefined' && city != null )
			{
				if ( typeof(geoCache['cities'][state]) != 'undefined' )
				{
					geoElements(title, geoCache['cities'][state], {'id':id,'type':'city','value':city});
				}
				else
				{
					runAjax('[conf.config.site_url]geo/cities/' + state, {'title':title}, function(response, id)
					{
						geoElements(title, response, {'id':id,'type':'city','value':city});
					}, id, 'ajax-data-'+id);
				}
			}
		}
		else
		{
			runAjax('[conf.config.site_url]geo/states/' + country, {'title':title}, function(response, id)
			{
				if ( typeof($('#' + id + '_state_city').attr('id')) != 'undefined' )
				{
					$('#' + id + '_state_city').remove();
				}

				geoCache['states'][country] = response;
				geoElements(title, response, {'id':id,'type':'state','value':( typeof(state) != 'undefined' ? state : null )});

				if ( typeof(city) != 'undefined' && city != null )
				{
					runAjax('[conf.config.site_url]geo/cities/' + state, {'title':title}, function(response, id)
					{
						geoCache['cities'][state] = response;
						geoElements(title, response, {'id':id,'type':'city','value':city});
					}, id, 'ajax-data-'+id);
				}
			}, id, 'ajax-data-'+id);
		}
	}
	else if ( typeof(state) != 'undefined' && state != null && ( typeof(city) == 'undefined' || city == null ) )
	{
		if ( typeof(geoCache['cities'][state]) != 'undefined' )
		{
			geoElements(title, geoCache['cities'][state], {'id':id,'type':'city','value':null});
		}
		else
		{
			runAjax('[conf.config.site_url]geo/cities/' + state, {'title':title}, function(response, id)
			{
				geoCache['cities'][state] = response;
				geoElements(title, response, {'id':id,'type':'city','value':null});
			}, id, 'ajax-data-'+id);
		}
	}
}

function geoElements(title, list, args)
{
	if ( typeof($('#' + args['id'] + '_' + args['type']).attr('id')) != 'undefined' )
	{
		var obj = $('#' + args['id'] + '_' + args['type']);
	}
	else
	{
		var obj = $('#' + args['id']).clone();
		var name = (obj.attr('name')).split('[')[0];

		obj.attr('onchange', ( args['type'] == 'state' ? "geo('" + title + "', this.id,null,this.value)" : "" ));
		obj.attr('name', name + '[' + args['type'] + ']');
		obj.attr('id', obj.attr('id') + ( args['type'] == 'city' && typeof(args['value']) != 'undefined' && args['value'] != null ? '_state' : '' ) + '_' + args['type']);
	}

	obj.html('');

	$.each(list, function(key, value) {
		 $(obj)
			 .append($("<option></option>")
			 .attr("value", $.trim(key))
			 .text(value));
	});

	if ( typeof(args['value']) != 'undefined' && args['value'] != null )
	{
		$(obj).val(args['value']);
		$('#' + args['id'] + ( args['type'] == 'city' ? '_state' : '' )).after(obj);
		$('#' + args['id'] + ( args['type'] == 'city' ? '_state' : '' )).after(' ');
	}
	else
	{
		$('#' + args['id']).after(obj);
		$('#' + args['id']).after(' ');
	}
}