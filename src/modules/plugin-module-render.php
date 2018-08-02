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

use Nette\Utils\Json;
use Nette\Utils\JsonException;

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
   * Send a json content
   *
   * @param array $data Array data
   *
   * @return void
   */
  public static function toJson(array $data): void {
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
   * Send a text content
   *
   * @param mixed $data Any variable
   *
   * @return void
   */
  public static function toText(mixed $data): void {
    $content = print_r($data, true);
    //$content = preg_replace('/(\w+\n\()/', '(', $content);

    header_remove();
    header('Cache-Control: max-age=0, must-revalidate', true);
    header('Content-Type: text; charset=UTF-8', true);

    echo "<pre>$content</pre>";
    exit;
  }

  /**
   * Render array to html list
   *
   * @param mixed $array Support object
   *
   * @return void
   *
   * TODO: make with Nette.Utils.Html
   */
  public static function toListHtml(mixed $array): void {
    if (is_object($array)) {
      $array = ModuleHelper::objectToArray($array);
    }

    foreach ($array as $value) {
      $html .= "<li>$value</li>";
    }

    echo "<ul>$html</ul>";
    exit;
  }
}