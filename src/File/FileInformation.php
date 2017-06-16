<?php

namespace Drupal\openseadragon\File;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Class FileInformation.
 *
 * @package Drupal\openseadragon\File
 */
class FileInformation implements FileInformationInterface {

  /**
   * StreamWrapper to dereference uris to path (ie. public://).
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  private $streamWrapper;

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
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   Stream Wrapper manager interface.
   */
  public function __construct(MimeTypeGuesserInterface $mimeTypeGuesser, StreamWrapperManagerInterface $streamWrapperManager) {
    $this->mimetypeGuesser = $mimeTypeGuesser;
    $this->streamWrapper = $streamWrapperManager;
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
    return new static($container->get('file.mime_type.guesser'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFileData($field_name, EntityInterface $entity) {
    $output = [];
    $field_value = $entity->get($field_name)->getValue();
    if (isset($field_value['0']['target_id'])) {
      // Load the image and take file uri.
      $fid = $field_value[0]['target_id'];
      $file = file_load($fid);

      $uri = $file->getFileUri();

      $mime_type = $file->getMimeType();
      if (strpos($mime_type, 'image/') != 0) {
        // Try a better mimetype guesser.
        $mime_type = $this->mimetypeGuesser->guess($uri);
        if (strpos($mime_type, 'image/') != 0) {
          // If we still don't have an image. Exit.
          return $output;
        }
      }
      $output['mime_type'] = $mime_type;

      $stream_wrapper_manager = $this->streamWrapper->getViaUri($uri);
      $output['full_path'] = $stream_wrapper_manager->realpath();
    }
    return $output;
  }

}
