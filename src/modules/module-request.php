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
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

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
   * Format result from endpoint
   *
   * @param array $result The endpoint,ajax,.. result
   *
   * @return array
   */
  public static function formatResult(array $result): array{
    // Check result format
    $finalResult = [];
    $isSuccess   = isset($result['success']) ? true : false;

    if ($isSuccess === false && isset($result['data'])) {
      $finalResult            = $result;
      $finalResult['success'] = boolval($result['data']);
    } elseif ($isSuccess === true && (isset($result['message']) || isset($result['data']))) {
      $finalResult = $result;
    } elseif ($isSuccess === true) {
      $finalResult['success'] = $result['success'];
      unset($result['success']);
      $finalResult['data'] = $result;
    } else {
      $finalResult['success'] = true;
      $finalResult['data']    = $result;
    }

    return $finalResult;
  }

  /**
   * Send request to a endpoint in endpoint.neon
   *
   * @param string $endpoint Endpoint ID
   *
   * @return array
   */
  public static function request(string $endpoint): array{
    $result            = [];
    $result['success'] = false;

    // Setup endpoint config
    $requestConfig = self::setupEndpointConfig($endpoint);

    // Check for errors
    if (!empty($requestConfig['message'])) {
      return $requestConfig;
    }

    $uri     = $requestConfig['uri'] ?? '';
    $method  = $requestConfig['method'] ?? false;
    $options = $requestConfig['option'] ?? false;

    if ($method === false || $options === false) {
      $result['message'] = _t('Invalid endpoint config.');
      return $result;
    }

    // Load cache
    if ($requestConfig['cache'] === true) {
      $cacheKey = ModuleCache::makeKey([endpoint, $requestConfig]);
      $result   = ModuleCache::Endpoint($cacheKey);
      if (!empty($result)) {
        return $result;
      }
    }

    $oClient = new Client($options);

    // Send request
    try {
      $oResponse = $oClient->request($method, $uri);
    } catch (RequestException $e) {
      $oResponse = Psr7\str($e->getRequest());
      if ($e->hasResponse()) {
        $oResponse = Psr7\str($e->getResponse());
      }
    }

    // Processed data received
    if (is_object($oResponse)) {
      $body = (string) $oResponse->getBody();

      if (empty($body)) {
        $result['message'] = 'No result';
        return $result;
      }
    } elseif (is_string($oResponse)) {
      // This may be an error
      list($header, $body) = explode("\r\n\r\n", $oResponse, 2);

      // Try convert unknown content to json
      try {
        Json::decode($body, Json::FORCE_ARRAY);
      } catch (JsonException $e) {
        $result['message'] = $e->getMessage();
        $result['debug']   = [
          'header'         => explode("\n", $header),
          'body'           => ModuleHelper::htmlToArray($body),
          'raw_data'       => $oResponse,
          'request_config' => $requestConfig,
        ];
        return $result;
      }
    }

    // Try get content json
    try {
      $result = Json::decode($body, Json::FORCE_ARRAY);
    } catch (JsonException $e) {
      $result['message'] = $e->getMessage();
      $result['data']    = $body;
      return $result;
    }

    // Format result
    $result = self::formatResult($result);

    if ($requestConfig['cache'] === true && $result['success'] !== false) {
      ModuleCache::Endpoint($cacheKey, $result);
    }

    return $result;
  }

  /**
   * Setup endpoint config
   *
   * @param string $endpoint Endpoint ID
   *
   * @return array
   */
  public static function setupEndpointConfig(string $endpoint): array{
    $requestConfig            = [];
    $requestConfig['success'] = false;

    // Get endpoint config
    $endpointConfig = ModuleConfig::Endpoint()->$endpoint ?? false;

    if ($endpointConfig === false) {
      $requestConfig['message'] = _t('The endpoint config does not exist.');
      return $requestConfig;
    }

    if ($endpointConfig->enable === false) {
      $requestConfig['message'] = _t('The endpoint are disabled.');
      return $requestConfig;
    }

    // Convert endpointConfig to array for arrayReplaceRecursive
    $endpointConfig = ModuleHelper::objectToArray($endpointConfig);

    if (isset($endpointConfig['error'])) {
      $requestConfig['message'] = _t('The endpoint are invalid.');
      return $requestConfig;
    }

    // Find and replace input value define by %@[name]%
    $sEndpointConfig = Json::encode($endpointConfig);
    $sEndpointConfig = ModuleConfig::returnValueFilter($endpoint, $sEndpointConfig);

    try {
      $requestConfig = Json::decode($sEndpointConfig, Json::FORCE_ARRAY);
    } catch (JsonException $e) {
      $requestConfig['message'] = _t('The endpoint are invalid.');
      $requestConfig['error']   = $e->getMessage();
      return $requestConfig;
    }

    $defaultConfig = [
      'timeout'         => 15,
      'allow_redirects' => false,
      'debug'           => false,
      'verify'          => TCPF_WP_PATH_INCLUDES . 'cacert.pem',
    ];

    // Merge default config option
    $requestConfig['option'] = array_merge($defaultConfig, $requestConfig['option']);

    return $requestConfig;
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
      ModuleEvent::on('wp_ajax_nopriv_' . $action, [self::class, 'managerAjaxRequest']);
    }

    // Add this action in filter _callback_ajax
    $action = ModuleEvent::makeTag($action);

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
   * Get the current hash key for each action
   *
   * @param string $action Action ajax
   *
   * @return void
   */
  public static function getHash(string $action) {
    // TODO: switch hash by auth mode
    return wp_create_nonce($action);
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
      $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_ENCODED);
    } elseif ($method === 'POST') {
      $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_ENCODED);
    }

    if (empty($action)) {
      http_response_code(404);
      $result['message'] = _t('Ajax action not found.');
      ModuleRender::sendJson($result);
    }

    // Get config ajax event form filter
    $config = ModuleEvent::filter('_callback_ajax', []);

    // Check security by WP Nonce
    if (check_ajax_referer($action, 'hash', false) === false) {
      http_response_code(403);
      $result['message'] = _t('Invalid request data.');
      ModuleRender::sendJson($result);
    }

    $capability = ModuleHelper::currentUserHave($config[$action]['capability']);

    if ($capability === false && $config[$action]['capability'] !== 'all') {
      http_response_code(403);
      $result['message'] = _t('You do not have permission to use.');
      ModuleRender::sendJson($result);
    }

    $callable = $config[$action]['callback'][0] ?? null;

    if ($callable === false) {
      http_response_code(404);
      $result['message'] = _t('Ajax callback not found.');
      ModuleRender::sendJson($result);
    }

    try {
      $result = Callback::invoke($callable, $result);
    } catch (InvalidArgumentException $e) {
      http_response_code(500);
      $result['message'] = _t($e->getMessage());
    }

    // Format result
    $result = self::formatResult($result);

    ModuleRender::sendJson($result);
  }
}