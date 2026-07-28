<?php

declare(strict_types=1);

namespace Drupal\openseadragon_bookmark_url;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Typed accessor service for openseadragon_bookmark_url.settings.
 *
 * Usage (from a service or controller):
 * @code
 *   $config = \Drupal::service('openseadragon_bookmark_url.config');
 *   $bundle = $config->getBundle();
 *   $field  = $config->getField();
 * @endcode
 */
final class BookmarkUrlConfig {

  private const CONFIG_NAME = 'openseadragon_bookmark_url.settings';

  /**
   * The openseadragon bookmark url config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * Constructs a BookmarkUrlConfig instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal config factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(self::CONFIG_NAME);
  }

  /**
   * Returns the configured node bundle machine name, or an empty string.
   */
  public function getBundle(): string {
    return $this->config->get('bookmark_url_bundle') ?? '';
  }

  /**
   * Returns the configured field machine name, or an empty string.
   */
  public function getField(): string {
    return $this->config->get('bookmark_url_field') ?? '';
  }

  /**
   * Returns TRUE when both bundle and field have been set by an administrator.
   */
  public function isConfigured(): bool {
    return $this->getBundle() !== '' && $this->getField() !== '';
  }

}
