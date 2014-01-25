(function($){
    $.fn.rating = function(options)
    {
        var defaults = {};

        var $this = $(this);

        var initialize = function(el)
        {
            var stars = el.find('a');
            var score = Math.round(el.data('rating')) || 0;

            stars.each(function(index)
            {
                if ( index < score )
                {
                    $(this).addClass('rated');
                }

                $(this).hover
                (
                    function()
                    {
                        $(this).prevAll().andSelf().addClass('hover');
                        $(this).nextAll().removeClass('hover');
                    },
                    function()
                    {
                        $(this).prevAll().andSelf().removeClass('hover');
                    }
                )
            })

        }

        return this.each(function()
        {
            if ( options )
            {
                $.extend(defaults, options)
            }

            initialize($this);
        });
    }
})(window.jQuery);
