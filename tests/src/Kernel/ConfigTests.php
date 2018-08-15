<?php

namespace Drupal\Tests\openseadragon\Kernel;

use Drupal\Core\Url;
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

    $this->mimeProphet = $this->prophesize(MimeTypeGuesserInterface::class);
    $this->fileProphet = $this->prophesize(File::class);
  }

  /**
   * @covers ::getFileData
   */
  public function testFileWithValidMime() {
    $file_uri = 'public://temp_files/test_file.jpg';

    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $full_path = "$base/sites/default/files/temp_files/test_file.jpg";

    $this->fileProphet->getFileUri()->willReturn($file_uri);
    $this->fileProphet->getMimeType()->willReturn('image/jpeg');
    $this->fileProphet->url()->willReturn($full_path);

    $mime_guesser = $this->mimeProphet->reveal();
    $file = $this->fileProphet->reveal();

    $file_information = new FileInformation($mime_guesser);
    $result = $file_information->getFileData($file);

    $this->assertEquals('image/jpeg', $result['mime_type'], "MimeType does not match");
    $this->assertEquals($full_path, $result['full_path'], 'Full path does not match');
  }

  /**
   * @covers ::getFileData
   */
  public function testFileWithInvalidMime() {
    $file_uri = 'public://temp_files/test_file.jpg';

    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $full_path = "$base/sites/default/files/temp_files/test_file.jpg";

    $this->fileProphet->getFileUri()->willReturn($file_uri);
    $this->fileProphet->getMimeType()->willReturn('application/octet-stream');
    $this->fileProphet->url()->willReturn($full_path);

    $this->mimeProphet->guess($file_uri)->willReturn('image/jp2');

    $mime_guesser = $this->mimeProphet->reveal();
    $file = $this->fileProphet->reveal();

    $file_information = new FileInformation($mime_guesser);
    $result = $file_information->getFileData($file);

    $this->assertEquals('image/jp2', $result['mime_type'], "MimeType does not match");
    $this->assertEquals($full_path, $result['full_path'], 'Full path does not match');
  }

}
