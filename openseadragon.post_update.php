<?php

/**
 * Update configuration to match the new schema structure.
 *
 * {@inheritdoc}
 */
function openseadragon_post_update_expand_config_data(&$sandbox) {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('openseadragon.settings');
  // Remove deprecated navigatorAutoResize.
  $message = '';
  if ($config->get('navigatorAutoResize') !== NULL) {
    $config->clear('navigatorAutoResize');
    $message = t('Removed deprecated navigatorAutoResize setting.');
  }
  // Save config back to pickup new schema.
  $config->save(TRUE);
  if (isset($message)) {
    return $message;
  }
}
