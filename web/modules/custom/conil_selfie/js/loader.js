/**
 * @file
 * File to lock the screen at submit selfie.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.loader = {
    attach: function (context, settings) {
      var body = $('body');
      $('.submit-selfie').on('click', function () {
        body.once().append('<div class="lock"><div class="loader"></div></div>');
        body.css({
          'overflow': 'hidden'
        });
        $('.lock').fadeIn('slow');
      });
    }
  }
})(jQuery, Drupal);
