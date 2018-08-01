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
  public function objectToArray(stdClass $input): array{
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
  public function arrayToObject(array $input): stdClass {
    try {
      return Json::decode(Json::encode($input));
    } catch (JsonException $e) {
      return (object) ['error' => $e->getMessage()];
    }
  }

  public function printVariableToHtml($mixed) {
    $sContent = print_r($mixed, true);
    $sContent = preg_replace('/(\w+\n\()/', '(', $sContent);
    return "<pre>$sContent</pre>";
  }

  public function renderArrayToHtml($array) {
    $array = $this->objectToArray($array);

    $sHTML = '<ul>';
    foreach ($array as $key => $value) {
      $sHTML .= "<li>$value</li>";
    }
    $sHTML .= '</ul>';
    return $sHTML;
  }

  public function getMainDomain($sText) {
    $sURL = $sText;

    // Get domain name form string
    if (preg_match_all('#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#si', $sText, $aResult, PREG_PATTERN_ORDER)) {
      $sURL = $aResult[0][0];
    }

    $aURL    = parse_url($sURL);
    $sDomain = !empty($aURL['host']) ? $aURL['host'] : false;

    if ($sDomain === false && $this->validDomainName($sText) === true) {
      $sDomain = $sText;
    }

    return $sDomain;
  }

  public function validDomainName($sDomain) {
    if (!preg_match("/^[a-z0-9][a-z0-9-._]{1,61}[a-z0-9]\.[a-z]{2,}$/i", $sDomain)) {
      return false;
    }
    return true;
  }

  public function arraySearchRecursive($mFind, $aData, $bOnlyParent = false, $sKeyParent = 0) {
    foreach ($aData as $sKey => $nValue) {
      if (is_array($nValue)) {
        $sPass = $sKeyParent;
        if (is_string($sKey)) {
          $sPass = $sKey;
        }
        $currentKey = $this->arraySearchRecursive($mFind, $nValue, $bOnlyParent, $sPass);
        if ($currentKey !== false) {
          return $currentKey;
        }
      } else if ($nValue === $mFind) {
        if ($bOnlyParent === true) {
          return $sKeyParent;
        }
        return $sKey;
      }
    }

    return false;
  }

  public function arrayFilterRecursive($aData, $fCallback = null, $bRemoveEmpty = false) {
    if (empty($aData)) {
      return $aData;
    }

    foreach ($aData as $sKey => &$nValue) {
      // mind the reference
      if (is_array($nValue)) {
        $nValue = $this->arrayFilterRecursive($nValue, $fCallback, $bRemoveEmpty);
        if ($bRemoveEmpty && !(bool) $nValue) {
          unset($aData[$sKey]);
        }
      } else {
        if (!is_null($fCallback) && !$fCallback($nValue, $sKey)) {
          unset($aData[$sKey]);
        } elseif (!(bool) $nValue) {
          unset($aData[$sKey]);
        }
      }
    }
    unset($nValue);
    return $aData;
  }

  public function arrayReplaceRecursive($aData, $mFind, $mReplace, $sFindType = 'value', $sReplaceType = 'value', $bFindReplace = false, $bRemoveEmpty = false, $bForceArray = false) {
    if (is_array($aData)) {
      foreach ($aData as $sKey => $Val) {
        // Replace with array
        if (is_array($aData[$sKey]) && $bForceArray === false) {
          $aData[$sKey] = $this->arrayReplaceRecursive($aData[$sKey], $mFind, $mReplace, $sFindType, $sReplaceType, $bFindReplace, $bRemoveEmpty);
        } else {
          // Found find by key
          if ($sFindType === 'key' && stripos($sKey, $mFind) !== false) {
            $aData[$sKey] = $this->replaceByType($aData, $sKey, $mFind, $mReplace, $sReplaceType, $bFindReplace);
            // Remove field by empty value
            if ($bRemoveEmpty === true && empty($aData[$sKey])) {
              unset($aData[$sKey]);
            }
            // Found find by value
          } elseif ($sFindType === 'value' && stripos($Val, $mFind) !== false) {
            $aData[$sKey] = $this->replaceByType($aData, $sKey, $mFind, $mReplace, $sReplaceType, $bFindReplace);
            // Remove field by empty value or by mFind === mReplace
            if ($bRemoveEmpty === true && (empty($aData[$sKey]) || $mFind === $mReplace)) {
              unset($aData[$sKey]);
            }
          }
        }
      }
    }
    return $aData;
  }

  private function replaceByType($aData, $sKey, $mFind, $mReplace, $sReplaceType, $bFindReplace) {
    $mValueOfKey = $aData[$sKey];
    // $sKey == $mFind
    if ($sReplaceType === 'key') {
      unset($aData[$sKey]);
      if ($bFindReplace == true && !is_array($mReplace)) {
        $aData[$mFind] = str_replace($mFind, $mReplace, $mValueOfKey);
      } else {
        $aData[$mFind] = $mReplace;
      }

      // $mValueOfKey == $mFind
    } elseif ($sReplaceType === 'value') {
      if ($bFindReplace == true && !is_array($mReplace)) {
        $aData[$sKey] = str_replace($mFind, $mReplace, $mValueOfKey);
      } else {
        $aData[$sKey] = $mReplace;
      }
    }
    return $aData[$sKey];
  }

  public function getCurrentURL() {
    $sPageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$sPageURL .= "s";}
    $sPageURL .= "://";
    $sPageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    return $sPageURL;
  }

  /**
   * Quick create a user wordpress
   *
   * @param string $userName User name
   * @param string $email    Email
   *
   * @return integer
   */
  public static function createUser(string $userName, string $email): integer {
    $userId = username_exists($userName);
    if (!$userId and email_exists($email) == false) {
      $randomPassword = wp_generate_password(12, false);
      $userId         = wp_create_user($userName, $randomPassword, $email);
    }
    return $userId;
  }

  public function updateUserMeta(init $userId, array $data, bool $forceUpdate = false) {
    $userMeta = [];
    if (!$forceUpdate) {
      $userMeta = get_user_meta($userId);
    }

    // Format data
    $formatedData = array_map(function (mixed $value) {
      $mOriginalValue = $value;
      if (!is_string($value) && !is_numeric($value)) {
        try {
          $value = Json::encode($value);
        } catch (JsonException $e) {
          $value = $mOriginalValue;
        }
      }
      return is_null($value) ? '' : $value;
    }, $data);

    if (empty($userMeta['restrict_access_ips']) && !empty($formatedData['restrict_access_ips'])) {
      update_user_meta($userId, 'restrict_access_ips', $formatedData['restrict_access_ips']);
    }
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
