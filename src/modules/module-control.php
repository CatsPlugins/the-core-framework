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
   * Add a event
   *
   * @param string  $tag          The name of the event to hook the $function callback to.
   * @param array   $function     The callback to be run when the filter is applied.
   * @param integer $priority     Lower numbers correspond with earlier execution.
   * @param integer $acceptedArgs The number of arguments the function accepts.
   *
   * @return void
   */
  public static function event(string $tag, array $function, int $priority = 10, int $acceptedArgs = 1) {
    // Auto add textdomain for custom tag
    $tag = $tag[0] === '_' ? ModuleCore::$textDomain . $tag : $tag;

    return add_filter($tag, $function, $priority, $acceptedArgs);
  }

  /**
   * Execute a event
   *
   * @param string $tag  The name of the event to hook the $function callback to.
   * @param mixed  $args Additional arguments which are passed on to the event hooked to the action
   *
   * @return void
   */
  public static function trigger(string $tag, $args) {
    // Auto add textdomain for custom tag
    $tag = $tag[0] === '_' ? ModuleCore::$textDomain . $tag : $tag;

    do_action($tag, $args);
  }

  /**
   * Apply filter a event
   *
   * @param string $tag  The name of the event to hook the $function callback to.
   * @param mixed  $args Additional arguments which are passed on to the event hooked to the filter
   *
   * @return void
   */
  public static function filter(string $tag, $args) {
    // Auto add textdomain for custom tag
    $tag = $tag[0] === '_' ? ModuleCore::$textDomain . $tag : $tag;

    return apply_filters($tag, $args);
  }

  

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
    $results = [];
    array_walk(
      $urls,
      function (&$value) use (&$results) {
        $result = self::registerAssetsFile(...$value);

        $scriptId = $result['id'];
        unset($result['id']);

        $results[$scriptId] = $result;
      }
    );
    return $results;
  }

  /**
   * Smart register assets an file
   *
   * @param string $url      Full URL or path script/style relative to the plugin assets directory
   * @param array  $deps     An array of registered script handles this script depends on
   * @param mixed  $version  String specifying script version number
   * @param string $position Position to enqueue the script/style
   *
   * @return array
   */
  private static function registerAssetsFile(string $url, array $deps = [], $version = null, string $position = 'all'): array{
    $result            = [];
    $result['url']     = $url;
    $result['success'] = false;

    // Get path url
    $parsedUrl = parse_url($url);
    if (empty($parsedUrl['path'])) {
      return $result;
    }

    $parsedPath = pathinfo($parsedUrl['path']);

    // Get file name
    $filename = $parsedPath['filename'];
    if (empty($filename)) {
      return $result;
    }

    $fileExt = $parsedPath['extension'];
    if (empty($fileExt)) {
      return $result;
    }

    // If url is not absolute, generate a absolute url
    if (!isset($parsedUrl['host']) || !ModuleHelper::isValidUrl($url)) {
      $assetsUrl    = ModuleCore::$assetsUrl;
      $assetsPath   = ModuleCore::$assetsPath;
      $basenameFile = $parsedPath['basename'];

      // Check file exist
      $filePath = $assetsPath . DS . $fileExt . DS . $basenameFile;
      if (!file_exists($filePath)) {
        return $result;
      }

      $basenameFile = $parsedPath['basename'];
      $url          = "$assetsUrl/$fileExt/$basenameFile";
    }

    $fileId = ModuleCore::$textDomain . '-' . $filename;

    if ($fileExt === 'js') {
      $position = position === 'footer' ? true : false;
      $success  = add_action(
        'wp_enqueue_scripts',
        function () use ($fileId, $url, $deps, $version, $position) {
          wp_register_script($fileId, $url, $deps, $version, $position);
        }
      );
    } else {
      $success = add_action(
        'wp_enqueue_scripts',
        function () use ($fileId, $url, $deps, $version, $position) {
          wp_register_style($fileId, $url, $deps, $version, $position);
        }
      );
    }

    $result['success'] = $success;
    $result['id']      = $fileId;
    $result['url']     = $url;
    return $result;
  }
}