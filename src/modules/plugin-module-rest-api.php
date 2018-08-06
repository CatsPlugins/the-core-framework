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
use Nette\Loaders\RobotLoader;
use Nette\Utils\Callback;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module RestAPI
 *
 * Create Rest API form Wordpress
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleRestAPI
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleRestAPI {
  public static $nonce;
  public static $version;
  public static $namespace;
  public static $pathRestModule;

  /**
   * Initialization REST API
   *
   * @param array $config Config custom REST API [version, namespace, refresh_modules, autoload_modules]
   *
   * @return void
   */
  public static function init(array $config): void {
    self::$version        = $config['version'];
    self::$nonce          = wp_create_nonce('wp_rest');
    self::$namespace      = $config['namespace'] . '/' . self::$version;
    self::$pathRestModule = ModuleCore::$modulesPath . $config['namespace'] . DS;

    $listClass = self::loadModules($config['refresh_modules'] ?? false);

    if ($config['autoload_modules'] === true) {
      self::initModules($listClass);
    }
  }

  /**
   * Load modules RestApi
   *
   * @param boolean $autoRefresh Auto refresh modules
   *
   * @return array
   */
  private static function loadModules(bool $autoRefresh): array{
    // Autoload all module files in self::$pathRestModule
    $moduleLoader = new RobotLoader;
    $moduleLoader->addDirectory(self::$pathRestModule);
    $moduleLoader->setTempDirectory(ModuleCore::$cachePath);
    $moduleLoader->setAutoRefresh($autoRefresh);
    $moduleLoader->register();

    return $moduleLoader->getIndexedClasses();
  }

  /**
   * Auto Initialization modules
   *
   * @param array $listClass List loaded class 
   * 
   * @return void
   */
  private static function initModules(array $listClass): void {
    foreach ($listClass as $className => $filePath) {
      try {
        Callback::invoke([$className, 'init']);
      } catch (InvalidArgumentException $e) {
        bdump($e, $filePath);
      }
    }
  }
}