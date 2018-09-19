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

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

use Merlin;
use Nette\Utils\Strings;

/**
 * The Module Debugger
 *
 * Render template or variable to json, html, text formated end exit process
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleLicense
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleLicense {
  private static $merlinConfigs;
  private static $merlinStrings;

  /**
   * Setup MerlinWP
   *
   * @param array $configs The MerlinWP configs
   * @param array $strings The MerlinWP strings
   *
   * @return Merlin
   */
  public static function setupMerlin(array $configs = null, array $strings = null): Merlin {
    $defaultConfigs = [
      'directory'            => 'merlin', // Location / directory where Merlin WP is placed in your theme.
      'merlin_url'           => 'merlin', // The wp-admin page slug where Merlin WP loads.
      'child_action_btn_url' => '', // URL for the 'child-action-link'.
      'dev_mode'             => false, // Enable development mode for testing.
      'license_step'         => true, // EDD license activation step.
      'license_required'     => false, // Require the license activation step.
      'license_help_url'     => '', // URL for the 'license-tooltip'.
      'edd_remote_api_url'   => '', // EDD_Theme_Updater_Admin remote_api_url.
      'edd_item_name'        => '', // EDD_Theme_Updater_Admin item_name.
      'edd_theme_slug'       => ModuleCore::$textDomain, // EDD_Theme_Updater_Admin item_slug.
    ];

    $defaultStrings = [
      'admin-menu'               => esc_html(_t('Plugin Setup')),
      /* translators: 1: Title Tag 2: Plugin Name 3: Closing Title Tag */
      'title%s%s%s%s'            => esc_html(_t('%1$s%2$s Plugin &lsaquo; Plugin Setup: %3$s%4$s')),
      'return-to-dashboard'      => esc_html(_t('Return to the dashboard')),
      'ignore'                   => esc_html(_t('Disable this wizard')),
      'btn-skip'                 => esc_html(_t('Skip')),
      'btn-next'                 => esc_html(_t('Next')),
      'btn-start'                => esc_html(_t('Start')),
      'btn-no'                   => esc_html(_t('Cancel')),
      'btn-plugins-install'      => esc_html(_t('Install')),
      'btn-child-install'        => esc_html(_t('Install')),
      'btn-content-install'      => esc_html(_t('Install')),
      'btn-import'               => esc_html(_t('Import')),
      'btn-license-activate'     => esc_html(_t('Activate')),
      'btn-license-skip'         => esc_html(_t('Later')),
      /* translators: Plugin Name */
      'license-header%s'         => esc_html(_t('Activate %s')),
      /* translators: Plugin Name */
      'license-header-success%s' => esc_html(_t('%s is Activated')),
      /* translators: Plugin Name */
      'license%s'                => esc_html(_t('Enter your license key to enable remote updates and plugin support.')),
      'license-label'            => esc_html(_t('License key')),
      'license-success%s'        => esc_html(_t('The plugin is already registered, so you can go to the next step!')),
      'license-json-success%s'   => esc_html(_t('Your plugin is activated! Remote updates and plugin support are enabled.')),
      'license-tooltip'          => esc_html(_t('Need help?')),
      /* translators: Plugin Name */
      'welcome-header%s'         => esc_html(_t('Welcome to %s')),
      'welcome-header-success%s' => esc_html(_t('Hi. Welcome back')),
      'welcome%s'                => esc_html(_t('This wizard will set up your plugin, install plugins, and import content. It is optional & should take only a few minutes.')),
      'welcome-success%s'        => esc_html(_t('You may have already run this plugin setup wizard. If you would like to proceed anyway, click on the "Start" button below.')),
      'child-header'             => esc_html(_t('Install Child Theme')),
      'child-header-success'     => esc_html(_t('You\'re good to go!')),
      'child'                    => esc_html(_t('Let\'s build & activate a child theme so you may easily make theme changes.')),
      'child-success%s'          => esc_html(_t('Your child theme has already been installed and is now activated, if it wasn\'t already.')),
      'child-action-link'        => esc_html(_t('Learn about child themes')),
      'child-json-success%s'     => esc_html(_t('Awesome. Your child theme has already been installed and is now activated.')),
      'child-json-already%s'     => esc_html(_t('Awesome. Your child theme has been created and is now activated.')),
      'plugins-header'           => esc_html(_t('Install Plugins')),
      'plugins-header-success'   => esc_html(_t('You\'re up to speed!')),
      'plugins'                  => esc_html(_t('Let\'s install some essential WordPress plugins to get your site up to speed.')),
      'plugins-success%s'        => esc_html(_t('The required WordPress plugins are all installed and up to date. Press "Next" to continue the setup wizard.')),
      'plugins-action-link'      => esc_html(_t('Advanced')),
      'import-header'            => esc_html(_t('Import Content')),
      'import'                   => esc_html(_t('Let\'s import content to your website, to help you get familiar with the plugin.')),
      'import-action-link'       => esc_html(_t('Advanced')),
      'ready-header'             => esc_html(_t('All done. Have fun!')),
      /* translators: Theme Author */
      'ready%s'                  => esc_html(_t('Your plugin has been all set up. Enjoy your new plugin by %s.')),
      'ready-action-link'        => esc_html(_t('Extras')),
      'ready-big-button'         => esc_html(_t('View your website')),
      'ready-link-1'             => sprintf('<a href="%1$s" target="_blank">%2$s</a>', 'https://wordpress.org/support/', esc_html(_t('Explore WordPress'))),
      'ready-link-2'             => '',
      'ready-link-3'             => '',
    ];

    // Setup environment before MerlinWP init
    list($configs, $strings) = self::setupBeforeEnvironmentForMerlinWp($configs, $strings);

    // Load default setting
    self::$merlinConfigs = array_merge($defaultConfigs, $configs ?? []);
    self::$merlinStrings = array_merge($defaultStrings, $strings ?? []);

    //bdump([$configs, $strings], 'MerlinWP');

    // Init MerlinWP
    $oMerlin = new Merlin(self::$merlinConfigs, self::$merlinStrings);

    // Setup environment after MerlinWP init
    $oMerlin = self::setupAfterEnvironmentForMerlinWp($oMerlin);

    return $oMerlin;
  }

  /**
   * Fake variable of class WP Theme
   *
   * @return void
   */
  public static function fakeWpTheme() {
    $oWpTheme = new Class {
      /**
       * Put all fake data here
       */
      public function __construct() {
        $pluginData       = ModuleCore::$pluginData;
        $this->theme_root = get_theme_root();
        $this->stylesheet = $pluginData['TextDomain'];
        $this->template   = $pluginData['TextDomain'];
        $this->name       = $pluginData['Name'];
        $this->author     = $pluginData['Author'];
        $this->version    = $pluginData['Version'];
      }

      /**
       * Return a plugin name has translated
       *
       * @return string
       */
      public function __toString() {
        return (string) _t($this->name);
      }
    };

    return $oWpTheme;
  }

  /**
   * Check Merlin has setup completed or not
   *
   * @return boolean
   */
  public static function isAlreadySetup(): bool {
    if (self::$merlinConfigs['dev_mode'] === true) {
      return false;
    }

    $slug = strtolower(preg_replace('#[^a-zA-Z]#', '', ModuleCore::$pluginData['TextDomain']));
    return boolval(get_option('merlin_' . $slug . '_completed', false));
  }

  /**
   * Reset Merlin
   *
   * @return void
   */
  public static function reset(): void {
    $slug = strtolower(preg_replace('#[^a-zA-Z]#', '', ModuleCore::$pluginData['TextDomain']));
    update_option('merlin_' . $slug . '_completed', '');
  }

  /**
   * Setup environment before MerlinWP init
   *
   * @param array $configs The Merlin configs
   * @param array $strings The Merlin strings
   *
   * @return array
   */
  private static function setupBeforeEnvironmentForMerlinWp(array $configs, array $strings): array{
    // Include Merlin Autoload
    include_once TCPF_WP_PATH_INCLUDES . '/merlin/vendor/autoload.php';

    // MerlinWP use get_parent_theme_file_path to get current path, but for theme, change it for plugin
    ModuleEvent::on('parent_theme_file_path', [self::class, 'changeParentThemeFilePath'], 99, 2);

    // MerlinWP use get_parent_theme_file_uri to get current url, but for theme, change it for plugin
    ModuleEvent::on('parent_theme_file_uri', [self::class, 'changeThemeFileUri'], 99, 2);

    // Change the configs names that match the plugin
    $configs['edd_remote_api_url'] = $configs['remote_api_url'];
    unset($configs['remote_api_url'], $configs['edd_theme_slug']);

    return [$configs, $strings];
  }

  /**
   * Setup environment after MerlinWP init
   *
   * @param Merlin $oMerlin The MerlinWP class
   *
   * @return Merlin
   */
  private static function setupAfterEnvironmentForMerlinWp(Merlin $oMerlin): Merlin {
    // Remove unused events with plugin settings
    ModuleEvent::off('admin_init', [$oMerlin, 'redirect'], 30);
    ModuleEvent::off('after_switch_theme', [$oMerlin, 'switch_theme']);
    ModuleEvent::off('wp_ajax_merlin_child_theme', [$oMerlin, 'generate_child']);
    ModuleEvent::off('admin_menu', [$oMerlin, 'add_admin_menu']);
    ModuleEvent::off('admin_init', [$oMerlin, 'admin_page'], 30, 0);

    return $oMerlin;
  }

  /**
   * Change parent theme file path
   *
   * MerlinWP use get_parent_theme_file_path to get current path, but for theme, change it for plugin
   *
   * @param string $path The file path.
   * @param string $file The requested file to search for.
   *
   * @return void
   */
  public static function changeParentThemeFilePath(string $path, string $file) {
    if (Strings::startsWith($file, 'merlin') === true) {
      $path = realpath(TCPF_WP_PATH_INCLUDES . $file);
      //bdump([$path, $file], 'changeParentThemeFilePath');
    }

    return $path;
  }

  /**
   * Change theme file uri
   *
   * @param string $url  URL to Merlin directory.
   * @param string $file The requested file to search for.
   *
   * @return void
   */
  public static function changeThemeFileUri(string $url, string $file) {
    if (Strings::contains($url, 'merlin') === true) {
      $url = ModuleHelper::pathToUrl(TCPF_WP_PATH_INCLUDES) . $file;
      //bdump([$url, $file], 'changeParentThemeFilePath');
    }

    return $url;
  }
}