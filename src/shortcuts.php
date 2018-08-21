<?php declare (strict_types = 1);
/**
 * The Plugin Core Framework for Wordpress
 *
 * PHP Version 7
 * Shortcuts module
 *
 * @category Framework
 * @package  CatsPlugins\TheCore
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

if (!function_exists('_t')) {
  /**
   * Shortcut translate string with plugin textdomain
   *
   * @param mixed $text Variable
   *
   * @return string
   */
  function _t($text): string {
    return call_user_func_array('CatsPlugins\TheCore\ModuleLanguage::trans', func_get_args());
  }
}