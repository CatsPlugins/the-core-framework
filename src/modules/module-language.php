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

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Language
 *
 * Setup and initialization The Core
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleLanguage
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleLanguage {
  /**
   * Initialization language
   *
   * @return void
   */
  public static function init(): void {
    load_plugin_textdomain(ModuleCore::$textDomain, false, ModuleCore::$languagePath);
  }

  /**
   * Translate text with textdomain
   *
   * @param mixed $text Text to translate
   *
   * @return string
   */
  public static function trans($text): string {
    return __($text, ModuleCore::$textDomain);
  }
}