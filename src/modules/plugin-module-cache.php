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
  private static $keyCache;

  /**
   * Init Module Cache
   *
   * @param string $pathCache path for store cache
   *
   * @return callable
   */
  public static function init(string $pathCache): callable {
    // Create cache path if not exist
    if (!file_exists($pathCache)) {
      FileSystem::createDir($pathCache, 744);
    }

    $storage = new FileStorage($pathCache);

    // Create Storage cache for API
    self::$api = new Cache($storage, 'API');
  }

  /**
   * Store current keyCache
   *
   * @param mixed $name Key cache name
   *
   * @return callable
   */
  public static function setKeyCache(mixed $name): void {
    self::$keyCache = $name;
  }

  /**
   * Get current keyCache
   *
   * @return mixed
   */
  public static function getKeyCache(): mixed {
    return self::$keyCache;
  }
}