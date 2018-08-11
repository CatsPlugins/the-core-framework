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
use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use Nette\Utils\Html;
use \stdClass;

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
   * Magic call a method of Engine
   *
   * @param string $method    Method name for callback
   * @param array  $arguments Arguments function
   *
   * @return mixed
   */
  public static function __callStatic(string $method, array $arguments) {
    return self::$engine->$method(...$arguments);
  }

  /**
   * Module Initialization
   *
   * @param string $templateCachePath Cache path for template
   *
   * @return void
   */
  public static function init(string $templateCachePath): void {
    self::$engine = self::initTemplateEngine($templateCachePath);

    // Add filter call any php method with params (with)
    self::$engine->addFilter(
      'func',
      function (string $method, ...$arguments) {
        try {
          return Callback::invokeArgs($method, $arguments);
        } catch (InvalidArgumentException $e) {
          bdump($e, 'Template filter func: ' . $method);
        }
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
   * Generate elements by config
   *
   * @param array $elementsConfig The element config
   *
   * @return string
   */
  public static function generateElements(array $elementsConfig): string {
    if (is_array($elementsConfig)) {
      $elementsConfig = ModuleHelper::arrayToObject($elementsConfig);
    }

    //bdump($elementsConfig, 'Elements Config');

    $oElement = Html::el();

    foreach ($elementsConfig as $elementConfig) {
      // Ignore if the configuration is invalid
      if (empty($elementConfig->htmltag)) {
        continue;
      }

      $htmlTag = $elementConfig->htmltag;
      unset($elementConfig->htmltag);

      // Get data for the item may be callback
      array_walk($elementConfig, [ModuleHelper::class, 'lazyInvokeArgsRecursive']);

      // Try to get an optional value if named (possibly an optional id)
      $elementValue = false;
      if (isset($elementConfig->attr->name)) {
        $elementName  = $elementConfig->attr->name;
        $elementValue = ModuleConfig::Option()->$elementName;
      }

      // If there is an attr name it will have a value, but not overwritten
      if ($elementValue && !isset($elementConfig->attr->value)) {
        $elementConfig->attr->value = $elementValue;
      }

      // If it is not named, it will have the value of the main element
      if (!isset($elementConfig->attr->name) && isset($elementsConfig->value) && !isset($elementConfig->attr->value)) {
        $elementConfig->attr->name  = $elementsConfig->name;
        $elementConfig->attr->value = $elementsConfig->value;
      }

      // If element is not have a html tag, render it forms a template
      if (isset(TCF_PATH_TEMPLATES_COMPONENTS[$htmlTag])) {
        $templateFile = realpath(TCF_PATH_TEMPLATES_COMPONENTS[$htmlTag] . $elementConfig->file . '.latte');

        // Ignore if template file not exist
        if ($templateFile === false) {
          continue;
        }

        unset($elementConfig->file);

        // Argument 2 passed renderToString must be of the type array
        if (is_object($elementConfig)) {
          $fixElementConfig = ModuleHelper::objectToArray($elementConfig);
        }

        $oHTML = self::$engine->renderToString($templateFile, $fixElementConfig);
        $oElement->addHtml($oHTML);
      }

      // Generate a html tag
      $oHTML = self::generateHtmlTag($htmlTag, $elementConfig);

      $oElement->addHtml($oHTML);
    }

    //bdump((string) $oElement);

    if ($elementsConfig->echo === true) {
      echo (string) $oElement;
    }

    return (string) $oElement;
  }

  /**
   * Generate a html tag
   *
   * @param string   $htmlTag       The html tag name
   * @param stdClass $elementConfig The element config of html tag
   *
   * @return string
   */
  public static function generateHtmlTag(string $htmlTag, stdClass $elementConfig): string {
    $oHTML = Html::el($htmlTag);

    // Set attr for this element
    if (!empty($elementConfig->attr)) {
      $attr = ModuleHelper::objectToArray($elementConfig->attr);
      $oHTML->addAttributes($attr);
    }

    // Set dataset for this element
    if (!empty($elementConfig->dataset)) {
      foreach ($elementConfig->dataset as $sName => $mDataSet) {
        $oHTML->data($sName, $mDataSet);
      }
    }

    // Set text for this element
    if (!empty($elementConfig->text)) {
      // If text value is an element name, it will have a element value
      if ($elementConfig->text === $elementConfig->attr->name) {
        $oHTML->setText($elementConfig->attr->value);
      } else {
        $oHTML->setText($elementConfig->text);
      }
    }

    // Set html for this element
    if (!empty($elementConfig->html)) {
      // If html value is an element name, it will have a element value
      if ($elementConfig->html === $elementConfig->attr->name) {
        $oHTML->setHtml($elementConfig->attr->value);
      } else {
        $oHTML->setHtml($elementConfig->html);
      }
    }

    // Get child elements config
    $childElement = [];
    if (!empty($elementConfig->child_element)) {
      $childElement = $elementConfig->child_element;
      unset($elementConfig->child_element);
    }

    // Add child element
    if (!empty($childElement) && is_array($childElement)) {
      $childElementHtml = self::generateElements($childElement, false);
      $oHTML->addHtml($childElementHtml);
    }

    return (string) $oHTML;
  }
}