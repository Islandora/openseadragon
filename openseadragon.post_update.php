<?php

/**
 * @file
 * Contains post_update hooks for openseadragon.
 */

/**
 * Update configuration to match the new schema structure.
 *
 * {@inheritdoc}
 */
function openseadragon_post_update_expand_config_data(&$sandbox) {
  $config = \Drupal::configFactory()->getEditable('openseadragon.settings');
  // Remove deprecated setting.
  $config->clear('navigatorAutoResize');
  $boolean_keys = [
    'fit_to_aspect_ratio',
    'debugMode',
    'alwaysBlend',
    'autoHideControls',
    'immediateRender',
    'homeFillsViewer',
    'panHorizontal',
    'panVertical',
    'constrainDuringPan',
    'wrapHorizontal',
    'wrapVertical',
    'autoResize',
    'preserveImageSizeOnResize',
    'mouseNavEnabled',
    'showNavigationControl',
    'showZoomControl',
    'showHomeControl',
    'showFullPageControl',
    'showRotationControl',
    'showNavigator',
    'navigatorMaintainSizeRatio',
    'navigatorAutoFade',
    'navigatorRotate',
    'showSequenceControl',
    'navPrevNextWrap',
    'sequenceMode',
    'preserveViewport',
    'preserveOverlays',
    'showReferenceStrip',
    'collectionMode',
    'ajaxWithCredentials',
    'useCanvas',
  ];
  $gesture_boolean_keys = [
    'scrollToZoom',
    'clickToZoom',
    'dblClickToZoom',
    'pinchToZoom',
    'flickEnabled',
    'pinchRotate',
  ];
  foreach (['default_options', 'viewer_options'] as $root_key) {
    $options = $config->get($root_key);
    if (!is_array($options)) {
      continue;
    }
    foreach ($boolean_keys as $key) {
      if (array_key_exists($key, $options)) {
        $options[$key] = (bool) $options[$key];
      }
    }
    foreach ([
      'gestureSettingsMouse',
      'gestureSettingsTouch',
      'gestureSettingsPen',
      'gestureSettingsUnknown',
    ] as $gesture_key) {
      if (!empty($options[$gesture_key]) && is_array($options[$gesture_key])) {
        foreach ($gesture_boolean_keys as $key) {
          if (array_key_exists($key, $options[$gesture_key])) {
            $options[$gesture_key][$key] = (bool) $options[$gesture_key][$key];
          }
        }
      }
    }
    $config->set($root_key, $options);
  }
  $config->save(TRUE);
  return t('Normalized OpenSeadragon settings to match the expanded config schema.');
}
