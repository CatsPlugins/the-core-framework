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
use Nette\Utils\Callback;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

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
   * Convert object to array
   *
   * @param stdClass $input Support object in object
   *
   * @return array
   */
  public static function objectToArray(stdClass $input): array{
    try {
      return Json::decode(Json::encode($input), Json::FORCE_ARRAY);
    } catch (JsonException $e) {
      return ['error' => $e->getMessage()];
    }
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
      return Json::decode(Json::encode($input));
    } catch (JsonException $e) {
      return (object) ['error' => $e->getMessage()];
    }
  }

  /**
   * Convert variable to HTML format
   *
   * @param mixed $content Any data
   *
   * @return string
   */
  public static function variableToHtml(mixed $content): string {
    $content = print_r($content, true);
    $content = preg_replace('/(\w+\n\()/', '(', $content);
    return "<pre>$content</pre>";
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
    return !preg_match("/^[a-z0-9][a-z0-9-._]{1,61}[a-z0-9]\.[a-z]{2,}$/i", $domain) ? false : true;
  }

  /**
   * Check url is valid format
   *
   * @param string $url Any url
   * 
   * @return boolean
   */
  public static function isValidUrl(string $url): bool {
    return !preg_match("/^(?:http(s)?:)?\/\/[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:\/?#[\]@!\$&'\(\)\*\+,;=.]+$/im", $url) ? true : false;
  }

  /**
   * Recursive searches the array for a given value and returns the first corresponding key if successful
   *
   * @param array   $arrayData  Array data
   * @param mixed   $search     Search value
   * @param boolean $onlyParent Only return parent
   * @param init    $keyParent  Current key parent
   *
   * @return init
   */
  public static function arraySearchRecursive(array $arrayData, mixed $search, bool $onlyParent = null, init $keyParent = null): init {
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
  public static function arrayReplaceRecursive(array $arrayData, mixed $search, mixed $replace, string $typeSearch = 'value', string $typeReplace = 'value', bool $findAndReplace = null, bool $removeEmpty = null, bool $forceReplaceArray = null): array{
    if (!is_array($arrayData)) {
      return $arrayData;
    }

    $funcRemoveEmpty = function (mixed $arrayKey, bool $condition) use ($removeEmpty, &$arrayData) {
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
  public static function replaceValueArray(array $arrayData, string $arrayKey, mixed $search, mixed $replace, string $typeReplace, bool $findAndReplace): array{
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
