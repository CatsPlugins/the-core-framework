<?php
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

if (!function_exists('___')) {
  /**
   * Shortcut translate string with plugin textdomain
   *
   * @param mixed $text Variable
   *
   * @return string
   */
  function ___($text): string {
    return call_user_func_array('CatsPlugins\TheCore\ModuleHelper::trans', func_get_args());
  }
}