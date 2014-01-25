(function($){
    $.fn.tabs = function(options)
    {
        var defaults = {
			current: ''
        };

        var $this = $(this),
        	$tabs = $this.children(),
        	$selectors = $this.find('li a'),
        	$frames = $('[data-role="' + $this.data('target') + '"] [data-role="frame"]');

        var initialize = function(selectors)
        {
            selectors.click(function(e){
                e.preventDefault();
                setCurrent($(this));
            });
        }

        var setCurrent = function($tab)
        {
            if ( $tab.parent('li').hasClass('active') ) return false;
            $frames.hide();
            $tabs.removeClass('active');
            var $target = $('[data-frame="' + $tab.attr('href').substr(1) + '"]');
            $target.show();
            $tab.parent("li").addClass('active');
		}

        var getCurrent = function()
        {
        	if ( window.location.hash )
        	{
        		$tab = $tabs.find('a[href=#' + window.location.hash.slice(1) + ']');
			}
        	else if ( defaults['current'] != '' )
        	{
        		$tab = $tabs.find('a[href=#' + defaults['current'] + ']');
			}
			else
			{
				$tab = $selectors.eq(0);
			}

        	return $tab;
		}

        return this.each(function()
        {
            if ( options )
            {
                $.extend(defaults, options)
            }

            initialize($selectors);

			setCurrent(getCurrent());
        });
    }
})(window.jQuery);
