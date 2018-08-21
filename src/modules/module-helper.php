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

use GuzzleHttp\Client;
use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use \DOMDocument;
use \DOMElement;
use \stdClass;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Helper
 *
 * Utils method
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleHelper
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */

final class ModuleHelper {
  /**
   * Auto trigger a hook before and after call a _method
   *
   * @param string $method    The name of the method being called.
   * @param array  $arguments The argument is an enumerated array containing the parameters passed to the method.
   *
   * @return void
   */
  public static function __callStatic(string $method, array $arguments) {
    return self::autoTriggerEventMethod(self::class, $method, $arguments);
  }

  /**
   * Auto trigger a hook before and after call method
   *
   * @param string $class     The namespaceD of the class.
   * @param string $method    The name of the method being called.
   * @param array  $arguments The argument is an enumerated array containing the parameters passed to the method.
   *
   * @return void
   */
  public static function autoTriggerEventMethod(string $class, string $method, array $arguments) {
    // Remove _ in method name
    $existMethod = Strings::substring($method, 1);

    try {
      Callback::check($class, $existMethod);
    } catch (InvalidArgumentException $e) {
      bdump($e, 'Method not found: ' . $existMethod);
      return;
    }

    // Do a action hook before method called
    ModuleEvent::trigger('_before' . $method);

    // Call method with parameters
    $result = Callback::invokeArgs([$class, $existMethod], $arguments);

    // Do a action hook after method called
    ModuleEvent::trigger('_after' . $method);

    return $result;
  }

  /**
   * Whether current user has a specific capability
   *
   * @param string $capability Wordpress capability
   *
   * @return boolean
   */
  public static function currentUserHave(string $capability): bool {
    // Load function wp_get_current_user if not exist
    if (!function_exists('wp_get_current_user')) {
      include_once ABSPATH . 'wp-includes/pluggable.php';
    }

    return current_user_can($capability) ? true : false;
  }

  /**
   * Whether or not in dev mode
   *
   * @param string $domain Current domain
   *
   * @return boolean
   */
  public static function isDevMode(string $domain): bool {
    $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
    return $domain === $serverName;
  }

  /**
   * Auto define variant by an array
   *
   * @param string $prefix Prefix of per define
   * @param array  $input  [name => value]
   *
   * @return void
   */
  public static function autoDefine(string $prefix, array $input): void {
    array_walk(
      $input,
      function ($value, $key) use ($prefix) {
        $name = self::formatNameDefine($prefix . $key);
        defined($name) or define($name, $value);
      }
    );
  }

  /**
   * Formatted string to a valid name for define
   *
   * @param string $input Any string name
   *
   * @return string
   */
  public static function formatNameDefine(string $input): string {
    // camelCase to snake_case
    $input = ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $input)), '_');

    return preg_replace('/\W+/', '_', strtoupper($input));
  }

  /**
   * Returns the safe value of a constant or neon constant
   *
   * @param string $name The name of the constant
   *
   * @return void
   */
  public static function constant(string $name) {
    // Check neon constant %constant_name%
    if ($name[0] === '%' && $name[-1] === '%') {
      $constant = Strings::substring($name, 1, -1);
    }

    return defined($constant) ? constant($constant) : $name;
  }

  /**
   * Convert object to array
   *
   * @param stdClass $input Support object in object
   *
   * @return array
   */
  public static function objectToArray(stdClass $input): array{
    try {
      $output = Json::decode(Json::encode($input), Json::FORCE_ARRAY);
    } catch (JsonException $e) {
      $output = ['error' => $e->getMessage()];
    }
    return (array) $output;
  }

  /**
   * Convert array to object
   *
   * @param array $input Support array in array
   *
   * @return stdClass
   */
  public static function arrayToObject(array $input): stdClass {
    try {
      $output = Json::decode(Json::encode($input));
    } catch (JsonException $e) {
      $output = ['error' => $e->getMessage()];
    }
    return (object) $output;
  }

  /**
   * Convert html to array
   *
   * @param string $html Html content
   *
   * @return Html
   */
  public static function htmlToArray(string $html): array{
    if (empty($html)) {
      return ['error' => _t('Content is empty.')];
    }

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    return self::elementsToArray($dom->documentElement);
  }

  /**
   * Convert elements to array
   *
   * @param DOMElement $elements Element object
   *
   * @return array
   */
  public static function elementsToArray(DOMElement $elements): array{
    $array = ['tag' => $elements->tagName];
    foreach ($elements->attributes as $attribute) {
      $array[$attribute->name] = $attribute->value;
    }
    foreach ($elements->childNodes as $subElement) {
      if ($subElement->nodeType == XML_TEXT_NODE) {
        $array['html'] = $subElement->wholeText;
      } else {
        $array['children'][] = self::elementsToArray($subElement);
      }
    }
    return $array;
  }

  /**
   * Get full current url
   *
   * @return string
   */
  public static function getCurrentUrl(): string {
    $ssl        = filter_input(INPUT_SERVER, 'HTTPS');
    $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
    $requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI');

    $protocol = $ssl === 'on' ? 'https' : 'http';

    return "$protocol://$serverName$requestUri";
  }

  /**
   * Convert a path to uri
   *
   * @param string $path Dir or file path
   *
   * @return string
   */
  public static function pathToUrl(string $path): string {
    $ssl          = filter_input(INPUT_SERVER, 'HTTPS');
    $serverName   = filter_input(INPUT_SERVER, 'SERVER_NAME');
    $documentRoot = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
    $correctPath  = str_replace('\\', '/', $path);
    $uri          = str_replace($documentRoot, '', $correctPath);

    $protocol = $ssl === 'on' ? 'https' : 'http';

    return "$protocol://$serverName/$uri";
  }

  /**
   * Get main domain form url
   *
   * @param string $url Url
   *
   * @return string
   */
  public static function getMainDomain(string $url): string {
    // Get domain name form string
    if (preg_match_all('#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si', $url, $result, PREG_PATTERN_ORDER)) {
      $domain = $result[0][0];
    }

    $urlParsed = parse_url($domain);
    $domain    = !empty($urlParsed['host']) ? $urlParsed['host'] : false;

    // Return that url if parse domain fail
    if ($domain === false && self::isValidDomainName($url) === true) {
      $domain = $url;
    }

    return $domain;
  }

  /**
   * Check domain is valid format
   *
   * @param string $domain Domain
   *
   * @return boolean
   */
  public static function isValidDomainName(string $domain): bool {
    return filter_var($domain, FILTER_VALIDATE_DOMAIN) !== false;
  }

  /**
   * Check url is valid format
   *
   * @param string $url Any url
   *
   * @return boolean
   */
  public static function isValidUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  /**
   * Fix an unclear callback can be called
   *
   * @param mixed $callback An unclear callback
   *
   * @return array|bool
   */
  public static function fixCallback($callback) {
    //bdump($callback, 'fix Callback');
    if (is_null($callback)) {
      return false;
    }

    if (is_string($callback)) {
      $stringCallback = $callback;
      $callback       = [$stringCallback];
    }

    if (is_callable($callback)) {
      return $callback;
    }

    if (is_object($callback)) {
      $callback = array_values(ModuleHelper::objectToArray($callback));
    }

    // Get full class namespace
    $callable = self::getFullClassNameSpace($callback[0]);
    $args     = $callback[1];

    // If format: class::method(args)
    if (Strings::contains($callable, '(')) {
      $args     = Strings::after($callable, '(', 1);
      $args     = Strings::before($args, ')', 1);
      $callable = Strings::before($callable, '(', 1);
    }

    if (Strings::contains($callable, '::')) {
      $class  = Strings::before($callable, '::', 1);
      $method = Strings::after($callable, '::', 1);

      $callable = [$class, $method];
    }

    try {
      Callback::check($callable);
    } catch (InvalidArgumentException $e) {
      bdump($e, 'Callback Invalid');
      return false;
    }

    //bdump([$callable, $args], 'fixed Callback');
    return [$callable, $args];
  }

  /**
   * Get a variable form string
   *
   * @param string $string Content method get string
   *
   * @return void
   */
  public static function getVariableFormString(string $string) {
    if (Strings::contains($string, '::$')) {
      $class    = Strings::before($string, '::', 1);
      $variable = Strings::after($string, '::$', 1);

      $class = self::getFullClassNameSpace($class);

      return $class::$$variable;
    }

    if (Strings::contains($string, '->')) {
      $class    = Strings::before($string, '->', 1);
      $variable = Strings::after($string, '->', 1);

      $class = self::getFullClassNameSpace($class);

      return $class->$$variable;
    }
  }

  /**
   * Get full class namespace
   *
   * @param string   $class  Class name
   * @param stdClass $parent Namespace of this class
   *
   * @return void
   */
  public static function getFullClassNameSpace(string $class, stdClass $parent = null) {
    $parent = $parent ?? __NAMESPACE__;

    if (Strings::startsWith($class, '\\\\') !== false) {
      $class = Strings::substring($class, 1);
      $class = $parent . $class;
    }
    return $class;
  }

  /**
   * Call function in lazy style
   *
   * @param mixed $data Array ha a lazy callable
   *
   * @return void
   */
  public static function lazyInvokeArgsRecursive(&$data): void {
    if (is_array($data)) {
      // Get callback function and parameter
      $callable = array_slice($data, 0, 2);
      $args     = array_slice($data, 2);

      try {
        Callback::invokeArgs($callable, $args);
      } catch (InvalidArgumentException $e) {
        bdump($e, 'lazyInvokeArgsRecursive');
        array_walk($data, [self::class, 'lazyInvokeArgsRecursive']);
      }
    }
  }

  /**
   * Recursive searches the array for a given value and returns the first corresponding key if successful
   *
   * @param array   $data  Array data
   * @param mixed   $search     Search value
   * @param boolean $onlyParent Only return parent
   * @param mixed   $keyParent  Current key parent
   *
   * @return int
   */
  public static function arraySearchRecursive(array $data, $search, bool $onlyParent = null, $keyParent = null): int {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $keyPass = is_string($key) ? $key : $keyParent;

        $currentKey = self::arraySearchRecursive($value, $search, $onlyParent, $keyPass);

        if ($currentKey !== false) {
          return $currentKey;
        }
      } elseif ($value === $search) {
        return $onlyParent === true ? $keyParent : $key;
      }
    }

    return -1;
  }

  /**
   * Filters recursive elements of an array using a callback function
   *
   * @param array    $data   Array data
   * @param callable $callback    Callback function
   * @param boolean  $removeEmpty Remove array if empty value
   *
   * @return array
   */
  public static function arrayFilterRecursive(array $data, callable $callback, bool $removeEmpty = null): array{
    if (empty($data)) {
      return $data;
    }

    foreach ($data as $key => &$value) {
      // mind the reference
      if (is_array($value)) {
        $value = self::arrayFilterRecursive($value, $callback, $removeEmpty);
        if ($removeEmpty && !(bool) $value) {
          unset($data[$key]);
        }
        continue;
      }

      if (Callback::check($callback) && !$callback($value, $key)) {
        unset($data[$key]);
      } elseif (!(bool) $value) {
        unset($data[$key]);
      }

    }
    unset($value);
    return $data;
  }

  /**
   * Search and replaces recursive elements by array key or array value
   *
   * @param array   $data              Array data
   * @param any     $search            Search value
   * @param any     $replace           Substitute content
   * @param string  $typeSearch        Type of search value
   * @param string  $typeReplace       Replace by Key or Value
   * @param boolean $findAndReplace    Find value and replace or replace value
   * @param boolean $removeEmpty       Remove array if empty value
   * @param boolean $forceReplaceArray Force replace that is a array
   *
   * @return array
   */
  public static function arrayReplaceRecursive(array $data, $search, $replace, string $typeSearch = 'value', string $typeReplace = 'value', bool $findAndReplace = null, bool $removeEmpty = null, bool $forceReplaceArray = null): array{
    if (!is_array($data)) {
      return $data;
    }

    $funcRemoveEmpty = function ($key, bool $condition) use ($removeEmpty, &$data) {
      if ($condition && $removeEmpty) {
        unset($data[$key]);
      }
    };

    foreach ($data as $key => $value) {
      // Replace with array
      if (is_array($data[$key]) && $forceReplaceArray !== true) {
        $data[$key] = self::arrayReplaceRecursive($data[$key], $search, $replace, $typeSearch, $typeReplace, $findAndReplace, $removeEmpty);
        continue;
      }

      // Make sure the value is string
      $value = (string) $value;

      // Search by Key
      if ($typeSearch === 'key' && stripos($key, $search) !== false) {
        $data[$key] = self::replaceValueArray($data, $key, $search, $replace, $typeReplace, $findAndReplace);
      }

      // Search by Value
      elseif ($typeSearch === 'value' && stripos($value, $search) !== false) {
        $data[$key] = self::replaceValueArray($data, $key, $search, $replace, $typeReplace, $findAndReplace);
      }

      // Remove field if value is empty or search === replace
      $funcRemoveEmpty($key, $typeSearch === 'key' || $search === $replace);
    }

    return $data;
  }

  /**
   * Replace value array by key or value
   *
   * @param array   $data           Array data
   * @param string  $key            Array key value
   * @param mixed   $search         Search value
   * @param mixed   $replace        Substitute content
   * @param string  $typeReplace    Replace by Key or Value
   * @param boolean $findAndReplace Find value and replace or replace value
   *
   * @return array
   */
  public static function replaceValueArray(array $data, string $key, $search, $replace, string $typeReplace, bool $findAndReplace): string{
    $value = $data[$key];

    // $key == $search
    if ($typeReplace === 'key') {
      unset($data[$key]);
      if ($findAndReplace == true && !is_array($replace)) {
        $data[$search] = str_replace($search, $replace, $value);
        return $data[$key];
      }
      $data[$search] = $replace;
    }

    // $value == $search
    if ($typeReplace === 'value') {
      if ($findAndReplace == true && !is_array($replace)) {
        $data[$key] = str_replace($search, $replace, $value);
        return $data[$key];
      }
      $data[$key] = $replace;
    }

    return $data[$key];
  }

  /**
   * Get client IP
   *
   * @return string
   */
  public static function getClientIp(): string {
    if (getenv('HTTP_CLIENT_IP')) {
      return getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
      return getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) {
      return getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) {
      return getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) {
      return getenv('HTTP_FORWARDED');
    } elseif (getenv('REMOTE_ADDR')) {
      return getenv('REMOTE_ADDR');
    }

    return 'unknown';
  }
}
