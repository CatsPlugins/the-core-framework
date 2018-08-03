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
   * @param array $config Config custom REST API [version, namespace]
   *
   * @return void
   */
  public function init(array $config): void {
    self::$version        = $config['version'];
    self::$nonce          = wp_create_nonce('wp_rest');
    self::$namespace      = ModuleCore::$textDomain . '/' . self::$version . '/';
    self::$pathRestModule = ModuleCore::$modulesPath . $config['namespace'] . DS;

    self::loadModules($config['refresh_modules']);
  }

  /**
   * Load modules RestApi
   *
   * @param boolean $autoRefresh Auto refresh modules
   * 
   * @return void
   */
  private function loadModules(bool $autoRefresh): void {
    // Autoload all module files in self::$pathRestModule
    $moduleLoader = new RobotLoader;
    $moduleLoader->addDirectory(self::$pathRestModule);
    $moduleLoader->setAutoRefresh($autoRefresh);
    $moduleLoader->register();
  }
}