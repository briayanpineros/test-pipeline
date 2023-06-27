/**
 * @file
 * Superfish SmallScreen Javascrip for Sidr Menu.
 *
 */
(function ($, Drupal) {

  Drupal.behaviors.conil_theme = {
    attach: function (context, settings) {
      $(document).ready(function () {

        // if ($('body').not('.path-frontpage').length){
        //   $(".header-wrapper").once().css('position', 'relative');
        // }

        $(".search-block-form input").attr('placeholder', Drupal.t('Enter the search...'));

        if ($('body.entity-user-edit-form').length) {
          $(".entity-user-edit-form #user-form").once().prepend('<h1 class="title1 mayu">' + Drupal.t("User profile") + '</h1>');
          $(".entity-user-edit-form #edit-account").once().prepend('<h3 class="title1">' + Drupal.t("Access data") + '</h3>').append('<h3 class="title1 passtitle">' + Drupal.t("Change of access data") + '</h3>');
        }

        if ($('body.path-agenda')) {
          let toolbar = $('div.js-drupal-fullcalendar div.fc-toolbar.fc-header-toolbar');
          let titulo = $('#block-conil-theme-page-title');
          titulo.append(toolbar);

        }

        if ($('body.path-poi').length) {
          //let pageTitle = $('#block-conil-theme-page-title');
          //$('#block-quicktabspoislideandmap .item-list').once().prepend(pageTitle);
          let submitButton = $(".exposed-form-wrapper #edit-actions");
          $(".exposed-form-wrapper details#edit-field-poi-geofield-proximity-collapsible").prepend(submitButton);
          let proximityFilter = $('.exposed-form-wrapper fieldset#edit-field-poi-geofield-proximity-wrapper');
          $(".exposed-form-wrapper details#edit-field-poi-geofield-proximity-collapsible").prepend(proximityFilter);
          let mainTitle = $('.view-points-of-interest .view-header .subcategory').text();
          document.title = mainTitle + " | " + Drupal.t("Conil tourism");

          let subcategoryMenu = $('.view-poi-subcategory-menu .view-content div.views-row');
          subcategoryMenu.each(function (element) {
            if ($(subcategoryMenu[element]).find('a').text().toUpperCase() === mainTitle.toUpperCase().replaceAll("\n", "")) {
              $(subcategoryMenu[element]).addClass('selected-option');
            }
          });

          let cuerpoPoi = $('')
          // $('#block-views-block-highlighted-opinions-highlighted-poi-opinions').once().prepend(aux);
        }

        function fixMenu() {
          $('#superfish-sidr-navigation-toggle').addClass('sf-expanded');
          $('div.sidr-inner #block-navegaciondemenu ul#superfish-sidr-navigation-accordion').removeClass('sf-hidden');
          $('#block-conilmainlogoandsidrclose .field .field__items .field__item:nth-child(1)').once().on('click', function () {
            $.sidr('close', 'sidr');
          });
          // $('div.sidr-inner * #superfish-sidr-navigation-accordion li.menuparent span.menuparent').each(function () {
          //   $(this).once().on('click', function (e) {
          //     // Making sure the buttons does not exist already.
          //     if ($(this).closest('li').children('ul').length > 0) {
          //       e.preventDefault();
          //       // Selecting the parent menu items.
          //       var parent = $(this).closest('li');
          //       // Once the button is clicked, collapse the sub-menu if it's expanded.
          //       if (parent.hasClass('sf-expanded')) {
          //         parent.children('ul').slideUp('fast', function () {
          //           // Doing the accessibility trick after hiding the sub-menu.
          //           $(this).closest('li').removeClass('sf-expanded').end().addClass('sf-hidden').show();
          //         });
          //       }
          //       // Otherwise, expand the sub-menu.
          //       else {
          //         // Doing the accessibility trick and then showing the sub-menu.
          //         parent.children('ul').hide().removeClass('sf-hidden').slideDown('fast')
          //           // Changing the caption of the inserted Expand link to 'Collape', if any is inserted.
          //           .end().addClass('sf-expanded').children('a.sf-accordion-button').text('')
          //           // Hiding any expanded sub-menu of the same level.
          //           .end().siblings('li.sf-expanded').children('ul')
          //           .slideUp('fast', function () {
          //             // Doing the accessibility trick after hiding it.
          //             $(this).closest('li').removeClass('sf-expanded').end().addClass('sf-hidden').show();
          //           })
          //           // Assuming Expand\Collapse buttons do exist, resetting captions, in those hidden sub-menus.
          //           .parent().children('a.sf-accordion-button').text('');
          //       }
          //     }
          //   });
          // });
        }
        if ($(window).width() < 720) {
          let image_gastronomic = $('.page-node-type-gastronomic-r .field--name-field-poi-media').html();
          $('.page-node-type-gastronomic-r .le .left').once().prepend(image_gastronomic);
          $('.page-node-type-gastronomic-r .field--name-field-poi-media').remove();
        }

        let galery = $('#block-views-block-carrousel-block-1');
        galery.once().insertBefore('.field--name-field-poi-comments');

        $('#comment-form').submit(function (event) {
          if ($('#edit-field-fivestar-0-rating--2').val() == '-') {
            let info = "<div role='contentinfo' aria-label='Mensaje de error' class='messages messages--error'><div role='alert'><h2 class='visually-hidden'>" + Drupal.t('Error message') + "</h2>" + Drupal.t('Select a score.') + "</div></div>";
            $('section[class*=field--name-field-poi-comments]').once().prepend(info);
            event.preventDefault();
            return false;
          }
          return true;
        });

        $('li.map a').once().click(function () {
          location.reload();
        });

        // Arreglos accesibilidad.
        $('body.path-frontpage input.form-search').once().attr('aria-label', Drupal.t('Campo de búsqueda')).attr('role', 'search');
        $('body.path-frontpage div.slick--view--carousel-home--block-1 nav.slick__arrow').once().attr('aria-label', Drupal.t('Carrusel Fotos panorámicas')).attr('role', 'navigation');
        $('body.path-frontpage div.slick--view--events-front-page nav.slick__arrow').once().attr('aria-label', Drupal.t('Carrusel Eventos')).attr('role', 'navigation');
        $('body.path-frontpage div.slick--view--highlighted-opinions--block-1 nav.slick__arrow').once().attr('aria-label', Drupal.t('Carrusel Opiniones')).attr('role', 'navigation');
        $('nav#block-piedepagina').once().attr('aria-label', Drupal.t('Menú secundario pie de página'));
        $('.sidr-trigger').once().attr('aria-label', Drupal.t('Menú'));
        $('.social-media-link-icon--facebook').once().attr('aria-label', Drupal.t('Link Facebook'));
        $('.social-media-link-icon--instagram').once().attr('aria-label', Drupal.t('Link Instagram'));
        $('.social-media-link-icon--twitter').once().attr('aria-label', Drupal.t('Link Twitter'));
        $('.social-media-link-icon--youtube_channel').once().attr('aria-label', Drupal.t('Link Youtube'));
        $('.social-media-link-icon--whatsapp').once().attr('aria-label', Drupal.t('Link Whatsapp'));
        $('.search-txt').once().attr('aria-label', Drupal.t('Campo de búsqueda'));
        $('body.path-frontpage label.js-form-required.form-required').remove();
        $('.facebook-share').once().attr('aria-label', Drupal.t('Compartir en Facebook'));
        $('.twitter.share').once().attr('aria-label', Drupal.t('Compartir en Twitter'));
        $('.whatsapp.show-for-small-only.share').once().attr('aria-label', Drupal.t('Compartir en Whatsapp'));
        $("p.CaptionCont.SelectBox.search label").once().attr('aria-label', Drupal.t('Selección idioma.'));
        $("div.sumo_field_tipo_viaje_target_id p.CaptionCont.SelectBox.search label").attr('aria-label', Drupal.t('Selección tipo de viaje.'));
        $("label > i").once().attr('aria-hidden', 'true').attr('role', 'select');
        $("i.material-icons").once().attr('aria-hidden', 'true').attr('role', 'img');

        let etiqueta = "<h2 class='sr-only'>" + Drupal.t('Ficha detalle del punto de interés') + "</h2>";
        $('body.page-node-type-poi .content-container').once().prepend(etiqueta);

        let $childSpan = $(".field.field--name-uid.field--type-entity-reference.field--label-hidden").children("span");
        let browserLanguage = navigator.language || navigator.userAgent;
        $childSpan.once().attr('lang', browserLanguage);

        $('div.btn').once().attr('role', 'button');
        $('#cboxPrevious').once().text(Drupal.t('Anterior'));
        $('#cboxNext').once().text(Drupal.t('Siguiente'));
        $('#cboxSlideshow').once().text(Drupal.t('Mostrar carrusel'));
        $('body.view-pomotional-video-page-1 img').once().attr('alt', Drupal.t('Imagen de previsualización del video.'));
        $('body.entity-webform-canonical textarea').once().attr('aria-label', Drupal.t('Comentario de incidencia.'));
        $('.acceso_mapa_sitio > img').once().attr('aria-label', Drupal.t('Ir a mapa de sitio'));

        let aviso_legal = "<h2 class='sr-only'>" + Drupal.t('Indice') + "</h2>";
        $('body.page-node-type-general-page .field--type-text-with-summary').once().prepend(aviso_legal);

        let punto_interes = "<h1 class='sr-only'>" + Drupal.t('Puntos de interés') + "</h1>";
        $('body.view-points-of-interest-page-1 nav.breadcrumb').once().prepend(punto_interes);
        // Fin arreglos.

        let textoLabel = $('.path-frontpage #block-exposedformviaje-a-medidablock-1 .options .selected label').text();
        $('.path-frontpage .subcategory').once().text(textoLabel);

        $('.url_video').once().each(function(index) {
          let url = $('.url_video')[index].innerHTML;
          $('.popupVideo').eq(index).data('video', url );
          $('.popupVideo', context).once('colorbox-video').click(function (e) {
            e.preventDefault();
            var videoUrl = $(this).data('video');
            let width = $(window).width();

            if (width <= 576) {
              $.colorbox({ iframe: true, href: videoUrl, width: '90%', height: '30%', frameborder: '0', class: 'media-oembed-content', title: $(this).attr('alt') });
            }
            if (width > 576 && width <= 992) {
              $.colorbox({ iframe: true, href: videoUrl, width: '90%', height: '40%', frameborder: '0', class: 'media-oembed-content', title: $(this).attr('alt') });
            }
            if (width > 992 && width <= 1200) {
              $.colorbox({ iframe: true, href: videoUrl, width: '90%', height: '50%', frameborder: '0', class: 'media-oembed-content', title: $(this).attr('alt') });
            }
            if (width > 1200) {
              $.colorbox({ iframe: true, href: videoUrl, width: '90%', height: '90%', frameborder: '0', class: 'media-oembed-content', title: $(this).attr('alt') });
            }
          });
        });

        let div = $('.media-library-item__name');
        let textoDiv = div.text();
        let nuevoTexto = textoDiv.replace(".mp4", "");
        div.text(nuevoTexto);

        $('.region-header-wrapper').once().on('click', fixMenu());
        $('.region-header-wrapper').once().on('touchstart ', fixMenu());
      });
    }
  };

})(jQuery, Drupal);
