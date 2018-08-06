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
  public static $textDomain;
  public static $pluginPath;
  public static $pluginVersion;

  public static $logPath;
  public static $cachePath;
  public static $assetsPath;
  public static $configPath;
  public static $modulesPath;
  public static $languagePath;

  public static $assetsUrl;

  /**
   * Initialization Module
   *
   * @param array $config Config plugin [textDomain, pluginVersion, pluginPath]
   *
   * @return void
   */
  public static function init(array $config): void {
    self::$textDomain    = $config['textdomain'];
    self::$pluginPath    = realpath(plugin_dir_path($config['plugin_path'])) . DS;
    self::$pluginVersion = $config['plugin_version'];

    self::$logPath      = self::$pluginPath . DS . 'log' . DS;
    self::$cachePath    = self::$pluginPath . DS . 'cache' . DS;
    self::$assetsPath   = self::$pluginPath . DS . 'assets' . DS;
    self::$configPath   = self::$pluginPath . DS . 'config' . DS;
    self::$modulesPath  = self::$pluginPath . DS . 'modules' . DS;
    self::$languagePath = self::$pluginPath . DS . 'languages' . DS;

    self::$assetsUrl = plugin_dir_url($config['plugin_path']) . 'assets';

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
    $moduleLoader->setTempDirectory(self::$cachePath);
    $moduleLoader->setAutoRefresh($autoRefresh);
    $moduleLoader->register();
  }
}