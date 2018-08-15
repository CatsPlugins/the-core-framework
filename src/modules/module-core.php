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
  public static $textDomain;
  public static $pluginPath;
  public static $pluginData;

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
    self::$pluginData = get_plugin_data($config['plugin_path'], false, false);

    self::$textDomain = self::$pluginData['TextDomain'];

    self::$logPath      = self::$pluginPath . 'log' . DS;
    self::$cachePath    = self::$pluginPath . 'cache' . DS;
    self::$assetsPath   = self::$pluginPath . 'assets' . DS;
    self::$configPath   = self::$pluginPath . 'config' . DS;
    self::$modulesPath  = self::$pluginPath . 'modules' . DS;
    self::$languagePath = self::$pluginPath . 'languages' . DS;

    self::$assetsUrl = plugin_dir_url(self::$pluginPath) . 'assets';

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