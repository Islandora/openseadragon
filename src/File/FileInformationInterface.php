<?php

namespace Drupal\openseadragon\File;

use Drupal\file\Entity\File;

/**
 * Class to get file path information for dereferencing files in Entity fields.
 */
interface FileInformationInterface {

  /**
   * Get the full_path and mime-type to the file.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file to get information about.
   *
   * @return array
   *   Data about the file contents, required keys are mime_type and full_path.
   */
  public function getFileData(File $file);

}
