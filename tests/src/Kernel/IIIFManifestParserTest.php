<?php

namespace Drupal\Tests\openseadragon\Kernel;

use Drupal\Core\Utility\Token;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\openseadragon\IIIFManifestParser;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Class IIIFManifestParserTest.
 *
 * @package Drupal\Tests\openseadragon\Kernel
 * @group openseadragon
 * @coversDefaultClass Drupal\openseadragon\IIIFManifestParser
 */
class IIIFManifestParserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'node',
    'user',
    'openseadragon',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Bootstrap minimal Drupal environment to run the tests.
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create a test content type.
    $this->testType = $this->container->get('entity_type.manager')->getStorage('node_type')->create([
      'type' => 'test_type',
      'name' => 'Test Type',
    ]);
  }

  /**
   * @covers ::getTileSources
   */
  public function testGetTileSourcesReturnsFalseOnRequestFail() {

    $token = $this->prophesize(Token::class)->reveal();

    $route_match = $this->prophesize(RouteMatchInterface::class)->reveal();

    $mock = new MockHandler([
      new Response(404, [], "Not Found"),
    ]);
    $handler = HandlerStack::create($mock);
    $http_client = new Client(['handler' => $handler]);

    $logger = $this->prophesize(LoggerInterface::class)->reveal();

    $parser = new IIIFManifestParser(
      $token,
      $route_match,
      $http_client,
      $logger
    );

    $this->assertFalse(
      $parser->getTileSources('http://example.org/does/not/exist'),
      "Parser should return FALSE if manifest URL does not return 200"
    );
  }

  /**
   * @covers ::getTileSources
   */
  public function testGetTileSourcesReturnsFalseOnEmptyManifest() {

    $token = $this->prophesize(Token::class)->reveal();

    $route_match = $this->prophesize(RouteMatchInterface::class)->reveal();

    $mock = new MockHandler([
      new Response(204, [], ""),
    ]);
    $handler = HandlerStack::create($mock);
    $http_client = new Client(['handler' => $handler]);

    $logger = $this->prophesize(LoggerInterface::class)->reveal();

    $parser = new IIIFManifestParser(
      $token,
      $route_match,
      $http_client,
      $logger
    );

    $this->assertFalse(
      $parser->getTileSources('http://example.org/exists/but/is/empty'),
      "Parser should return FALSE if manifest URL does not a response body"
    );
  }

  /**
   * @covers ::getTileSources
   */
  public function testGetTileSourcesReturnsFalseOnMalformedManifest() {

    $token = $this->prophesize(Token::class)->reveal();

    $route_match = $this->prophesize(RouteMatchInterface::class)->reveal();

    $mock = new MockHandler([
      new Response(200, [], "<xml>Totally not json</xml>"),
    ]);
    $handler = HandlerStack::create($mock);
    $http_client = new Client(['handler' => $handler]);

    $logger = $this->prophesize(LoggerInterface::class)->reveal();

    $parser = new IIIFManifestParser(
      $token,
      $route_match,
      $http_client,
      $logger
    );

    $this->assertFalse(
      $parser->getTileSources('http://example.org/exists/but/is/malformed'),
      "Parser should return FALSE if manifest URL does not return JSON"
    );
  }

  /**
   * @covers ::getTileSources
   */
  public function testGetTileSources() {

    $token = $this->prophesize(Token::class)->reveal();

    $route_match = $this->prophesize(RouteMatchInterface::class)->reveal();

    $manifest = file_get_contents(__DIR__ . '/../../resources/manifest.json');

    $mock = new MockHandler([
      new Response(200, [], $manifest),
    ]);

    $handler = HandlerStack::create($mock);
    $http_client = new Client(['handler' => $handler]);

    $logger = $this->prophesize(LoggerInterface::class)->reveal();

    $parser = new IIIFManifestParser(
      $token,
      $route_match,
      $http_client,
      $logger
    );

    $tile_sources = $parser->getTileSources('http://example.org/manifest');
    $expected = [
      'http://127.0.0.1:8080/cantaloupe/iiif/2/derp.TIF',
      'http://127.0.0.1:8080/cantaloupe/iiif/2/derp.jpeg',
    ];
    $this->assertTrue(
      empty(array_diff($tile_sources, $expected)),
      "Expected " . json_encode($tile_sources) . ", recieved " . json_encode($tile_sources)
    );
  }

  /**
   * @covers ::getTileSources
   */
  public function testGetTileSourcesWithTokens() {

    $token = $this->container->get('token');

    $node = Node::create(['type' => 'test_type', 'title' => 'Test']);
    $node->save();

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getParameter('node')->willReturn($node);
    $route_match = $route_match->reveal();

    $manifest = file_get_contents(__DIR__ . '/../../resources/manifest.json');
    $http_client = $this->prophesize(Client::class);
    $http_client->get("http://example.org/node/{$node->id()}/manifest")->willReturn(new Response(200, [], $manifest));
    $http_client = $http_client->reveal();

    $logger = $this->prophesize(LoggerInterface::class)->reveal();

    $parser = new IIIFManifestParser(
      $token,
      $route_match,
      $http_client,
      $logger
    );

    $tile_sources = $parser->getTileSources('http://example.org/node/[node:nid]/manifest');
    $expected = [
      'http://127.0.0.1:8080/cantaloupe/iiif/2/derp.TIF',
      'http://127.0.0.1:8080/cantaloupe/iiif/2/derp.jpeg',
    ];
    $this->assertTrue(
      empty(array_diff($tile_sources, $expected)),
      "Expected " . json_encode($tile_sources) . ", recieved " . json_encode($tile_sources)
    );
  }

}
