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

use Nette\InvalidArgumentException;
use Nette\Utils\Callback;

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
  // Store assets files config
  private static $assetsConfig;

  /**
   * Add a event
   *
   * @param string  $tag          The name of the event to hook the $function callback to.
   * @param mixed   $function     The callback to be run when the filter is applied.
   * @param integer $priority     Lower numbers correspond with earlier execution.
   * @param integer $acceptedArgs The number of arguments the function accepts.
   * @param array   $parameters   Parameters passed to a callback
   *
   * @return void
   */
  public static function event(string $tag, $function, int $priority = 10, int $acceptedArgs = 1, array $parameters = null) {
    // Auto add textdomain for custom tag
    $tag = $tag[0] === '_' ? ModuleCore::$textDomain . $tag : $tag;

    // Add callback with parameters
    if (!is_null($parameters)) {
      $function = function () use ($function, $parameters) {
        try {
          return Callback::invokeArgs($function, $parameters);
        } catch (InvalidArgumentException $e) {
          bdump($e, 'Add callback with parameters : ' . $function);
        }
      };
    }

    //bdump([$tag, $function, $priority, $acceptedArgs], 'Event');
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
   * Lazy register assets files
   *
   * @param array $configs An array config assets [url, deps, ver, position]
   *
   * @return array
   */
  public static function registerAssetsFiles(array $configs): void {
    foreach ($configs as $config) {
      $result = self::registerAssetsFile(...$config);

      $id = $result['hash'];
      unset($result['hash']);

      self::$assetsConfig[$id] = $result;
    }
  }

  /**
   * Smart register an assets file
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
    $result['hash']    = md5($url);
    $result['url']     = $url;
    $result['success'] = false;
    //bdump($result, 'Begin registerAssetsFile');

    // Convert path to url
    if (file_exists($url)) {
      $url        = ModuleHelper::pathToUrl($url);
      $isValidUrl = true;
      //bdump($url, 'Convert path to url');
    }

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

    $isValidUrl = $isValidUrl ?? ModuleHelper::isValidUrl($url);

    // If url is not absolute, generate a absolute url
    if (!isset($parsedUrl['host']) || !$isValidUrl) {
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
    bdump([$fileId, $url, $deps, $version, $position], 'wp_enqueue_scripts');

    if ($fileExt === 'js') {
      $position = position === 'footer' ? true : false;
      $success  = self::event(
        'wp_enqueue_scripts',
        function () use ($fileId, $url, $deps, $version, $position) {
          bdump([$fileId, $url, $deps, $version, $position], 'wp_register_script');
          wp_register_script($fileId, $url, $deps, $version, $position);
        }
      );
    } else {
      $success = self::event(
        'wp_enqueue_scripts',
        function () use ($fileId, $url, $deps, $version, $position) {
          bdump([$fileId, $url, $deps, $version, $position], 'wp_register_style');
          wp_register_style($fileId, $url, $deps, $version, $position);
        }
      );
    }

    $result['success'] = $success;
    $result['id']      = $fileId;
    $result['url']     = $url;

    //bdump($result, 'registerAssetsFile');
    return $result;
  }

  /**
   * Lazy enqueue assets files
   *
   * @param array $configs An array config assets [url, deps, ver, position]
   *
   * @return void
   */
  public static function enqueueAssetsFiles(array $configs): void {
    foreach ($configs as $config) {
      self::enqueueAssetsFile(...$config);
    }
  }

  /**
   * Smart enqueue an assets file
   *
   * @param string $file Full URL or path script/style file relative to the plugin assets directory
   *
   * @return void
   */
  private static function enqueueAssetsFile(string $file): void {
    $hash   = md5($file);
    $result = self::$assetsConfig[$hash] ?? false;

    // If assets file not reg, try reg it
    if ($result === false) {
      $result = self::registerAssetsFile($file);
    }

    if ($result['success'] === false) {
      return;
    }

    //bdump($result, 'enqueueAssetsFile');
    self::event(
      'wp_enqueue_scripts',
      function () use ($result) {
        bdump($result, 'wp_enqueue_script');
        wp_enqueue_script($result['id']);
      }
    );
  }
}