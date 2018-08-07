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

use Latte\Engine;
use Latte\Loaders\FileLoader;
use Nette\SmartObject;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Template
 *
 * Build html page with Latte Engine
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleTemplate
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleTemplate {
  private static $engine;

  /**
   * Module Initialization
   *
   * @param string $templateCachePath Cache path for template
   *
   * @return void
   */
  public static function init(string $templateCachePath): void {
    self::$engine = self::initTemplateEngine($templateCachePath);

    // Add filter call any php functions with params (with)
    self::addFilter(
      'with',
      function (string $function, ...$arguments) {
        return $function(...$arguments);
      }
    );
  }

  /**
   * Template Engine Initialization
   *
   * @param string $templateCachePath Cache path for template
   *
   * @return Engine
   */
  private static function initTemplateEngine(string $templateCachePath): Engine {
    $engine = new Engine;
    $engine->setTempDirectory($templateCachePath);
    $engine->setLoader(new FileLoader);
    return $engine;
  }

  /**
   * Magic call a method of Engine
   *
   * @param string $function  Function string for callback
   * @param array  $arguments Arguments function
   * 
   * @return Engine
   */
  public static function __callStatic(string $function, array $arguments): Engine {
    return self::$engine->$function(...$arguments);
  }
}