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
   * @param callable $callback Callback
   * 
   * @return void
   */
  public static function handleEventActivatePlugin(callable $callback): void {
    register_activation_hook(ModuleCore::$pluginPath, $callback);
  }

  /**
   * Handle event when deactivate plugin
   *
   * @param callable $callback Callback
   * 
   * @return void
   */
  public static function handleEventDeactivatePlugin(callable $callback): void {
    register_deactivation_hook(ModuleCore::$pluginPath, $callback);
  }

  /**
   * Initialization language
   *
   * @return void
   */
  public static function initLanguage(): void {
    load_plugin_textdomain(ModuleCore::$textDomain, false, ModuleCore::$languagePath);
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
      $assetsUrl    = ModuleCore::$assetsUrl;
      $assetsPath   = ModuleCore::$assetsPath;
      $basenameFile = $parsedPath['basename'];

      // Check file exist
      $filePath = $assetsPath . DS . $fileExt . DS . $basenameFile;
      if (!file_exists($filePath)) {
        return false;
      }

      $basenameFile = $parsedPath['basename'];
      $url          = "$assetsUrl/$fileExt/$basenameFile";
    }

    if ($fileExt === 'js') {
      $position = position === 'footer' ? true : false;
      return wp_register_script(ModuleCore::$textDomain . '-' . $filename, $url, $deps, $version, $position);
    } else {
      return wp_register_style(ModuleCore::$textDomain . '-' . $filename, $url, $deps, $version, $position);
    }
  }
}