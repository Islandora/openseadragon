<?php

namespace Drupal\openseadragon\File;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class to get file path information for dereferencing files in Entity fields.
 */
interface FileInformationInterface {

  /**
   * Get the full_path and mime-type to the file.
   *
   * @param string $field_name
   *   The file field name on the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Data about the file contents, keys are (mime_type and full_path).
   */
  public function getFileData($field_name, EntityInterface $entity);

}
