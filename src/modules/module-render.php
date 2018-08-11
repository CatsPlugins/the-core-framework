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

use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Render
 *
 * Render template or variable to json, html, text formated end exit process
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleRender
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleRender {
  /**
   * Auto trigger a hook before and after call a _method
   *
   * @param string $method    The name of the method being called.
   * @param array  $arguments The argument is an enumerated array containing the parameters passed to the method.
   *
   * @return void
   */
  public static function __callStatic(string $method, array $arguments) {
    return ModuleHelper::autoTriggerEventMethod(self::class, $method, $arguments);
  }

  /**
   * Send array to a json content
   *
   * @param array $data Array data
   *
   * @return void
   */
  public static function sendJson(array $data): void {
    try {
      $content = Json::encode($data);
    } catch (JsonException $e) {
      $content = Json::encode(['error' => $e->getMessage()]);
    }

    header_remove();
    header('Cache-Control: max-age=0, must-revalidate', true);
    header('Content-Type: application/json;charset=UTF-8', true);

    echo $content;
    exit;
  }

  /**
   * Send variable to a text content
   *
   * @param mixed $data Any variable
   *
   * @return void
   */
  public static function sendText($data): void {
    Debugger::$showLocation = false;
    dump($data);
    $html = ob_get_clean();

    header_remove();
    header('Cache-Control: max-age=0, must-revalidate', true);
    header('Content-Type: text; charset=UTF-8', true);

    echo $html;
    exit;
  }

  /**
   * Send variable to a html content
   *
   * @param mixed $data Any variable
   *
   * @return void
   *
   */
  public static function sendHtml($data): void {
    Debugger::$showLocation = false;
    dump($data);
    $html = ob_get_clean();

    header_remove();
    header('Cache-Control: max-age=0, must-revalidate', true);
    header('Content-Type: text/html; charset=UTF-8', true);

    echo $html;
    exit;
  }

  /**
   * Send admin notification
   *
   * @param boolean $showEverywhere Whether or not to show everywhere
   *
   * @return void
   */
  public static function sendAdminNotification(bool $showEverywhere = null): void {
    $currentScreen = get_current_screen();
    $pluginScreen  = '_' . ModuleCore::$textDomain . '-';

    // Only show notification on plugin setting, or show all
    if (stripos($currentScreen->id, $pluginScreen) !== false || $showEverywhere === true) {
      ModuleControl::trigger('_show_admin_notification');
    }
  }

  /**
   * Render a template page
   *
   * @param string $pageId Page id in config
   *
   * @return void
   */
  public static function showPage(string $pageId): void {
    // Get file path of template
    $templateFile = realpath(TCF_PATH_TEMPLATES_COMPONENTS['page'] . $pageId . '.latte');

    // Show error if file not exist
    if ($templateFile === false) {
      echo '<h2 class="center-align">' . ModuleHelper::trans('The template page does not exist!') . '</h2>';
      return;
    }

    // Get page configuration
    $pageConfig = ModuleConfig::Admin()->PAGES->$pageId;

    // Enqueue assets files
    ModuleControl::enqueueAssetsFiles($pageConfig->assets);

    // Add more data
    $pageConfig->page_id    = $pageId;
    $pageConfig->textdomain = ModuleCore::$textDomain;

    // Remove data not used
    unset($pageConfig->assets, $pageConfig->sections);

    // Argument 2 passed renderToString must be of the type array
    if (is_object($pageConfig)) {
      $pageConfig = ModuleHelper::objectToArray($pageConfig);
    }

    echo ModuleTemplate::renderToString($templateFile, $pageConfig);
  }
}