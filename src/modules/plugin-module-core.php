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

use Nette\Loaders\RobotLoader;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Core
 *
 * Setup and initialization The Core
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleCore
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleCore {
  public static $textdomain;
  public static $pluginPath;
  public static $pluginVersion;

  public static $logPath;
  public static $cachePath;
  public static $assetsPath;
  public static $configPath;
  public static $modulesPath;
  public static $languagePath;

  /**
   * Initialization Module
   *
   * @param array $config Config plugin [textdomain, pluginVersion, pluginPath]
   *
   * @return void
   */
  public static function init(array $config): void {
    self::$textdomain    = $config['textdomain'];
    self::$pluginPath    = $config['plugin_path'];
    self::$pluginVersion = $config['plugin_version'];
    
    self::$logPath      = $pluginPath . DS . 'log' . DS;
    self::$cachePath    = $pluginPath . DS . 'cache' . DS;
    self::$assetsPath   = $pluginPath . DS . 'assets' . DS;
    self::$configPath   = $pluginPath . DS . 'config' . DS;
    self::$modulesPath  = $pluginPath . DS . 'modules' . DS;
    self::$languagePath = $pluginPath . DS . 'languages' . DS;

    self::loadModules($config['refresh_modules']);
  }

  /**
   * Load modules plugin
   *
   * @param boolean $autoRefresh Auto refresh modules
   *
   * @return void
   */
  private static function loadModules(bool $autoRefresh) {
    // Autoload all module files in self::$modulesPath
    $moduleLoader = new RobotLoader;
    $moduleLoader->addDirectory(self::$modulesPath);
    $moduleLoader->setAutoRefresh($autoRefresh);
    $moduleLoader->register();
  }
}