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
          // Load bookmark url, but without page tracking, to allow hash to be set.
          Drupal.openSeadragonViewer[base].bookmarkUrl({trackPage: false});
          if (window.location.hash === '') {
            var hash =  settings.openseadragon[osdViewerId].openseadragonBookmarkUrl;
            if (hash !== '') {
              window.location.hash = hash;
            }
          }
          // With hash set or not, once OSD loaded, begin page tracking.
          Drupal.openSeadragonViewer[base].addOnceHandler('open', function(){
            Drupal.openSeadragonViewer[base].bookmarkUrl({trackPage: true});
          });
        });
      });
    }
  };

})($, Drupal, drupalSettings, once);
