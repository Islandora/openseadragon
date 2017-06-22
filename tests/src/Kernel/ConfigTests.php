<?php

namespace Drupal\Tests\openseadragon\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\openseadragon\File\FileInformation;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class ConfigTests.
 *
 * @package Drupal\Tests\openseadragon\Kernel
 * @group openseadragon
 * @coversDefaultClass Drupal\openseadragon\File\FileInformation
 */
class ConfigTests extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'openseadragon',
  ];

  /**
   * The mimetype guesser prophecy.
   *
   * @var Prophecy\Prophet
   */
  private $mimeProphet;

  /**
   * The stream wrapper prophecy.
   *
   * @var Prophecy\Prophet
   */
  private $streamProphet;

  /**
   * The Public Stream result of the stream wrapper.
   *
   * @var Prophecy\Prophet
   */
  private $publicStreamProphet;

  /**
   * The file entity prophecy.
   *
   * @var Prophecy\Prophet
   */
  private $fileProphet;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->streamProphet = $this->prophesize(StreamWrapperManagerInterface::class);

    $this->mimeProphet = $this->prophesize(MimeTypeGuesserInterface::class);

    $this->fileProphet = $this->prophesize(File::class);

    $this->publicStreamProphet = $this->prophesize(PublicStream::class);
  }

  /**
   * @covers ::getFileData
   */
  public function testFileWithValidMime() {
    $file_uri = 'public://temp_files/test_file.jpg';
    $full_path = '/tmp/drupal/temp_files/test_file.jpg';
    $this->fileProphet->getFileUri()->willReturn($file_uri);
    $this->fileProphet->getMimeType()->willReturn('image/jpeg');

    $this->publicStreamProphet->realpath()->willReturn($full_path);
    $public_stream = $this->publicStreamProphet->reveal();

    $this->streamProphet->getViaUri($file_uri)->willReturn($public_stream);

    $mime_guesser = $this->mimeProphet->reveal();
    $stream_wrapper = $this->streamProphet->reveal();
    $file = $this->fileProphet->reveal();

    $file_information = new FileInformation($mime_guesser, $stream_wrapper);
    $result = $file_information->getFileData($file);

    $this->assertEquals('image/jpeg', $result['mime_type'], "MimeType does not match");
    $this->assertEquals($full_path, $result['full_path'], 'Full path does not match');
  }

  /**
   * @covers ::getFileData
   */
  public function testFileWithInvalidMime() {
    $file_uri = 'public://temp_files/test_file.jpg';
    $full_path = '/tmp/drupal/temp_files/test_file.jpg';
    $this->fileProphet->getFileUri()->willReturn($file_uri);
    $this->fileProphet->getMimeType()->willReturn('application/octet-stream');
    $this->mimeProphet->guess($file_uri)->willReturn('image/jp2');

    $this->publicStreamProphet->realpath()->willReturn($full_path);
    $public_stream = $this->publicStreamProphet->reveal();

    $this->streamProphet->getViaUri($file_uri)->willReturn($public_stream);

    $mime_guesser = $this->mimeProphet->reveal();
    $stream_wrapper = $this->streamProphet->reveal();
    $file = $this->fileProphet->reveal();

    $file_information = new FileInformation($mime_guesser, $stream_wrapper);
    $result = $file_information->getFileData($file);

    $this->assertEquals('image/jp2', $result['mime_type'], "MimeType does not match");
    $this->assertEquals($full_path, $result['full_path'], 'Full path does not match');
  }

}
