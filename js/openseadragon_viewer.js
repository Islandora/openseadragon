/*jslint browser: true*/
/*global OpenSeadragon, Drupal*/
/**
 * @file
 * Displays OpenSeadragon viewer.
 */
(function(once) {
    'use strict';

    /**
     * The DOM element that represents the Singleton Instance of this class.
     * @type {string}
     */
    var base = '#openseadragon-viewer';

    /**
     * Initialize the OpenSeadragon Viewer.
     */
    Drupal.behaviors.openSeadragon = {
        attach: function(context, settings) {
            Object.keys(settings.openseadragon).forEach(function(osdViewerId) {
              // Use custom element #id if set.
              base = '#' + osdViewerId;
              once('openSeadragonViewer', base, context).forEach(function () {
                    Drupal.openSeadragonViewer[base] = new Drupal.openSeadragonViewer(base, settings.openseadragon[osdViewerId]);
              });
            });
        },
        detach: function(context, settings, trigger) {
            Object.keys(settings.openseadragon).forEach(function(osdViewerId) {
                // Use custom element #id if set.
                base = '#' + osdViewerId;
                once.remove('openSeadragonViewer', base, context).forEach(function () {
                    delete Drupal.openSeadragonViewer[base];
                });
            });
        }
    };

    /**
     * Creates an instance of the OpenSeadragon Viewer widget.
     *
     * @param {string} base
     *   The element ID that this class is bound to.
     * @param {object} settings
     *   Drupal.settings for this object widget.
     *
     * @constructor
     */
    Drupal.openSeadragonViewer = function (base, settings) {
        var viewer = new OpenSeadragon(settings.options);

        var update_clip = function(event) {
            var viewer = event.eventSource;
            var fitWithinBoundingBox = function(d, max) {
                if (d.width / d.height > max.x / max.y) {
                    return new OpenSeadragon.Point(max.x, parseInt(d.height * max.x / d.width));
                } else {
                    return new OpenSeadragon.Point(parseInt(d.width * max.y / d.height), max.y);
                }
            };

            var getDisplayRegion = function(viewer, source) {
                // Determine portion of scaled image that is being displayed.
                var box = new OpenSeadragon.Rect(0, 0, source.x, source.y);
                var container = viewer.viewport.getContainerSize();
                var bounds = viewer.viewport.getBounds();
                // If image is offset to the left.
                if (bounds.x > 0){
                    box.x = box.x - viewer.viewport.pixelFromPoint(new OpenSeadragon.Point(0,0)).x;
                }
                // If full image doesn't fit.
                if (box.x + source.x > container.x) {
                    box.width = container.x - viewer.viewport.pixelFromPoint(new OpenSeadragon.Point(0,0)).x;
                    if (box.width > container.x) {
                        box.width = container.x;
                    }
                }
                // If image is offset up.
                if (bounds.y > 0) {
                    box.y = box.y - viewer.viewport.pixelFromPoint(new OpenSeadragon.Point(0,0)).y;
                }
                // If full image doesn't fit.
                if (box.y + source.y > container.y) {
                    box.height = container.y - viewer.viewport.pixelFromPoint(new OpenSeadragon.Point(0,0)).y;
                    if (box.height > container.y) {
                        box.height = container.y;
                    }
                }
                return box;
            };
            var source = viewer.source;
            var zoom = viewer.viewport.getZoom();
            var size = new OpenSeadragon.Rect(0, 0, source.dimensions.x, source.dimensions.y);
            var container = viewer.viewport.getContainerSize();
            var fit_source = fitWithinBoundingBox(size, container);
            var total_zoom = fit_source.x / source.dimensions.x;
            var container_zoom = fit_source.x / container.x;
            var level = (zoom * total_zoom) / container_zoom;
            var box = getDisplayRegion(viewer, new OpenSeadragon.Point(parseInt(source.dimensions.x * level), parseInt(source.dimensions.y * level)));
            var scaled_box = new OpenSeadragon.Rect(parseInt(box.x / level), parseInt(box.y / level), parseInt(box.width / level), parseInt(box.height / level));
            var params = {
                    'identifier': source['@id'],
                    'region': scaled_box.x + ',' + scaled_box.y + ',' + (scaled_box.getBottomRight().x - scaled_box.x) + ',' + (scaled_box.getBottomRight().y - scaled_box.y),
                    'size': (zoom <= 1) ? source.dimensions.x + ',' + source.dimensions.y : container.x + ',' + container.y
                };
           // $("#clip").attr('href',  Drupal.settings.basePath + 'islandora/object/' + settings.pid + '/print?' + $.param({
           //         'clip': $.param(params)
           //     }));
        };

        viewer.addHandler("open", update_clip);
        viewer.addHandler("animation-finish", update_clip);

        if (settings.fitToAspectRatio) {
            viewer.addHandler("open", function (event) {
                var viewer = event.eventSource;
                if (viewer.source.aspectRatio / viewer.viewport.getAspectRatio() <= 1) {
                    viewer.viewport.fitVertically();
                }
                else {
                    viewer.viewport.fitHorizontally();
                }
            });
        }

    };
})(once);
