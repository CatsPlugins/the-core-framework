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

use Nette\InvalidArgumentException;
use Nette\Utils\Callback;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Request
 *
 * Process of request endpoint, API, Ajax
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleRequest
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleRequest {
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
   * Setup multiple ajax event
   *
   * @param array $ajaxConfigs The ajax configs
   *
   * @return void
   */
  public static function setupMultipleAjax(array $ajaxConfigs): void {
    foreach ($ajaxConfigs as $ajaxConfig) {
      self::setupAjax(...$ajaxConfig);
    }
  }

  /**
   * Setup a ajax event
   *
   * @param string $capability The capability of current user
   * @param string $action     Ajax action name
   * @param array  $callback   Ajax callback
   *
   * @return void
   */
  public static function setupAjax(string $capability, string $action, string $callback): void {
    // Convert string callback to object 
    $callback = ModuleHelper::fixCallback($callback);

    // Create a wp_ajax managed by managerAjaxRequest event
    ModuleEvent::on('wp_ajax_' . $action, [self::class, 'managerAjaxRequest']);

    // Create a wp_ajax_nopriv managed by managerAjaxRequest event
    if ($capability === 'all') {
      ModuleEvent::on('wp_ajax_nopriv' . $action, [self::class, 'managerAjaxRequest']);
    }

    // Add this action in filter _callback_ajax
    ModuleEvent::on(
      '_callback_ajax',
      function ($callbacks) use ($capability, $action, $callback) {
        $callbacks[$action] = [
          'capability' => $capability,
          'callback'   => $callback,
        ];
        return $callbacks;
      }
    );
  }

  /**
   * Manage ajax requests
   *
   * @return void
   */
  public static function managerAjaxRequest() {
    $result            = [];
    $result['success'] = false;

    $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');

    if ($method === 'GET') {
      $action = filter_input(INPUT_GET, 'event', FILTER_SANITIZE_ENCODED);
    } elseif ($method === 'POST') {
      $action = filter_input(INPUT_POST, 'event', FILTER_SANITIZE_ENCODED);
    }

    if (empty($action)) {
      http_response_code(404);
      $result['message'] = _t('Ajax action not found.');
      ModuleRender::sendJson($result);
    }

    // Get config ajax event form filter
    $config = ModuleEvent::filter('_callback_ajax', []);

    $capability = ModuleHelper::currentUserHave($config[$action]['capability']);

    if ($capability === false && $config[$action]['capability'] !== 'all') {
      http_response_code(403);
      $result['message'] = _t('You do not have permission to use.');
      ModuleRender::sendJson($result);
    }

    $callable = $config[$action]['callback'] ?? false;

    if ($callable === false) {
      http_response_code(404);
      $result['message'] = _t('Ajax callback not found.');
      ModuleRender::sendJson($result);
    }

    try {
      $result = Callback::invokeArgs($callable, $result);
    } catch (InvalidArgumentException $e) {
      bdump($e, 'InvalidArgumentException');
      http_response_code(500);
      $result['message'] = _t($e->getMessage());
    }

    ModuleRender::sendJson($result);
  }

  /////////// Callback Ajax /////////////
  public function saveOptions($result) {
    $aFilter = [
      'options' => [
        'flags' => FILTER_FORCE_ARRAY,
        'name'  => FILTER_SANITIZE_ENCODED,
        'value' => FILTER_SANITIZE_ENCODED,
      ],
    ];

    $aValidate = filter_input_array(INPUT_POST, $aFilter);

    if (empty($aValidate)) {
      http_response_code(403);
      $result['message'] = __('Save options failed!', PWP_TEXTDOMAIN);
      return;
    }

    $aOption = $aValidate['options'];

    foreach ($aOption as $sOption => $aValue) {
      $aValue['value']   = is_string($aValue['value']) ? stripslashes($aValue['value']) : $aValue['value'];
      $aOption[$sOption] = $this->PWP->setting->setOption($aValue['name'], $aValue['value']);
    }

    $result['success'] = true;
    $result['message'] = __('Save options success!', PWP_TEXTDOMAIN);
    $result['debug']   = $aOption;
  }
}