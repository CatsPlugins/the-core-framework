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

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Utils\Callback;
use Nette\Utils\FileSystem;

/**
 * The Module Cache
 *
 * Caching code, api,...
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleCache
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleCache {
  private static $storages;

  /**
   * Init Module Cache
   *
   * @param string ...$storagesName List storage cache
   *
   * @return void
   */
  public static function init(...$storagesName): void {
    // Default path cache
    $pathCache = ModuleCore::$cachePath;

    // Create cache path if not exist
    if (!file_exists($pathCache)) {
      FileSystem::createDir($pathCache, 744);
    }

    $storage = new FileStorage($pathCache);

    // Create Storage cache
    foreach ($storagesName as $name) {
      $correctName                  = strtolower($name);
      self::$storages[$correctName] = new Cache($storage, strtoupper($name));
    }
  }

  /**
   * Smart method cache
   *
   * @param string $storageName Name cache storage
   * @param array  $arguments   Callback, array,...
   * 
   * @return void
   */
  public static function __callStatic(string $storageName, array $arguments) {
    $name       = strtolower($storageName);
    $cacheKey   = $arguments[0] ?? null;
    $cacheValue = $arguments[1] ?? null;
    $storage    = self::$storages[$name] ?? null;

    if ($storage === null) {
      return false;
    }

    // Special Mode
    if ($cacheKey !== null) {
      // Call method mode
      if (method_exists($storage, $cacheKey)) {
        if ($cacheValue !== null) {
          return Callback::invokeArgs([$storage, $cacheKey], $cacheValue);
        }

        return Callback::invoke($storage, $cacheKey);
      }

      // Save cache mode (default)
      if ($cacheValue !== null) {
        return $storage->save($cacheKey, $cacheValue);
      }
    }

    // Mode load cache by Key
    if ($cacheKey !== null && $cacheValue === null) {
      // Load multiple items from the cache.
      if (is_array($cacheKey)) {
        return $storage->bulkLoad($cacheKey);
      }

      return $storage->load($cacheKey);
    }

    return true;
  }
}