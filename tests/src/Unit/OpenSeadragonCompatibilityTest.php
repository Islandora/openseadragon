<?php

namespace Drupal\Tests\openseadragon\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;

/**
 * Verifies local OpenSeadragon config remains compatible with upstream OSD.
 *
 * @group openseadragon
 */
class OpenSeadragonCompatibilityTest extends UnitTestCase {

  /**
   * Settings owned by this module rather than upstream OpenSeadragon.
   */
  private const MODULE_LOCAL_OPTIONS = [
    'fit_to_aspect_ratio',
  ];

  /**
   * Ensures configured default option keys still exist in upstream OSD.
   */
  public function testDefaultOptionsExistInConfiguredOsdVersion() : void {
    $module_root = dirname(__DIR__, 3);
    $libraries_yml = file_get_contents($module_root . '/openseadragon.libraries.yml');
    $settings_yml = file_get_contents($module_root . '/config/install/openseadragon.settings.yml');

    $this->assertNotFalse($libraries_yml, 'Failed to read openseadragon.libraries.yml.');
    $this->assertNotFalse($settings_yml, 'Failed to read default config.');

    preg_match('/https:\/\/cdn\.jsdelivr\.net\/npm\/openseadragon@[^\s]+\/openseadragon(?:\.min)?\.js/', $libraries_yml, $matches);
    $this->assertNotEmpty($matches, 'Could not find OpenSeadragon library URL.');

    $osd_url = str_replace('.min.js', '.js', $matches[0]);
    $osd_source = @file_get_contents($osd_url);
    $this->assertNotFalse($osd_source, sprintf('Failed to fetch %s.', $osd_url));

    $default_options = Yaml::decode($settings_yml)['default_options'] ?? [];
    $osd_options = $this->mergeStructures(
      $this->extractOptionsStructure($osd_source),
      $this->extractDefaultSettingsStructure($osd_source)
    );

    foreach ($default_options as $key => $value) {
      if (in_array($key, self::MODULE_LOCAL_OPTIONS, TRUE)) {
        continue;
      }

      $this->assertArrayHasKey($key, $osd_options, sprintf('Config key "%s" is not documented in OpenSeadragon.Options.', $key));

      if (!is_array($value)) {
        continue;
      }

      $this->assertIsArray($osd_options[$key], sprintf('Config key "%s" is expected to be a nested object in OpenSeadragon.Options.', $key));
      foreach (array_keys($value) as $nested_key) {
        $this->assertArrayHasKey($nested_key, $osd_options[$key], sprintf('Config key "%s.%s" is not documented in OpenSeadragon.Options.', $key, $nested_key));
      }
    }
  }

  /**
   * Ensures the settings form reads defaults from the expected config keys.
   */
  public function testSettingsFormDefaultMappingsMatchConfigKeys() : void {
    $module_root = dirname(__DIR__, 3);
    $form_php = file_get_contents($module_root . '/src/Form/OpenSeadragonSettingsForm.php');
    $settings_yml = file_get_contents($module_root . '/config/install/openseadragon.settings.yml');

    $this->assertNotFalse($form_php, 'Failed to read settings form.');
    $this->assertNotFalse($settings_yml, 'Failed to read default config.');

    $default_options = Yaml::decode($settings_yml)['default_options'] ?? [];
    $lines = explode("\n", $form_php);
    $current_gesture_section = NULL;

    foreach ($lines as $index => $line) {
      if (preg_match('/^\s+\'(gestureSettings[A-Za-z]+)\'\s*=>\s*\[$/', $line, $section_match)) {
        $current_gesture_section = $section_match[1];
      }

      if (!preg_match('/#default_value\' => \$settings\[\'([A-Za-z0-9_]+)\'\](?:\[\'([A-Za-z0-9_]+)\'\])?/', $line, $value_match)) {
        continue;
      }

      [, $top_level_key, $nested_key] = $value_match + [NULL, NULL, NULL];
      $line_number = $index + 1;

      $this->assertArrayHasKey($top_level_key, $default_options, sprintf('Line %d references unknown config key "%s".', $line_number, $top_level_key));

      if ($nested_key === NULL) {
        continue;
      }

      $this->assertIsArray($default_options[$top_level_key], sprintf('Line %d expects "%s" to be a nested config array.', $line_number, $top_level_key));
      $this->assertArrayHasKey($nested_key, $default_options[$top_level_key], sprintf('Line %d references unknown nested config key "%s.%s".', $line_number, $top_level_key, $nested_key));

      if ($current_gesture_section !== NULL) {
        $this->assertSame($current_gesture_section, $top_level_key, sprintf('Line %d in %s reads defaults from %s.%s.', $line_number, $current_gesture_section, $top_level_key, $nested_key));
      }
    }
  }

  /**
   * Extracts the key structure of documented OpenSeadragon.Options properties.
   *
   * @param string $source
   *   The upstream OpenSeadragon source.
   *
   * @return array
   *   A nested map of setting keys.
   */
  private function extractOptionsStructure(string $source) : array {
    $structure = [];
    preg_match_all('/@property\s+\{[^}]+\}\s+\[?([A-Za-z0-9_.]+)/', $source, $matches);
    $this->assertNotEmpty($matches[1], 'No OpenSeadragon option properties were found in upstream source.');

    foreach ($matches[1] as $property_path) {
      $segments = explode('.', $property_path);
      $current = &$structure;

      foreach ($segments as $index => $segment) {
        if ($segment === '') {
          continue;
        }

        if ($index === count($segments) - 1) {
          $current[$segment] = $current[$segment] ?? TRUE;
          continue;
        }

        if (!isset($current[$segment]) || !is_array($current[$segment])) {
          $current[$segment] = [];
        }
        $current = &$current[$segment];
      }

      unset($current);
    }

    return $structure;
  }

  /**
   * Extracts the key structure of OpenSeadragon.DEFAULT_SETTINGS.
   *
   * @param string $source
   *   The upstream OpenSeadragon source.
   *
   * @return array
   *   A nested map of default setting keys.
   */
  private function extractDefaultSettingsStructure(string $source) : array {
    $marker = 'DEFAULT_SETTINGS';
    $marker_position = strpos($source, $marker);
    $this->assertNotFalse($marker_position, 'OpenSeadragon.DEFAULT_SETTINGS was not found.');

    $open_brace_position = strpos($source, '{', $marker_position);
    $this->assertNotFalse($open_brace_position, 'Could not locate DEFAULT_SETTINGS object literal.');

    $object_literal = $this->extractBalancedBlock($source, $open_brace_position, '{', '}');
    return $this->parseObjectStructure($object_literal);
  }

  /**
   * Parses a JavaScript object literal into a nested key map.
   *
   * @param string $object_literal
   *   The object literal including outer braces.
   *
   * @return array
   *   A nested map of property names.
   */
  private function parseObjectStructure(string $object_literal) : array {
    $content = trim(substr($object_literal, 1, -1));
    $length = strlen($content);
    $offset = 0;
    $structure = [];

    while ($offset < $length) {
      $this->skipWhitespaceCommentsAndCommas($content, $offset);
      if ($offset >= $length) {
        break;
      }

      $key = $this->readPropertyName($content, $offset);
      $this->skipWhitespaceComments($content, $offset);

      if (($content[$offset] ?? NULL) !== ':') {
        throw new \RuntimeException(sprintf('Malformed object literal near property "%s".', $key));
      }
      $offset++;

      $this->skipWhitespaceComments($content, $offset);
      $value_start = $content[$offset] ?? '';

      if ($value_start === '{') {
        $nested_literal = $this->extractBalancedBlock($content, $offset, '{', '}');
        $structure[$key] = $this->parseObjectStructure($nested_literal);
        $offset += strlen($nested_literal);
      }
      else {
        $structure[$key] = TRUE;
        $this->skipValueExpression($content, $offset);
      }
    }

    return $structure;
  }

  /**
   * Merges two nested key maps.
   */
  private function mergeStructures(array $left, array $right) : array {
    foreach ($right as $key => $value) {
      if (isset($left[$key]) && is_array($left[$key]) && is_array($value)) {
        $left[$key] = $this->mergeStructures($left[$key], $value);
        continue;
      }
      $left[$key] = $value;
    }

    return $left;
  }

  /**
   * Reads a JavaScript property name from an object literal.
   */
  private function readPropertyName(string $content, int &$offset) : string {
    $char = $content[$offset] ?? '';

    if ($char === "'" || $char === '"') {
      return $this->readQuotedString($content, $offset);
    }

    if (preg_match('/\G([A-Za-z0-9_$]+)/A', $content, $matches, 0, $offset)) {
      $offset += strlen($matches[1]);
      return $matches[1];
    }

    throw new \RuntimeException(sprintf('Unable to read property name near offset %d.', $offset));
  }

  /**
   * Skips a non-object JavaScript expression value.
   */
  private function skipValueExpression(string $content, int &$offset) : void {
    $length = strlen($content);
    $paren_depth = 0;
    $bracket_depth = 0;

    while ($offset < $length) {
      $char = $content[$offset];
      $next = $content[$offset + 1] ?? '';

      if ($char === "'" || $char === '"') {
        $this->readQuotedString($content, $offset);
        continue;
      }

      if ($char === '/' && $next === '/') {
        $offset += 2;
        while ($offset < $length && $content[$offset] !== "\n") {
          $offset++;
        }
        continue;
      }

      if ($char === '/' && $next === '*') {
        $offset += 2;
        while ($offset + 1 < $length && !($content[$offset] === '*' && $content[$offset + 1] === '/')) {
          $offset++;
        }
        $offset += 2;
        continue;
      }

      if ($char === '(') {
        $paren_depth++;
      }
      elseif ($char === ')') {
        $paren_depth--;
      }
      elseif ($char === '[') {
        $bracket_depth++;
      }
      elseif ($char === ']') {
        $bracket_depth--;
      }
      elseif ($char === ',' && $paren_depth === 0 && $bracket_depth === 0) {
        return;
      }

      $offset++;
    }
  }

  /**
   * Skips whitespace, comments, and commas.
   */
  private function skipWhitespaceCommentsAndCommas(string $content, int &$offset) : void {
    $length = strlen($content);

    while ($offset < $length) {
      $char = $content[$offset];
      $next = $content[$offset + 1] ?? '';

      if (ctype_space($char) || $char === ',') {
        $offset++;
        continue;
      }

      if ($char === '/' && $next === '/') {
        $offset += 2;
        while ($offset < $length && $content[$offset] !== "\n") {
          $offset++;
        }
        continue;
      }

      if ($char === '/' && $next === '*') {
        $offset += 2;
        while ($offset + 1 < $length && !($content[$offset] === '*' && $content[$offset + 1] === '/')) {
          $offset++;
        }
        $offset += 2;
        continue;
      }

      break;
    }
  }

  /**
   * Skips whitespace and comments.
   */
  private function skipWhitespaceComments(string $content, int &$offset) : void {
    $length = strlen($content);

    while ($offset < $length) {
      $char = $content[$offset];
      $next = $content[$offset + 1] ?? '';

      if (ctype_space($char)) {
        $offset++;
        continue;
      }

      if ($char === '/' && $next === '/') {
        $offset += 2;
        while ($offset < $length && $content[$offset] !== "\n") {
          $offset++;
        }
        continue;
      }

      if ($char === '/' && $next === '*') {
        $offset += 2;
        while ($offset + 1 < $length && !($content[$offset] === '*' && $content[$offset + 1] === '/')) {
          $offset++;
        }
        $offset += 2;
        continue;
      }

      break;
    }
  }

  /**
   * Reads a quoted JavaScript string and advances the offset past it.
   */
  private function readQuotedString(string $content, int &$offset) : string {
    $quote = $content[$offset];
    $offset++;
    $length = strlen($content);
    $value = '';

    while ($offset < $length) {
      $char = $content[$offset];

      if ($char === '\\') {
        $value .= $char . ($content[$offset + 1] ?? '');
        $offset += 2;
        continue;
      }

      if ($char === $quote) {
        $offset++;
        return stripcslashes($value);
      }

      $value .= $char;
      $offset++;
    }

    throw new \RuntimeException('Unterminated string literal.');
  }

  /**
   * Extracts a balanced delimited block starting at the given offset.
   */
  private function extractBalancedBlock(string $content, int $offset, string $open, string $close) : string {
    $length = strlen($content);
    $start = $offset;
    $depth = 0;

    while ($offset < $length) {
      $char = $content[$offset];
      $next = $content[$offset + 1] ?? '';

      if ($char === "'" || $char === '"') {
        $this->readQuotedString($content, $offset);
        continue;
      }

      if ($char === '/' && $next === '/') {
        $offset += 2;
        while ($offset < $length && $content[$offset] !== "\n") {
          $offset++;
        }
        continue;
      }

      if ($char === '/' && $next === '*') {
        $offset += 2;
        while ($offset + 1 < $length && !($content[$offset] === '*' && $content[$offset + 1] === '/')) {
          $offset++;
        }
        $offset += 2;
        continue;
      }

      if ($char === $open) {
        $depth++;
      }
      elseif ($char === $close) {
        $depth--;
        if ($depth === 0) {
          $offset++;
          return substr($content, $start, $offset - $start);
        }
      }

      $offset++;
    }

    throw new \RuntimeException('Unterminated block while parsing OpenSeadragon source.');
  }

}
