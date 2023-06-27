// /**
//  * @file
//  * Superfish SmallScreen Javascrip for Sidr Menu.
//  *
//  */
// (function ($, Drupal) {
//
//   Drupal.behaviors.totem_theme = {
//     attach: function (context, settings) {
//       $(document).ready(function () {
//
//         // if ($('body').not('.path-frontpage').length){
//         //   $(".header-wrapper").once().css('position', 'relative');
//         // }
//
//         $(".search-block-form input").attr('placeholder', Drupal.t('Introduce el dato a buscar...'));
//
//         function fixMenu (){
//           $('#superfish-sidr-navigation-toggle').addClass('sf-expanded');
//           $('div.sidr-inner #block-navegaciondemenu ul#superfish-sidr-navigation-accordion').removeClass('sf-hidden');
//           $('#block-conilmainlogoandsidrclose .field .field__items .field__item:nth-child(1)').on('click', function () {
//             $.sidr('close', 'sidr');
//           });
//           // $('div.sidr-inner * #superfish-sidr-navigation-accordion li.menuparent span.menuparent').each(function () {
//           //   $(this).once().on('click', function (e) {
//           //     // Making sure the buttons does not exist already.
//           //     if ($(this).closest('li').children('ul').length > 0) {
//           //       e.preventDefault();
//           //       // Selecting the parent menu items.
//           //       var parent = $(this).closest('li');
//           //       // Once the button is clicked, collapse the sub-menu if it's expanded.
//           //       if (parent.hasClass('sf-expanded')) {
//           //         parent.children('ul').slideUp('fast', function () {
//           //           // Doing the accessibility trick after hiding the sub-menu.
//           //           $(this).closest('li').removeClass('sf-expanded').end().addClass('sf-hidden').show();
//           //         });
//           //       }
//           //       // Otherwise, expand the sub-menu.
//           //       else {
//           //         // Doing the accessibility trick and then showing the sub-menu.
//           //         parent.children('ul').hide().removeClass('sf-hidden').slideDown('fast')
//           //           // Changing the caption of the inserted Expand link to 'Collape', if any is inserted.
//           //           .end().addClass('sf-expanded').children('a.sf-accordion-button').text('')
//           //           // Hiding any expanded sub-menu of the same level.
//           //           .end().siblings('li.sf-expanded').children('ul')
//           //           .slideUp('fast', function () {
//           //             // Doing the accessibility trick after hiding it.
//           //             $(this).closest('li').removeClass('sf-expanded').end().addClass('sf-hidden').show();
//           //           })
//           //           // Assuming Expand\Collapse buttons do exist, resetting captions, in those hidden sub-menus.
//           //           .parent().children('a.sf-accordion-button').text('');
//           //       }
//           //     }
//           //   });
//           // });
//         }
//
//         $('.region-header-wrapper').once().on('click', fixMenu());
//         $('.region-header-wrapper').once().on('touchstart ', fixMenu());
//       });
//     }
//   };
//
// })(jQuery, Drupal);
