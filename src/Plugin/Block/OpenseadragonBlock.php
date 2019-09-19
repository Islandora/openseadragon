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
    $form['iiif_manifest_url_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('IIIF Manifest URL'),
    ];
    $form['iiif_manifest_url_fieldset']['iiif_manifest_url'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Absolute URL of the IIIF manifest to render.  You may use tokens to provide a pattern (e.g. "http://localhost/node/[node:nid]/manifest")'),
      '#default_value' => $this->configuration['iiif_manifest_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['iiif_manifest_url_fieldset']['token_help'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('node'),
    );


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
