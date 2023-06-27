/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.load_sumoselect = {
    attach: function(context, settings) {
      // SumoSelect translations.
      var ssLocale = {
        placeholder: Drupal.t('Seleccione'),
        captionFormat: '{0} ' + Drupal.t('seleccionado'),
        captionFormatAllSelected: '{0} ' + Drupal.t('todos los items'),
        searchText: Drupal.t('Buscar'),
        noMatch: Drupal.t("Sin coincidencias para") + " '{0}'",
        locale: [Drupal.t('OK'), Drupal.t('Cancelar'), Drupal.t('Seleccionar todo')]
      };
      $('select:not([multiple])').once('sumoselect-load').SumoSelect($.extend({
        'forceCustomRendering': true,
        'floatWidth': 200,
        'search': true
      }, ssLocale));
      $('select[multiple]').once('sumoselect-load').SumoSelect($.extend({
        'forceCustomRendering': true,
        'floatWidth': 200,
        'search': true,
        'selectAll': false
      }, ssLocale));
    }
  };

})(jQuery, Drupal);
