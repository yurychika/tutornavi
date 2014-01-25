(function($){
    $.fn.dropdown = function(options)
    {
        var defaults = {};
        var selector = $(this);

        var clear = function()
        {
        	$('[data-dropdown]').removeClass('hover');
            $('[data-dropdown-menu]').hide();
        }

        var initialize = function(options, selector)
        {
        	selector.children('a').click(function(e)
        	{
        		e.preventDefault();
			});

			var target = selector.data('dropdown') || 'menu';
			var dropdown = $('[data-dropdown-menu="' + target + '"]');

			var hover = selector.data('dropdown-action') == 'hover' ? true : false;

            selector.click(function(e)
            {
                e.stopPropagation();

                if ( dropdown.css('display') != 'block' )
                {
                	clear();
                    dropdown.show();
                    $(this).addClass('hover');
				}
                else if ( !hover )
                {
                	clear();
				}
            });

            if ( hover )
            {
            	selector.mouseenter(function(e){
            		$(this).click();
				}).mouseleave(function(){
           			clear();
	            });
			}
			else
			{
				//$('html').click(function(e)
				//{
				//	clear();
				//});
			}
        }

        return this.each(function()
        {
            if ( options )
            {
                options = $.extend(defaults, options)
            }

            initialize(options, selector);
        });
    }
})(window.jQuery);
