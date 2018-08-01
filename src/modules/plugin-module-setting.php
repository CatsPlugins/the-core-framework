<?php
// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

use Nette\Neon\Exception as NE;
use Nette\Neon\Neon;
use Tracy\Debugger;

class TCF_Setting {
  private $option;

  ///////////// Call TCFCore /////////////
  public function __construct(&$TCF) {
    $this->TCF = &$TCF;
  }

  ///////////// Register Debugger /////////////
  public function registerDebugger($bForce = false) {
    if (!function_exists('wp_get_current_user')) {
      include ABSPATH . "wp-includes/pluggable.php";
    }

    // Fix output has been sent
    $sOutput = ob_get_contents();
    if (!empty($sOutput)) {
      ob_end_clean();
    }

    Debugger::$showLocation = true;
    Debugger::$maxDepth     = 4; // default: 3
    Debugger::$maxLength    = 650; // default: 150
    Debugger::$strictMode   = false;

    if (current_user_can('administrator') || $bForce) {
      Debugger::enable(Debugger::DEVELOPMENT);
    } else {
      // PRODUCTION
      Debugger::enable(Debugger::PRODUCTION);
    }

    error_reporting(E_ALL & ~E_NOTICE);

    if (!empty($sOutput)) {
      bdump($sOutput);
    }
  }

  ///////////// Register Setting /////////////
  public function readSetting($fileName) {
    $sFileContent = file_get_contents(TCF_PATH_CONFIG . $fileName . '.neon');

    try {
      $aConfig = Neon::decode($sFileContent);
    } catch (NE $e) {
      dump($e);
      exit;
    }
    
    bdump($aConfig);
    $this->option = $aConfig;
  }

  ///////////// Action /////////////

  public function getOption($sKey = '', $bRaw = false) {
    $nValue = empty($sKey) ? false : get_option($sKey, false);

    if ($nValue === false && isset($this->option[$sKey]['value'])) {
      $nValue = $this->option[$sKey]['value'];
    }

    if ($bRaw === true || !isset($this->option[$sKey]['type'])) {
      return $nValue;
    }

    switch ($this->option[$sKey]['type']) {
    case 'string':
      $nValue = strval($nValue);
      break;
    case 'integer':
      $nValue = intval($nValue);
      break;
    case 'number':
      $nValue = floatval($nValue);
      break;
    case 'boolean':
      $nValue = boolval($nValue);
      break;
    case 'array':
      if (!is_array($nValue)) {
        $nValue = json_decode($nValue, true);
      }
      break;
    }

    return $nValue;
  }

  public function setOption($sKey, $nValue) {
    $bResult = false;

    switch ($this->option[$sKey]['type']) {
    case 'string':
      $nValue = strval($nValue);
      break;
    case 'integer':
      $nValue = intval($nValue);
      break;
    case 'number':
      $nValue = floatval($nValue);
      break;
    case 'boolean':
      $nValue = boolval($nValue) ? 1 : 0;
      break;
    case 'array':
      if (is_array($nValue)) {
        $nValue = json_encode($nValue, true);
      } elseif (!is_array($nValue)) {
        $nValue = [$nValue];
      }
      break;
    default:
      return false;
    }

    //Check for valid options
    if (!empty($this->option[$sKey]['type'])) {
      $bResult = update_option($sKey, $nValue);
    }

    return $bResult;
  }

  ///////////// Internationalizing /////////////
  public function initLanguage($sTextDomain) {
    defined('TCF_TEXTDOMAIN') ? null : define('TCF_TEXTDOMAIN', $sTextDomain);
    load_plugin_textdomain(TCF_TEXTDOMAIN, false, basename(TCF_PATH_BASE) . DS . 'languages' . DS);
  }

  ///////////// Register All Scripts/Styles /////////////
  public function registerAllScripts() {

    // Filter URL script
    add_filter('clean_url', [$this->TCF->tool, 'parseURLScripts'], 11, 1);

    wp_register_script(TCF_TEXTDOMAIN . '-materialize', TCF_URL . 'admin/assets/js/materialize.min.js', ['jquery'], false, true);
    wp_register_script(TCF_TEXTDOMAIN . '-jscookie', TCF_URL . 'admin/assets/js/js.cookie.min.js', ['jquery'], false, true);

    // Global Script
    wp_register_script(TCF_TEXTDOMAIN . '-script-global', TCF_URL . 'public/assets/js/uwr-global.js', ['jquery'], false, true);

    // Admin Script
    wp_register_script(TCF_TEXTDOMAIN . '-script-TCF-admin', TCF_URL . 'admin/assets/js/TCF-admin.js', ['jquery'], false, true);
    wp_register_script(TCF_TEXTDOMAIN . '-script-uwr-admin', TCF_URL . 'admin/assets/js/uwr-admin.js', ['jquery'], false, true);
  }

  public function registerAllStyles() {
    wp_register_style(TCF_TEXTDOMAIN . '-materialize', TCF_URL . 'admin/assets/css/materialize.min.css', [], false);
    wp_register_style(TCF_TEXTDOMAIN . '-materialize-custom', TCF_URL . 'admin/assets/css/materialize.custom.css', [], true);
    wp_register_style(TCF_TEXTDOMAIN . '-materialize-icon', esc_url_raw('https://fonts.googleapis.com/icon?family=Material+Icons'), [], false);
  }

  ///////////// Enqueue Global Scripts/Styles /////////////
  public function enqueueGlobalScript() {
    $globalData = apply_filters(TCF_TEXTDOMAIN . '_global_js_data', []);
    wp_localize_script(TCF_TEXTDOMAIN . '-script-global', TCF_TEXTDOMAIN . 'Data', $globalData);
    wp_enqueue_script(TCF_TEXTDOMAIN . '-script-global');
  }

  public function enqueueGlobalStyle() {

  }

  ///////////// Enqueue Admin Scripts/Styles /////////////
  public function enqueueAdminScript() {
    wp_enqueue_script(TCF_TEXTDOMAIN . '-materialize');
    wp_enqueue_script(TCF_TEXTDOMAIN . '-jscookie');

    $adminData = apply_filters(TCF_TEXTDOMAIN . '_admin_js_data', []);
    wp_localize_script(TCF_TEXTDOMAIN . '-script-TCF-admin', 'TCFData', $adminData);
    wp_enqueue_script(TCF_TEXTDOMAIN . '-script-TCF-admin');

    wp_enqueue_script(TCF_TEXTDOMAIN . '-script-uwr-admin');
  }

  public function enqueueAdminStyle() {
    wp_enqueue_style(TCF_TEXTDOMAIN . '-materialize');
    wp_enqueue_style(TCF_TEXTDOMAIN . '-materialize-custom');
    wp_enqueue_style(TCF_TEXTDOMAIN . '-materialize-icon');
  }
}