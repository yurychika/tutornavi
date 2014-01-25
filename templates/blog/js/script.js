$(document).ready(function() {

    $('.sidebar-left .box h2 .click').click(function(){
        if ($(this).hasClass('ed'))
            $(this).removeClass('ed');
        else
            $(this).addClass('ed');
        $(this).parent().parent().find('ul').first().slideToggle();
        return false;
    });

    $('.header-top .notify').click(function(){
        if ($('.header-top .notify-content').hasClass('show-notify'))
            $('.header-top .notify-content').removeClass('show-notify');
        else
            $('.header-top .notify-content').addClass('show-notify');
        return false;
    });
    $(document).mouseup(function (e)
    {
        var container = $('.notify-content');

        if (!container.is(e.target)
            && container.has(e.target).length === 0)
        {
            container.removeClass('show-notify');
        }
    });

    var title =""
    $('.simple-tooltip').hover(function(event) {
        title = $(this).attr('title');
        var toolTip = title;
        $(this).removeAttr('title');
        $('<span class="tooltip"></span>').text(toolTip)
            .appendTo('body')
            .css('top', (event.pageY - 10) + 'px')
            .css('left', (event.pageX + 20) + 'px')
            .fadeIn('slow');
    }, function() {
        $('.tooltip').remove();
        $(this).attr('title',title);
    }).mousemove(function(event) {
            $('.tooltip')
                .css('top', (event.pageY - 10) + 'px')
                .css('left', (event.pageX + 20) + 'px');
        });



});