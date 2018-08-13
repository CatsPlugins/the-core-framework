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

use GuzzleHttp\Client;
use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use \stdClass;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

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
   * Translate text with textdomain
   *
   * @param mixed $text Text to translate
   *
   * @return string
   */
  public static function trans($text): string {
    return __($text, ModuleCore::$textDomain);
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
      include ABSPATH . 'wp-includes/pluggable.php';
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
   * @return void
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
    $callback[0] = self::getFullClassNameSpace($callback[0]);

    if (Strings::contains($callback[0], '(')) {
      $args        = Strings::after($callback[0], '(', 1);
      $callback[1] = Strings::before($args, ')', 1);
      $callback[0] = Strings::before($callback[0], '(', 1);
    }

    if (Strings::contains($callback[0], '::')) {
      $class  = Strings::before($callback[0], '::', 1);
      $method = Strings::after($callback[0], '::', 1);

      $callback[0] = [$class, $method];
    }

    //bdump($callback, 'fixed Callback');
    try {
      Callback::check($callback[0]);
    } catch (InvalidArgumentException $e) {
      bdump($e, 'Callback Invalid');
      return false;
    }
    return $callback;
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

    if (Strings::contains($class, '\\') === false) {
      $class = $parent . '\\' . $class;
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
   * @param array   $arrayData  Array data
   * @param mixed   $search     Search value
   * @param boolean $onlyParent Only return parent
   * @param mixed   $keyParent  Current key parent
   *
   * @return int
   */
  public static function arraySearchRecursive(array $arrayData, $search, bool $onlyParent = null, $keyParent = null): int {
    foreach ($arrayData as $key => $value) {
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
   * @param array    $arrayData   Array data
   * @param callable $callback    Callback function
   * @param boolean  $removeEmpty Remove array if empty value
   *
   * @return array
   */
  public static function arrayFilterRecursive(array $arrayData, callable $callback, bool $removeEmpty = null): array{
    if (empty($arrayData)) {
      return $arrayData;
    }

    foreach ($arrayData as $key => &$value) {
      // mind the reference
      if (is_array($value)) {
        $value = self::arrayFilterRecursive($value, $callback, $removeEmpty);
        if ($removeEmpty && !(bool) $value) {
          unset($arrayData[$key]);
        }
        continue;
      }

      if (Callback::check($callback) && !$callback($value, $key)) {
        unset($arrayData[$key]);
      } elseif (!(bool) $value) {
        unset($arrayData[$key]);
      }

    }
    unset($value);
    return $arrayData;
  }

  /**
   * Search and replaces recursive elements by array key or array value
   *
   * @param array   $arrayData         Array data
   * @param mixed   $search            Search value
   * @param mixed   $replace           Substitute content
   * @param string  $typeSearch        Type of search value
   * @param string  $typeReplace       Replace by Key or Value
   * @param boolean $findAndReplace    Find value and replace or replace value
   * @param boolean $removeEmpty       Remove array if empty value
   * @param boolean $forceReplaceArray Force replace that is a array
   *
   * @return array
   */
  public static function arrayReplaceRecursive(array $arrayData, $search, $replace, string $typeSearch = 'value', string $typeReplace = 'value', bool $findAndReplace = null, bool $removeEmpty = null, bool $forceReplaceArray = null): array{
    if (!is_array($arrayData)) {
      return $arrayData;
    }

    $funcRemoveEmpty = function ($arrayKey, bool $condition) use ($removeEmpty, &$arrayData) {
      if ($condition && $removeEmpty) {
        unset($arrayData[$arrayKey]);
      }
    };

    foreach ($arrayData as $arrayKey => $arrayValue) {
      // Replace with array
      if (is_array($arrayData[$arrayKey]) && $forceReplaceArray === false) {
        $arrayData[$arrayKey] = self::arrayReplaceRecursive($arrayData[$arrayKey], $search, $replace, $typeSearch, $typeReplace, $findAndReplace, $removeEmpty);
        continue;
      }

      // Search by Key
      if ($typeSearch === 'key' && stripos($arrayKey, $search) !== false) {
        $arrayData[$arrayKey] = self::replaceValueArray($arrayData, $arrayKey, $search, $replace, $typeReplace, $findAndReplace);
      }

      // Search by Value
      elseif ($typeSearch === 'value' && stripos($arrayValue, $search) !== false) {
        $arrayData[$arrayKey] = self::replaceValueArray($arrayData, $arrayKey, $search, $replace, $typeReplace, $findAndReplace);
      }

      // Remove field if value is empty or search === replace
      $funcRemoveEmpty($arrayKey, $typeSearch === 'key' || $search === $replace);
    }

    return $arrayData;
  }

  /**
   * Replace value array by key or value
   *
   * @param array   $arrayData      Array data
   * @param string  $arrayKey       Array key value
   * @param mixed   $search         Search value
   * @param mixed   $replace        Substitute content
   * @param string  $typeReplace    Replace by Key or Value
   * @param boolean $findAndReplace Find value and replace or replace value
   *
   * @return array
   */
  public static function replaceValueArray(array $arrayData, string $arrayKey, $search, $replace, string $typeReplace, bool $findAndReplace): array{
    $value = $arrayData[$arrayKey];

    // $arrayKey == $search
    if ($typeReplace === 'key') {
      unset($arrayData[$arrayKey]);
      if ($findAndReplace == true && !is_array($replace)) {
        $arrayData[$search] = str_replace($search, $replace, $value);
        return $arrayData[$arrayKey];
      }
      $arrayData[$search] = $replace;
    }

    // $value == $search
    if ($typeReplace === 'value') {
      if ($findAndReplace == true && !is_array($replace)) {
        $arrayData[$arrayKey] = str_replace($search, $replace, $value);
        return $arrayData[$arrayKey];
      }
      $arrayData[$arrayKey] = $replace;
    }

    return $arrayData[$arrayKey];
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
