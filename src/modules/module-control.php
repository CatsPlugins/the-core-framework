<?php declare (strict_types = 1);
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

namespace CatsPlugins\TheCore;

use Nette\Utils\Strings;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Control
 *
 * Control request, router, assets files
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
   * Initialization language
   *
   * @return void
   */
  public static function initLanguage(): void {
    load_plugin_textdomain(ModuleCore::$textDomain, false, ModuleCore::$languagePath);
  }

  /**
   * Get assets hash
   *
   * @param string $file Full URL or path script/style file relative to the plugin assets directory
   *
   * @return string
   */
  private static function getAssetsHash(string $file) {
    return md5($file);
  }

  /**
   * Get assets id has generate
   *
   * @param string $file Full URL or path script/style file relative to the plugin assets directory
   *
   * @return array or bool
   */
  public static function getAssetsId(string $file) {
    $assetsInfo = self::getAssetsInfo($file);
    return $assetsInfo !== false ? $assetsInfo['id'] : false;
  }

  /**
   * Get all information of assets file
   *
   * @param string $file Full URL or path script/style file relative to the plugin assets directory
   *
   * @return array or bool
   */
  public static function getAssetsInfo($file = null) {
    if (is_null($file)) {
      $assetsInfo = self::$assetsConfig;
    } else {
      $hash       = self::getAssetsHash($file);
      $assetsInfo = self::$assetsConfig[$hash];
    }

    return $assetsInfo;
  }

  /**
   * Lazy register assets files
   *
   * @param array $configs An array config assets [url/file, deps, ver, position]
   *
   * @return void
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
    $result               = [];
    $result['url']        = $url;
    $result['hash']       = self::getAssetsHash($url);
    $result['registered'] = false;
    //bdump($result, 'Begin registerAssetsFile');

    // Convert path to url
    if (file_exists($url)) {
      $url        = ModuleHelper::pathToUrl($url);
      $isValidUrl = true;
      //bdump($url, 'Convert path to url');
    }

    // Get path url
    $parsedUrl = parse_url($url);
    //bdump($parsedUrl, 'parsed url');
    if (empty($parsedUrl['path'])) {
      return $result;
    }

    $parsedPath = pathinfo($parsedUrl['path']);
    //bdump($parsedPath, 'parsed info');

    // Get file name for id: file name or query or url
    $fileName = isset($parsedPath['extension']) ? $parsedPath['filename'] : ($parsedUrl['query'] ?? Strings::webalize($url));
    $fileExt  = $parsedPath['extension'] ?? false;

    $isValidUrl = $isValidUrl ?? ModuleHelper::isValidUrl($url);
    //bdump($isValidUrl, 'valid url');

    // If url is not absolute, generate a absolute url
    // ! Maybe have error with an special url
    if (!isset($parsedUrl['host']) || !$isValidUrl) {
      $assetsUrl    = ModuleCore::$assetsUrl;
      $assetsPath   = ModuleCore::$assetsPath;
      $basenameFile = $parsedPath['basename'];

      // Check file exist
      $filePath = $assetsPath . DS . $fileExt . DS . $basenameFile;
      if (!file_exists($filePath)) {
        //bdump($filePath, 'file don\'t exists');
        return $result;
      }

      $basenameFile = $parsedPath['basename'];
      $url          = "$assetsUrl/$fileExt/$basenameFile";
    }

    $fileId = ModuleCore::$textDomain . '-' . $fileName;

    // Check the URL is outside the web or not, and categorized it
    if ($fileExt === false) {
      $jsDep  = array_search('js', $deps);
      $cssDep = array_search('css', $deps);

      if ($jsDep !== false) {
        $fileExt = 'js';
        unset($deps[$jsDep]);
      } elseif ($cssDep !== false) {
        $fileExt = 'css';
        unset($deps[$cssDep]);
      }
    }

    // Register script or style
    if ($fileExt === 'js') {
      $position = position === 'footer' ? true : false;
      $success  = ModuleEvent::on(
        'wp_loaded',
        function () use ($fileId, $url, $deps, $version, $position) {
          //bdump([$fileId, $url, $deps, $version, $position], 'wp_register_script');
          wp_register_script($fileId, $url, $deps, $version, $position);
        }
      );
    } elseif ($fileExt === 'css') {
      $success = ModuleEvent::on(
        'wp_loaded',
        function () use ($fileId, $url, $deps, $version, $position) {
          //bdump([$fileId, $url, $deps, $version, $position], 'wp_register_style');
          wp_register_style($fileId, $url, $deps, $version, $position);
        }
      );
    } else {
      bdump([$fileId, $fileName, $fileExt], 'Register fail');
      return $result;
    }

    $result['registered'] = $success;
    $result['id']         = $fileId;
    $result['ext']        = $fileExt;
    $result['url']        = $url;

    //bdump($result, 'registerAssetsFile');
    return $result;
  }

  /**
   * Lazy enqueue assets files
   *
   * @param array $configs An array config assets [url/file, deps, ver, position]
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
  private static function enqueueAssetsFile(string $file): bool {
    $result = self::getAssetsInfo($file);
    //bdump([$file, $result], 'enqueueAssetsFile');

    // If assets file not reg, try reg it
    if ($result === false) {
      $result = self::registerAssetsFile($file);
    }

    if ($result['registered'] === false) {
      return false;
    }

    if ($result['ext'] === 'js') {
      wp_enqueue_script($result['id']);
    } elseif ($result['ext'] === 'css') {
      wp_enqueue_style($result['id']);
    } else {
      return false;
    }

    return true;
  }

  /**
   * Provide javascript variable for javascript files by values of filter
   *
   * @param array $configs An array config [url/file, var_js_name, filter]
   *
   * @return void
   */
  public static function provideDataJs(array $configs): void {
    //bdump($configs, 'provideDataJs');
    foreach ($configs as $config) {
      self::setDataToJs(...$config);
    }
  }

  /**
   * Provide javascript variable for a javascript file by values of filter
   *
   * @param string $file   Full URL or path script/style file relative to the plugin assets directory
   * @param string $jsName A variable's name will be on Javascript
   * @param string $filter An event id to apply for the filter
   *
   * @return boolean
   */
  private static function setDataToJs(string $file, string $jsName, string $filter): bool {
    $result = self::getAssetsInfo($file);
    //bdump([$result, $jsName, $filter], 'setDataToJs');

    if ($result === false) {
      return false;
    }

    if ($result['registered'] === false) {
      return false;
    }

    $fileId = $result['id'];
    $jsData = ModuleEvent::filter($filter, []);
    
    $success = ModuleEvent::on(
      'wp_loaded',
      function () use ($fileId, $jsName, $jsData) {
        //bdump([$fileId, $jsName, $jsData], 'wp_localize_script');
        wp_localize_script($fileId, $jsName, $jsData);
      }
    );

    return $success;
  }
}