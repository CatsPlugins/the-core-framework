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

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Control
 *
 * Control event, request, router
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleControl
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleControl {
  /**
   * Handle event when activate plugin
   *
   * @return void
   */
  public static function handleEventActivatePlugin():void {
    
  }

  /**
   * Handle event when deactivate plugin
   *
   * @return void
   */
  public static function handleEventDeactivatePlugin():void {

  }
}