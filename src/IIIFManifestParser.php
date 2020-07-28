<?php

namespace Drupal\openseadragon;

use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\Core\Routing\RouteMatchInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

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
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Token $token,
    RouteMatchInterface $route_match,
    Client $http_client,
    LoggerInterface $logger
  ) {
    $this->token = $token;
    $this->routeMatch = $route_match;
    $this->httpClient = $http_client;
    $this->logger = $logger;
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

    // Try to construct the URL out of a tokenized string
    // if the node is available.
    $current_node = $this->routeMatch->getParameter('node');
    if ($current_node) {
      $manifest_url = $this->token->replace($manifest_url, ['node' => $current_node]);
    }

    // If the URL is relative, make it absolute.
    if (substr($manifest_url, 0, 4) !== "http") {
      $manifest_url = ltrim($manifest_url, '/');
      $manifest_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString() . $manifest_url;
    }

    try {
      // Request the manifest.
      $manifest_response = $this->httpClient->get($manifest_url);

      // Decode the manifest json.
      $manifest_string = (string) $manifest_response->getBody();
      $manifest = json_decode($manifest_string, TRUE);

      // Exit early if the request does not contain JSON.
      if (empty($manifest)) {
        $this->logger->warning("Could not decode the manifest contents into JSON: $manifest_string");
        return FALSE;
      }

      // Hack the tile sources out of the manifest.
      $tile_sources = [];
      foreach ($manifest['sequences'] as $sequence) {
        if (!isset($sequence['canvases']) || empty($sequence['canvases'])) {
          continue;
        }

        foreach ($sequence['canvases'] as $canvas) {
          if (!isset($canvas['images']) || empty($canvas['images'])) {
            continue;
          }

          foreach ($canvas['images'] as $key => $image) {
            if (is_numeric($key)) {
              $tile_sources[] = $image['resource']['service']['@id'];
            }
          }
        }
      }

      return $tile_sources;
    }
    catch (RequestException $e) {
      $this->logger->warning("Request for IIIF manifest at $manifest_url returned {$e->getCode()}");
      return FALSE;
    }
  }

}
