<?php

namespace Drupal\openseadragon\File;

use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class FileInformation.
 *
 * @package Drupal\openseadragon\File
 */
class FileInformation implements FileInformationInterface {

  /**
   * File MimeType Guesser to use extension to determine file type.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  private $mimetypeGuesser;

  /**
   * FileInformation constructor.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   File mimetype guesser interface.
   */
  public function __construct(MimeTypeGuesserInterface $mimeTypeGuesser) {
    $this->mimetypeGuesser = $mimeTypeGuesser;
  }

  /**
   * Static constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('file.mime_type.guesser'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFileData(File $file) {
    $output = [];
    $uri = $file->getFileUri();
    $mime_type = $file->getMimeType();
    if (strpos($mime_type, 'image/') === FALSE) {
      // Try a better mimetype guesser.
      $mime_type = $this->mimetypeGuesser->guess($uri);
      if (strpos($mime_type, 'image/') === FALSE) {
        // If we still don't have an image. Exit.
        return $output;
      }
    }
    $output['mime_type'] = $mime_type;
    $output['full_path'] = $file->url();
    return $output;
  }

}
