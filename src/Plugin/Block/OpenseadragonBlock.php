<?php

namespace Drupal\openseadragon\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\openseadragon\ConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OpenseadragonBlock' block from a IIIF manifest.
 *
 * @Block(
 *  id = "openseadragon_block",
 *  admin_label = @Translation("Openseadragon block"),
 * )
 */
class OpenseadragonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Views storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewsStorage;

  /**
   * OpenSeadragon Config.
   *
   * @var \Drupal\openseadragon\ConfigInterface
   */
  protected $seadragonConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    EntityStorageInterface $views_storage,
    ConfigInterface $seadragon_config
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->viewsStorage = $views_storage;
    $this->seadragonConfig = $seadragon_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('openseadragon.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['iiif_manifest_url_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('IIIF Manifest URL'),
    ];
    $form['iiif_manifest_url_fieldset']['iiif_manifest_url'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Relative path or URL of the IIIF manifest to render. You may use tokens to provide a pattern (e.g. "http://example.org/node/[node:nid]/manifest", or simply "node/[node:nid]/manifest" if you are referring to your own site)'),
      '#default_value' => $this->configuration['iiif_manifest_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => ['node'],
    ];
    $form['iiif_manifest_url_fieldset']['token_help'] = [
      '#theme' => 'token_tree_link',
      '#global_types' => FALSE,
      '#token_types' => ['node'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['iiif_manifest_url'] = $form_state->getValue(['iiif_manifest_url_fieldset', 'iiif_manifest_url']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache_tags = Cache::mergeTags(
      parent::getCacheTags(),
      ['node_list', 'media_list']
    );

    $view_id = $this->seadragonConfig->getManifestView();
    $view = $this->viewsStorage->load($view_id);
    if ($view) {
      $cache_tags = Cache::mergeTags(
        $cache_tags,
        $view->getCacheTags()
      );
    }

    if ($node = $this->routeMatch->getParameter('node')) {
      $cache_tags = Cache::mergeTags(
        $cache_tags,
        ['node:' . $node->id()]
      );
    }

    $build = [];
    $build['openseadragon_block'] = [
      '#theme' => 'openseadragon_iiif_manifest_block',
      '#iiif_manifest_url' => $this->configuration['iiif_manifest_url'],
      '#cache' => [
        'contexts' => Cache::mergeContexts(parent::getCacheContexts(), ['route']),
        'tags' => $cache_tags,
      ],
    ];

    return $build;
  }

}
