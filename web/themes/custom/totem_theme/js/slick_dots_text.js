(function ($, Drupal) {
    $(window).on('scroll', function () {
        $(window).once().on('scroll', function () {
            let slick = $('#slick-views-events-front-page-block-block-2');
            let slides = $(slick).find(".slick-track .slick-slide");
            let dots = $('.slick-dots li');
            $(slides).each(function (index) {
                let text = $(this).find('.field-content div.description div.title').text();
                $(dots[index]).text(text);
            });

            slick = $('#slick-views-events-front-page-block-block-1');
            slides = $(slick).find(".slick-track .slick-slide");
            dots = $('.slick-dots li');
            $(slides).each(function (index) {
                let text = $(this).find('.field-content div.description div.title').text();
                $(dots[index]).text(text);
            });

            slick = $('#slick-views-events-front-page-block-poi-block-1-1');
            slides = $(slick).find(".slick-track .slick-slide");
            dots = $('.slick-dots li');
            $(slides).each(function (index) {
                let text = $(this).find('.field-content div.description div.title').text();
                $(dots[index]).text(text);
            });

        });
    });
})(jQuery, Drupal);