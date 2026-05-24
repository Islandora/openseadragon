/*jslint browser: true*/
/*global OpenSeadragon, Drupal*/
/**
 * @file
 * Attaches openseadragon-bookmark-url to OpenSeadragon viewer.
 */
(function($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * The DOM element that represents the Singleton Instance of this class.
   * @type {string}
   */
  var base = '#openseadragon-viewer';

  /**
   * Invoke bookmark url plugin.
   */
  Drupal.behaviors.openSeadragonBookmarkURL = {
    attach: function(context, settings) {
      Object.keys(settings.openseadragon).forEach(function(osdViewerId) {
        // Use custom element #id if set.
        base = '#' + osdViewerId;
        once('openSeadragonViewerBookmarkURL', base, context).forEach(function () {
          Drupal.openSeadragonViewer[base].bookmarkUrl();
        });
      });
    }
  };

})($, Drupal, drupalSettings, once);
