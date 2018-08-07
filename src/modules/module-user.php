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
use Nette\SmartObject;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module User
 *
 * Utils method
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleUser
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleUser {
  /**
   * Quick create a user wordpress
   *
   * @param string $userName User name
   * @param string $email    Email
   *
   * @return init
   */
  public static function createUser(string $userName, string $email): init {
    $userId = username_exists($userName);
    if (!$userId and email_exists($email) == false) {
      $randomPassword = wp_generate_password(12, false);
      $userId         = wp_create_user($userName, $randomPassword, $email);
    }
    return $userId;
  }

  /**
   * Multi update user meta
   *
   * @param init    $userId      User ID
   * @param array   $data        Meta data
   * @param boolean $forceUpdate Force update new value
   *
   * @return void
   */
  public function updateUserMeta(init $userId, array $data, bool $forceUpdate = null):void {
    $userMeta = [];
    if ($forceUpdate === false) {
      $userMeta = get_user_meta($userId);
    }

    // Format correct data
    $formatedData = array_map(
      function ($value) {
        $originalValue = $value;
        if (!is_string($value) && !is_numeric($value)) {
          try {
            $value = Json::encode($value);
          } catch (JsonException $e) {
            $value = $originalValue;
          }
        }
        return is_null($value) ? '' : $value;
      }, $data
    );

    // Update meta
    array_walk(
      function ($value, $key) use ($userId, $formatedData) {
        if (empty($value) && !empty($formatedData[$key])) {
          update_user_meta($userId, $key, $formatedData[$key]);
        }
      }
    );
  }
}