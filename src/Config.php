<?php

namespace Drupal\openseadragon;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration for the openseadragon viewer.
 *
 * @package Drupal\openseadragon
 */
class Config implements ConfigInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The openseadragon config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The OpenSeadragon config name.
   *
   * @var string
   */
  private static $configName = 'openseadragon.settings';

  /**
   * The config default settings key.
   *
   * @var string
   */
  private static $defaultKey = 'default_options';

  /**
   * The config user customized key.
   *
   * @var string
   */
  private static $userKey = 'viewer_options';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injected config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(Config::$configName);
    $this->configFactory = $configFactory;
    $this->addCacheableDependency($this->config);
  }

  /**
   * Factory.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Injected container.
   *
   * @return static
   *   \Drupal\openseadragon\Config
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings(bool $filterNull = FALSE) {
    $settings = $this->config->get(Config::$defaultKey);
    if ($filterNull) {
      $settings = $this->filterNull($settings);
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(bool $filterNull = FALSE) {
    $settings = $this->config->get(Config::$userKey);
    $default = $this->config->get(Config::$defaultKey);
    if (!is_null($settings)) {
      $settings = $settings + $default;
    }
    else {
      $settings = $default;
    }
    if ($filterNull) {
      $settings = $this->filterNull($settings);
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getIiifAddress() {
    return $this->config->get('iiif_server');
  }

  /**
   * {@inheritdoc}
   */
  public function getManifestView() {
    return $this->config->get('manifest_view');
  }

  /**
   * Filter out NULL values from the array.
   *
   * @param array $configArray
   *   Array to filter.
   *
   * @return array
   *   The array without NULL values.
   */
  private function filterNull(array $configArray) {
    // We have to filter NULL values, to prevent issues with javascript.
    $is_not_null = function ($val) {
      return !is_null($val);
    };
    return array_filter($configArray, $is_not_null);
  }

}
