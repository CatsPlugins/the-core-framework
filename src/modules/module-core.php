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

use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

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
  public static $assetsUrl;
  public static $textDomain;
  public static $pluginPath;
  public static $pluginData;

  public static $logPath;
  public static $cachePath;
  public static $assetsPath;
  public static $configPath;
  public static $modulesPath;
  public static $includesPath;
  public static $languagePath;
  public static $componentsPath;

  /**
   * Initialization Module
   *
   * @param array $config Config plugin [string pluginPath, bool refreshModules]
   *
   * @return void
   */
  public static function init(array $config): void {
    // Check callable get_plugin_data
    if (!function_exists('get_plugin_data')) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    self::$pluginPath = realpath(plugin_dir_path($config['plugin_path'])) . DS;
    //bdump(self::$pluginPath, 'Plugin Path');

    self::$pluginData = get_plugin_data($config['plugin_path'], false, false);
    //bdump(self::$pluginData, 'Plugin Data');

    self::$textDomain = self::$pluginData['TextDomain'];

    self::$assetsUrl = plugin_dir_url($config['plugin_path']) . 'assets';
    //bdump(self::$assetsUrl, 'Plugin Assets');

    self::$logPath        = self::$pluginPath . 'log' . DS;
    self::$cachePath      = self::$pluginPath . 'cache' . DS;
    self::$assetsPath     = self::$pluginPath . 'assets' . DS;
    self::$configPath     = self::$pluginPath . 'configs' . DS;
    self::$modulesPath    = self::$pluginPath . 'modules' . DS;
    self::$includesPath   = self::$pluginPath . 'includes' . DS;
    self::$languagePath   = self::$pluginPath . 'languages' . DS;
    self::$componentsPath = self::$pluginPath . 'components' . DS;

    // Check log path
    if (!realpath(self::$logPath)) {
      FileSystem::createDir(self::$logPath, 0755);
    }

    // Check cache path
    if (!realpath(self::$cachePath)) {
      FileSystem::createDir(self::$cachePath, 0755);
    }

    // Check components path
    if (!realpath(self::$componentsPath)) {
      FileSystem::createDir(self::$componentsPath, 0755);
      FileSystem::createDir(self::$componentsPath . 'pages', 0755);
      FileSystem::createDir(self::$componentsPath . 'elements', 0755);
    }

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