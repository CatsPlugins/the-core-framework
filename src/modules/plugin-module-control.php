<?php
/**
 * The Plugin Core Framework for Wordpress
 *
 * PHP Version 7
 *
 * @category Framework
 * @package  CatsPlugins\TheCore
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */

declare (strict_types = 1);

namespace CatsPlugins\TheCore;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Control
 *
 * Control event, request, router, assets
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleControl
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleControl {
  /**
   * Handle event when activate plugin
   *
   * @return void
   */
  public static function handleEventActivatePlugin(): void {

  }

  /**
   * Handle event when deactivate plugin
   *
   * @return void
   */
  public static function handleEventDeactivatePlugin(): void {

  }

  /**
   * Setup language
   *
   * @param string $textDomain Plugin TextDomain
   *
   * @return void
   */
  public static function setupLanguage(string $textDomain): void {
    // TODO: check define conflict
    defined('TCF_TEXTDOMAIN') ? null : define('TCF_TEXTDOMAIN', $textDomain);

    load_plugin_textdomain($textDomain, false, basename(TCF_PATH_BASE) . DS . 'languages' . DS);
  }

  /**
   * Lazy and smart register assets files
   *
   * @param array $urls An array config assets [url, deps, ver, position]
   * 
   * @return array
   */
  public static function registerAssetsFiles(array $urls): array{
    $result = [];
    array_walk(
      $urls,
      function (array $value, string $key) {
        $result[$key] = self::registerAssetsFile(...$value);
      }
    );

    return $result;
  }

  /**
   * Smart register assets an file
   *
   * @param string $url      Full URL or path script/style relative to the plugin assets directory
   * @param array  $deps     An array of registered script handles this script depends on
   * @param mixed  $version  String specifying script version number
   * @param string $position Position to enqueue the script/style
   *
   * @return boolean
   */
  private static function registerAssetsFile(string $url, array $deps = [], mixed $version = null, string $position = 'all'): bool {
    // Get path url
    $parsedUrl = parse_url($url);
    if (empty($parsedUrl['path'])) {
      return false;
    }

    $parsedPath = pathinfo($parsedUrl['path']);

    // Get file name
    $filename = $parsedPath['filename'];
    if (empty($filename)) {
      return false;
    }

    $fileExt = $parsedPath['extension'];
    if (empty($fileExt)) {
      return false;
    }

    // If url is not absolute, generate a absolute url
    if (!isset($parsedUrl['host']) || !ModuleHelper::isValidUrl($url)) {

      // TODO: Replace TCF_URL, TCF_PATH_BASE define, ex: ModuleConfig::get('plugin', 'assets_url')
      $assetsUrl    = TCF_URL;
      $assetsPath   = TCF_PATH_BASE;
      $basenameFile = $parsedPath['basename'];

      // Check file exist
      $filePath = $assetsPath . DS . $fileExt . DS . $basenameFile;
      if (!file_exists($filePath)) {
        return false;
      }

      $basenameFile = $parsedPath['basename'];
      $url          = "$assetsUrl/$fileExt/$basenameFile";
    }

    // TODO: Replace TCF_TEXTDOMAIN define, ex: ModuleConfig::get('plugin', 'textdomain')
    if ($fileExt === 'js') {
      $position = position === 'footer' ? true : false;
      return wp_register_script(TCF_TEXTDOMAIN . '-' . $filename, $url, $deps, $version, $position);
    } else {
      return wp_register_style(TCF_TEXTDOMAIN . '-' . $filename, $url, $deps, $version, $position);
    }
  }
}