/**
 * @file
 * Superfish SmallScreen Javascrip for Sidr Menu.
 *
 */
(function ($, Drupal) {
  Drupal.behaviors.totem_theme = {
    attach: function (context, settings) {
      let zoom = 100;
      $(document).ready(function () {
        let bloque_valoracion = $('#block-views-block-valoracion-block-1').html();
        $('.field--name-field-poi-telephone').once().prepend(bloque_valoracion);

        jQuery.uaMatch = function (ua) {
          ua = ua.toLowerCase();

          var match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
            /(webkit)[ \/]([\w.]+)/.exec(ua) ||
            /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
            /(msie)[\s?]([\w.]+)/.exec(ua) ||
            /(trident)(?:.*? rv:([\w.]+)|)/.exec(ua) ||
            ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) ||
            [];

          return {
            browser: match[1] || "",
            version: match[2] || "0"
          };
        };

        var currFFZoom = 1;
        var currIEZoom = 100;
        var maxZoom = 2;
        var minZoom = 2;
        function add() {
          if (jQuery.uaMatch(navigator.userAgent).browser == "mozilla") {
            var step = 0.25;
            if (currFFZoom < maxZoom && currFFZoom >= minZoom) {
              currFFZoom += step;
              $('body').css('-moz-transform', 'scale(' + currFFZoom + ')');
              $('body').css('-moz-transform-origin', '0px 0px 0px');
              $('.header-group').css('position', 'inherit');
            }
          }
          else {
            var step = 2;
            currIEZoom += step;
            $('body').css('zoom', currIEZoom + '%');
          }
        }

        function rest() {
          if (jQuery.uaMatch(navigator.userAgent).browser == "mozilla") {
            var step = 0.25;
            if (currFFZoom <= maxZoom && currFFZoom > minZoom) {
              currFFZoom -= step;
              $('body').css('-moz-transform', 'scale(' + currFFZoom + ')');
            }
            if (currFFZoom == 1) {
              $('body').css('-moz-transform-origin', '');
              $('.header-group').css('position', 'sticky');
            }
          } else {
            var step = 2;
            currIEZoom -= step;
            $('body').css('zoom', currIEZoom + '%');
          }
        }
        let elementos = document.querySelectorAll('body a:not(.visually-hidden):not(nav#block-menudenavegaciondetotem a):not(nav#block-menunavegaciontotem2 a):not(div#block-quicktabspoislideandmap-2 a), body input:not(.search-txt):not([type=hidden]):not(div.footer-bottom input), body select');
        let indiceActual = 0;

        function avanzar(direccion) {
          if (elementos[indiceActual].tagName === 'SELECT') {
            elementos[indiceActual].parentElement.classList.remove("open");
          }
          elementos[indiceActual].blur();

          if (direccion === 'adelante') {
            indiceActual++;
            if (indiceActual >= elementos.length) {
              indiceActual = 0;
            }
          } else {
            indiceActual--;
            if (indiceActual < 0) {
              indiceActual = elementos.length - 1;
            }
          }
          elementos[indiceActual].focus();
          if (elementos[indiceActual].tagName === 'SELECT') {
            elementos[indiceActual].parentElement.classList.add("open");
          }
        }

        function clickElementoConFoco() {
          let elementoConFoco = document.activeElement;
          if (elementoConFoco.tagName === "A") {
            window.location.href = elementoConFoco.href;
          }
        }

        let zoom_in = document.createElement("button");
        zoom_in.id = "zoom_in";
        zoom_in.innerHTML = "+";
        zoom_in.addEventListener('click', add);

        let zoom_out = document.createElement("button");
        zoom_out.id = "zoom_out";
        zoom_out.innerHTML = "-";
        zoom_out.addEventListener('click', rest);

        let before = document.createElement("a");
        before.id = "before";
        before.textContent = "Anterior";
        before.addEventListener('click', function () {
          avanzar('atras');
        });

        let enter = document.createElement("button");
        enter.id = "enter";
        enter.innerHTML = "ENTRAR";
        enter.addEventListener('touchstart', clickElementoConFoco);

        let after = document.createElement("a");
        after.id = "after";
        after.textContent = "Siguiente";
        after.addEventListener('click', function () {
          avanzar('adelante');
        });

        let div_accessibility = document.createElement('div');
        div_accessibility.classList.add('accessibility');
        $('.footer-bottom').once().append(div_accessibility).html();
        $('.accessibility').once().append(zoom_out, zoom_in, before, enter, after).html();

      });

      if ($('#block-views-block-poi-subcategory-menu-block-1-2').length) {
        let path = window.location.pathname.split('/');
        let tid = path[path.length - 1];
        if (tid) {
          $('#block-views-block-poi-subcategory-menu-block-1-2 .views-row.tid-' + tid).once().addClass('active');
        }
      }

      let mainTitle = $('.view-points-of-interest .view-header .subcategory').text();

      let subcategoryMenu = $('.view-poi-subcategory-menu .view-content div.views-row');
      subcategoryMenu.each(function (element) {

        if ($(subcategoryMenu[element]).find('a').text().toUpperCase() === mainTitle.toUpperCase().replaceAll("\n", "")) {
          $(subcategoryMenu[element]).addClass('selected-option');
        }
      });

      // Arregla carrusel con videos locales.
      $('.url_video').once().each(function (index) {
        let url = $('.url_video')[index].innerHTML;
        $('.popupVideo').eq(index).data('video', url);
        $('.popupVideo', context).once('colorbox-video').click(function (e) {
          e.preventDefault();
          var videoUrl = $(this).data('video');
          $.colorbox({ iframe: true, href: videoUrl, width: '80%', height: '80%', frameborder: '0', class: 'media-oembed-content', title: $(this).attr('alt') });
        });
      });

      // if ($('body.page-node-type-agenda').length) {
      //   let textoInicio = $(".field--name-field-agenda-inicio-fecha").text().split(" ");
      //   $(".field--name-field-agenda-inicio-fecha").once().html("<span class='field-agenda-inicio-fecha-dia'>" + textoInicio[0] + "</span><span class='field-agenda-inicio-fecha-resto'>" + textoInicio.slice(1).join(" ") + "<br>Start</span>");
      //   let textoFin = $(".field--name-field-agenda-fin-fecha").text().split(" ");
      //   $(".field--name-field-agenda-fin-fecha").once().html("<span class='field-agenda-fin-fecha-dia'>" + textoFin[0] + "</span><span class='field-agenda-fin-fecha-resto'>" + textoFin.slice(1).join(" ") + "<br>End</span>");
      // }

      // Evitar zoom.
      document.addEventListener('gesturestart', function (e) {
        e.preventDefault();
        // special hack to prevent zoom-to-tabs gesture in safari
        document.body.style.zoom = 0.99;
      });

      document.addEventListener('gesturechange', function (e) {
        e.preventDefault();
        // special hack to prevent zoom-to-tabs gesture in safari
        document.body.style.zoom = 0.99;
      });

      document.addEventListener('gestureend', function (e) {
        e.preventDefault();
        // special hack to prevent zoom-to-tabs gesture in safari
        document.body.style.zoom = 0.99;
      });

      // Código de la empresa de Totems para control de inactividad.
      const events = ['click', 'mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart']; // DOM Events
      events.forEach(function (name) {
        document.addEventListener(name, parent.postMessage('hello!', '*'), true);

      });

    }
  };

})(jQuery, Drupal);
