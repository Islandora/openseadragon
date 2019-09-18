<?php

namespace Drupal\openseadragon\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'OpenseadragonBlock' block from a IIIF manifest.
 *
 * @Block(
 *  id = "openseadragon_block",
 *  admin_label = @Translation("Openseadragon block"),
 * )
 */
class OpenseadragonBlock extends BlockBase {

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
    $form['iiif_manifest_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IIIF Manifest URL'),
      '#description' => $this->t('URL of the IIIF manifest to render.  You may use tokens to provide a pattern (e.g. "http://localhost/node/[node:id]/manifest"'),
      '#default_value' => $this->configuration['iiif_manifest_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['iiif_manifest_url'] = $form_state->getValue('iiif_manifest_url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['openseadragon_block'] = [
      '#theme' => 'openseadragon_iiif_manifest_block',
      '#iiif_manifest_url' => $this->configuration['iiif_manifest_url'],
    ];

    return $build;
  }

}
