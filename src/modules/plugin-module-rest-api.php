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

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module RestAPI
 *
 * Create Rest API form Wordpress
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleRestAPI
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleRestAPI {
  
  public function init(array $config):void {
    defined($name) or define($name, $value);
    define('STS_API_VERSION', 'v1');
    define('STS_API_NAMESPACE', TCF_TEXTDOMAIN.'/' . STS_API_VERSION . '/');
    define('STS_API_PATH', TCF_INCLUDES . 'rest-api' . DS);
    define('STS_API_NONCE', wp_create_nonce('wp_rest'));
  }

  ///////////// API Purchase/Active /////////////
  private function setupRestApiEnvato() {
    require_once STS_API_PATH . 'rest-api-envato.php';
    $oApi = new RestApiEnvato($this->TCF);
    add_action('rest_api_init', [$oApi, 'registerRestRoute'], 10);
  }

  private function setupRestApiUsers() {
    require_once STS_API_PATH . 'rest-api-users.php';
    $oApi = new RestApiUser($this->TCF);
    add_action('rest_api_init', [$oApi, 'registerRestRoute'], 10);
  }
}