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

use Tracy\Debugger;
use Nette\SmartObject;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Debugger
 *
 * Render template or variable to json, html, text formated end exit process
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleDebugger
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleDebugger {
  /**
   * Init Module Debugger
   *
   * @param boolean $forceEnable Force enable Debugger
   *
   * @return void
   */
  public static function init(bool $forceEnable = null) {
    if (!function_exists('wp_get_current_user')) {
      include ABSPATH . "wp-includes/pluggable.php";
    }

    // Fix output has been sent
    $output = ob_get_contents();
    if (!empty($output)) {
      ob_end_clean();
    }

    $isDevMode = false;
    if (!$forceEnable) {
      $devDomain = ModuleConfig::Core()->DEV_DOMAIN ?? '';
      $isDevMode = ModuleHelper::isDevMode($devDomain);
    }

    Debugger::$showLocation = true;
    Debugger::$maxDepth     = 4; // default: 3
    Debugger::$maxLength    = 650; // default: 150
    Debugger::$strictMode   = false;

    self::enableDebugBar(true);

    if ((current_user_can('administrator') && $isDevMode) || $forceEnable) {
      Debugger::enable(Debugger::DEVELOPMENT, ModuleCore::$logPath);
    } else {
      // PRODUCTION
      Debugger::enable(Debugger::PRODUCTION, ModuleCore::$logPath);
    }

    error_reporting(E_ALL & ~E_NOTICE);

    if (!empty($output)) {
      bdump($output);
    }
  }

  /**
   * Debug logging
   *
   * @param mixed $data Any data save to log
   *
   * @return void
   */
  public static function log($data): void {
    Debugger::log($data);
  }

  /**
   * Enable debug bar
   *
   * @param boolean $showBar Whether or not to enable the debug bar
   *
   * @return void
   */
  public static function enableDebugBar(bool $showBar): void {
    Debugger::$showBar = $showBar;
  }
}