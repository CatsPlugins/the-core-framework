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

use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use Nette\Utils\Strings;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Event
 *
 * Manager events
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleEvent
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleEvent {
  /**
   * Add a event
   *
   * @param string  $tag          The name of the event to hook the $function callback to.
   * @param mixed   $function     The callback to be run when the filter is applied.
   * @param integer $priority     Lower numbers correspond with earlier execution.
   * @param integer $acceptedArgs The number of arguments the function accepts.
   * @param array   $parameters   Parameters passed to a callback
   *
   * @return void
   */
  public static function on(string $tag, $function, int $priority = 10, int $acceptedArgs = 1, array $parameters = null) {
    $tag = self::makeTag($tag);

    // Add callback with parameters
    if (!is_null($parameters)) {
      $function = function () use ($function, $parameters) {
        try {
          return Callback::invokeArgs($function, $parameters);
        } catch (InvalidArgumentException $e) {
          bdump($e, 'Add callback with parameters : ' . $function);
        }
      };
    }

    //bdump([$tag, $function, $priority, $acceptedArgs], 'Event');
    return add_filter($tag, $function, $priority, $acceptedArgs);
  }

  /**
   * Remove one or all event by tag 
   *
   * @param string  $tag      The name of the event to hook the $function callback to.
   * @param mixed   $function The callback to be run when the filter is applied.
   * @param integer $priority Lower numbers correspond with earlier execution.
   *
   * @return void
   */
  public static function off(string $tag, $function, int $priority = 10) {
    $tag = self::makeTag($tag);

    if ($function === '*') {
      return remove_all_filters($tag, $priority);
    }

    return remove_filter($tag, $function, $priority);
  }

  /**
   * Execute a event
   *
   * @param string $tag  The name of the event to hook the $function callback to.
   * @param mixed  $args Additional arguments which are passed on to the event hooked to the action
   *
   * @return void
   */
  public static function trigger(string $tag, $args = null) {
    $tag = self::makeTag($tag);
    do_action($tag, $args);
  }

  /**
   * Apply filter a event
   *
   * @param string $tag  The name of the event to hook the $function callback to.
   * @param mixed  $args Additional arguments which are passed on to the event hooked to the filter
   *
   * @return void
   */
  public static function filter(string $tag, $args = null) {
    $tag = self::makeTag($tag);
    return apply_filters($tag, $args);
  }

  /**
   * Make a unique tag name
   *
   * @param string $tag The name of the event to hook.
   *
   * @return string
   */
  public static function makeTag(string $tag): string {
    // Auto add textdomain for custom tag
    $tag = $tag[0] === '_' ? ModuleCore::$textDomain . $tag : $tag;
    $tag = Strings::replace(
      $tag,
      '/[A-Z]+/',
      function ($matched) {
        return '_' . $matched[0];
      }
    );

    return Strings::webalize($tag, '_', true);
  }

  /**
   * Handle event when activate plugin
   *
   * @param callable $callback Callback
   *
   * @return void
   */
  public static function activatePlugin(callable $callback): void {
    register_activation_hook(ModuleCore::$pluginPath, $callback);
  }

  /**
   * Handle event when deactivate plugin
   *
   * @param callable $callback Callback
   *
   * @return void
   */
  public static function deactivatePlugin(callable $callback): void {
    register_deactivation_hook(ModuleCore::$pluginPath, $callback);
  }
}