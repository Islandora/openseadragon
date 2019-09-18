<?php

use Drupal\Core\Utility\Token;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Utility class to extract tile sources from a IIIF manifest.
 *
 * @package Drupal\openseadragon
 */
class IIIFManifestParser {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */	
  protected $token;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */	
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Token $token,
    RouteMatchInterface $route_match  
  ) {
    $this->token = $token;
    $this->routeMatch = $route_match;
  }

  /**
   * Extracts the list of tiles sources from a IIIF manifest.
   *
   * @param string $manifest_url
   *   The location of the IIIF manifest, which can include tokens.
   *
   * @return array
   *   The URLs of all the tile sources in a manifest.
   */
  public function getTileSources($manifest_url) {
    // Try to construct the URL out of a tokenized string if the node is available.
    $current_node = $this->routeMatch->getParameter('node');
    if ($current_node) {
      $manifest_url = $this->token->replace($manifest_url, ['node' => $current_node]);
    }

    // Request the manifest.
    $manifest = file_get_contents($manifest_url);
    dsm($manifest);
    if (empty($manifest)) {
      return FALSE;
    }

    // Hack the tile sources out of the manifest.
    $manifest = json_decode($manifest, TRUE);
    $tile_sources = [];
    foreach ($manifest['sequences'] as $sequence) {
      foreach ($sequence['canvases'] as $canvas) {
        foreach ($canvas['images'] as $key => $image) {
          if (is_numeric($key)) {
            $tile_sources[] = $image['resource']['service']['@id'];
          }
        }
      }
    }

    return $tile_sources;
  }

}
