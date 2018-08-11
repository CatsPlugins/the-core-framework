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
use Nette\Caching\Storages\MemoryStorage;
use Nette\Caching\Storages\NewMemcachedStorage;
use Nette\Caching\Storages\SQLiteStorage;
use Nette\InvalidStateException;
use Nette\NotSupportedException;
use Nette\Utils\Callback;
use Nette\Utils\FileSystem;
use \stdClass;

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
   * @return void
   */
  public static function init(): void {
    $configs = ModuleConfig::Cache()->CACHE_STORAGE;
    if (empty($configs)) {
      return;
    }

    self::$storages = new stdClass;
    //bdump($configs, 'Config cache');

    // Create Storage cache
    foreach ($configs as $storageName => $storagesCache) {
      $name                  = strtolower($storageName);
      $folder                = strtoupper($storageName);
      self::$storages->$name = new stdClass;

      if (isset($storagesCache->memory)) {
        $oStorage                      = new MemoryStorage();
        self::$storages->$name->memory = new Cache($oStorage, $folder);
      }

      if (isset($storagesCache->file)) {
        $pathCache = realpath($storagesCache->file);

        // Create cache path if not exist
        if (!file_exists($pathCache)) {
          FileSystem::createDir($pathCache, 744);
        }

        $oStorage                    = new FileStorage($pathCache);
        self::$storages->$name->file = new Cache($oStorage, $folder);
      }

      if (isset($storagesCache->memcached)) {
        try {
          $oStorage                         = new NewMemcachedStorage($storagesCache->memcached->host, $storagesCache->memcached->port);
          self::$storages->$name->memcached = new Cache($oStorage, $folder);
        } catch (NotSupportedException $e) {
          bdump($e->getMessage(), 'Module Cache: Memcached');
        } catch (InvalidStateException $e) {
          bdump($e->getMessage(), 'Module Cache: Memcached');
        }
      }

      if (isset($storagesCache->sqlite)) {
        try {
          $oStorage                      = new SQLiteStorage($storagesCache->sqlite);
          self::$storages->$name->sqlite = new Cache($oStorage, $folder);
        } catch (PDOException $e) {
          bdump($e->getMessage(), 'Module Cache: SQLite');
        }
      }
    }
    bdump(self::$storages, 'Cache Storage');
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
    bdump($arguments, $storageName);

    $result   = null;
    $name     = strtolower($storageName);
    $storages = self::$storages->$name ?? null;

    if ($storage === null) {
      return false;
    }

    $cacheKey    = $arguments[0] ?? null;
    $cacheValue  = $arguments[1] ?? null;
    $cacheOption = $arguments[2] ?? null;
    $cacheTime   = ModuleConfig::Cache()->CACHE_TIME;

    foreach ($storages as $type => $storage) {
      // Set time cache form setting
      if ($cacheKey === 'save' && !isset($cacheOption['expire'])) {
        $cacheOption['expire']  = $cacheTime->$name;
        $cacheOption['sliding'] = false;
      }

      if (is_object($storage) && $cacheKey !== null) {
        // Save cache mode (default)
        if ($cacheValue !== null) {
          $result = $storage->save($cacheKey, $cacheValue);
        } else {
          try {
            // Call exist method cache
            $result = Callback::invokeArgs([$storage, $cacheKey], $cacheValue);
          } catch (InvalidArgumentException $e) {
            bdump($e, 'Method not found: ' . $cacheKey);

            // Load multiple items from the cache.
            if (is_array($cacheKey)) {
              $result = $storage->bulkLoad($cacheKey);
            } else {
              $result = $storage->load($cacheKey);
            }
          }
        }

        bdump($result, $type);
      }
    }

    return $result;
  }
}