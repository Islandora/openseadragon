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
   * Invoke bookmark url and log any viewer changes in URL.
   */
  Drupal.behaviors.openSeadragonBookmarkURL = {
    attach: function(context, settings) {
      Object.keys(settings.openseadragon).forEach(function(osdViewerId) {
        // Use custom element #id if set.
        base = '#' + osdViewerId;
        once('openSeadragonViewerBookmarkURL', base, context).forEach(function () {
          Drupal.openSeadragonViewer[base].addHandler('bookmark-url-change', displayNewUrl);
          Drupal.openSeadragonViewer[base].bookmarkUrl();
        });
      });
    },
    detach: function(context, settings, trigger) {
      Object.keys(settings.openseadragon).forEach(function(osdViewerId) {
        // Use custom element #id if set.
        base = '#' + osdViewerId;
        Drupal.openSeadragonViewer[base].removeHandler('bookmark-url-change', displayNewUrl);
      });
    }
  };

  function displayNewUrl(event) {
    console.log('New URL:', event.url);
  }

})($, Drupal, drupalSettings, once);
