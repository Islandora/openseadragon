<?php

namespace Drupal\openseadragon;

/**
 * Special config class.
 *
 * This class allows the default config structure to remain, this is used to
 * clean up the settings form values and remove extra unnecessary arrays.
 */
interface ConfigInterface {

  /**
   * Get the default viewer settings.
   *
   * @param bool $filterNull
   *   Whether to filter out NULL values from array.
   *
   * @return array
   *   The default settings.
   */
  public function getDefaultSettings(bool $filterNull = FALSE);

  /**
   * Get the current viewer settings.
   *
   * @param bool $filterNull
   *   Whether to filter out NULL values from array.
   *
   * @return array
   *   The user configured settings.
   */
  public function getSettings(bool $filterNull = FALSE);

  /**
   * Get the current iiif server URL.
   *
   * @return string
   *   The iiif server URL, or NULL if none set.
   */
  public function getIiifAddress();

}
