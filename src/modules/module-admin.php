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

use Nette\InvalidArgumentException;
use Nette\Utils\Callback;
use \stdClass;

// Blocking access direct to the plugin
defined('TCPF_WP_PATH_BASE') or die('No script kiddies please!');

/**
 * The Module Admin
 *
 * Automatically create admin page
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleAdmin
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleAdmin {
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
   * Init Module Admin
   *
   * @return void
   */
  public static function init(): void {
    if (is_admin()) {
      // Create admin menu
      ModuleEvent::on('admin_menu', [self::class, 'createMenus']);

      // Setup admin assets
      self::setupAssets();
    }
  }

  /**
   * Create menus form event admin_menu
   *
   * @return void
   */
  public static function createMenus() {
    // Get menus configuration
    $menusConfig = ModuleConfig::Admin()->MENUS;

    // Create top-level token menu
    self::addMenus($menusConfig);

    // Notification
    ModuleEvent::on('admin_notices', [ModuleRender::class, 'sendAdminNotification']);

    // Setup admin pages
    ModuleEvent::on('admin_init', [self::class, 'setupPages']);
  }

  /**
   * Adds multiple admin menus
   *
   * @param stdClass $menusConfig Menus Config
   *
   * @return void
   */
  public static function addMenus(stdClass $menusConfig): void {
    foreach ($menusConfig as $menuConfig) {
      self::addMenu($menuConfig);

      // Create submenu
      if ($menuConfig->sub_menu) {
        self::addSubMenus($menuConfig->slug, $menuConfig->sub_menu);
      }
    }
  }
  /**
   * Add a admin menu
   *
   * @param stdClass $menuConfig Menu Config
   *
   * @return void
   */
  public static function addMenu(stdClass $menuConfig): void {
    // Auto fix function callback format
    list($callable, $args) = ModuleHelper::fixCallback($menuConfig->callback);

    // Check callback hash parameter
    if (!empty($args)) {
      $menuConfig = self::addCallbackWithParameter($menuConfig, $callable, $args);
    }

    //bdump($menuConfig, 'addMenu');

    // Check callable add_menu_page
    if (!function_exists('add_menu_page')) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    add_menu_page(
      $menuConfig->title,
      $menuConfig->name,
      $menuConfig->capability,
      $menuConfig->slug,
      $menuConfig->callback,
      $menuConfig->icon_url,
      $menuConfig->position
    );
  }

  /**
   * Adds multiple admin sub menus
   *
   * @param string   $slug        The slug name for the parent menu
   * @param stdClass $menusConfig Sub menus configuration
   *
   * @return void
   */
  public static function addSubMenus(string $slug, stdClass $menusConfig): void {
    foreach ($menusConfig as $menuConfig) {
      self::addSubMenu($slug, $menuConfig);
    }
  }

  /**
   * Add a admin sub menu
   *
   * @param string   $slug       The slug name for the parent menu
   * @param stdClass $menuConfig Menu Config
   *
   * @return void
   */
  public static function addSubMenu(string $slug, stdClass $menuConfig): void {
    // Auto fix function callback format
    list($callable, $args) = ModuleHelper::fixCallback($menuConfig->callback);

    // Check callback hash parameter
    if (!empty($args)) {
      $menuConfig = self::addCallbackWithParameter($menuConfig, $callable, $args);
    }

    //bdump($menuConfig, 'addSubMenu');

    add_submenu_page(
      $slug,
      $menuConfig->title,
      $menuConfig->name,
      $menuConfig->capability,
      $menuConfig->slug,
      $menuConfig->callback
    );
  }

  /**
   * Add menu with callback hash parameter
   *
   * @param stdClass $menuConfig Menu Config
   * @param any      $callable   Callable
   * @param any      $args       Args of callback
   *
   * @return stdClass
   */
  private static function addCallbackWithParameter(stdClass $menuConfig, $callable, $args): stdClass {
    global $admin_page_hooks;

    // Check callable get_plugin_page_hookname
    if (!function_exists('get_plugin_page_hookname')) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Get hook of this menu
    $slug                    = plugin_basename($menuConfig->slug);
    $admin_page_hooks[$slug] = sanitize_title($menuConfig->title);
    $hookName                = get_plugin_page_hookname($slug, '');
    $hasCapability           = ModuleHelper::currentUserHave($menuConfig->capability);

    // Add event with parameter
    if (!empty($hookName) && !empty($callable) && $hasCapability) {
      $args = is_array($args) ? $args : [$args];

      //bdump([$hookName, $callable, $args], 'addCallbackWithParameter');
      
      ModuleEvent::on($hookName, $callable, 10, 1, $args);
      $menuConfig->callback = null;
    }

    return $menuConfig;
  }

  /**
   * Setup pages by configuration
   *
   * @return void
   */
  public static function setupPages(): void {
    $pagesConfig = ModuleConfig::Admin()->PAGES;

    foreach ($pagesConfig as $pageId => $pageConfig) {
      // Skip page is not page setting
      if (!isset($pageConfig->sections)) {
        continue;
      }

      $finalPageId = ModuleCore::$textDomain . '_' . $pageId;

      self::addSettingsSections($finalPageId, $pageConfig->sections);
    }
  }

  /**
   * Register assets files of pages
   *
   * @return void
   */
  public static function setupAssets(): void {
    $pagesConfig = ModuleConfig::Admin()->PAGES;

    foreach ($pagesConfig as $pageConfig) {
      // Setup assets files
      if (isset($pageConfig->assets)) {
        ModuleControl::registerAssetsFiles($pageConfig->assets);
      }

      // Setup js variable config
      if (isset($pageConfig->jsData)) {
        ModuleControl::provideDataJs($pageConfig->jsData);
      }

      // Setup ajax config
      if (isset($pageConfig->ajax)) {
        ModuleRequest::setupMultipleAjax($pageConfig->ajax);
      }
    }
  }

  /**
   * Adds multiple settings sections
   *
   * @param string   $pageId         The page ID
   * @param stdClass $sectionsConfig Sections Config
   *
   * @return void
   */
  public static function addSettingsSections(string $pageId, stdClass $sectionsConfig): void {
    foreach ($sectionsConfig as $sectionId => $sectionConfig) {
      // Add a settings section
      self::addSettingsSection($sectionId, $sectionConfig);

      // Add settings for this section
      self::addSettings($pageId, $sectionId, $sectionConfig);
    }
  }

  /**
   * Add a settings section
   *
   * @param string   $sectionId     The section id name
   * @param stdClass $sectionConfig Section Config
   *
   * @return void
   */
  public static function addSettingsSection(string $sectionId, stdClass $sectionConfig): void {
    $sectionConfig->callback = ModuleHelper::fixCallback($sectionConfig->callback);
    //bdump([$sectionId, $sectionConfig->title, $sectionConfig->callback ?? null, $sectionConfig->tab], 'addSettingsSection');
    add_settings_section($sectionId, $sectionConfig->section_title, $sectionConfig->callback ?? null, $sectionConfig->tab);
  }

  /**
   * Add a setting
   *
   * @param string   $pageId        The page ID
   * @param string   $sectionId     The section id name
   * @param stdClass $sectionConfig Section Config
   *
   * @return void
   */
  public static function addSettings(string $pageId, string $sectionId, stdClass $sectionConfig): void {
    foreach ($sectionConfig->options as $optionId => $optionElements) {
      // Register a setting
      $optionStruct = ModuleConfig::Option('raw')->$optionId;

      self::registerSetting($pageId, $optionId, $optionStruct);

      // Add a setting field
      self::addSettingsField($optionId, $optionElements, $sectionId, $sectionConfig);
    }
  }

  /**
   * Register a setting
   *
   * @param string   $pageId       The page ID
   * @param string   $optionId     Option ID
   * @param stdClass $optionStruct Option struct
   *
   * @return void
   */
  public static function registerSetting(string $pageId, string $optionId, stdClass $optionStruct): void {
    //bdump([$pageId, $optionId, $optionStruct], 'registerSetting');
    register_setting(
      $pageId,
      $optionId,
      $optionStruct
    );
  }

  /**
   * Add a settings field
   *
   * @param string   $optionId       Option ID
   * @param array    $optionElements Option elements configuration
   * @param string   $sectionId      The section id name
   * @param stdClass $sectionConfig  Section Config
   *
   * @return void
   */
  public static function addSettingsField(string $optionId, array $optionElements, string $sectionId, stdClass $sectionConfig): void {
    // Ignore if there is no configuration
    if (empty($optionElements)) {
      return;
    }

    // Add more data field for generate html
    $optionElements['echo']  = true;
    $optionElements['name']  = $optionId;
    $optionElements['value'] = ModuleConfig::Option()->$optionId;

    // Call generateElementHTML to generate html by $optionElements;
    //bdump([$optionId, $sectionConfig->title, [ModuleTemplate::class, 'generateElements'], $sectionConfig->tab, $sectionId, $optionElements], 'addSettingsField');
    add_settings_field($optionId, $sectionConfig->field_title, [ModuleTemplate::class, 'generateElements'], $sectionConfig->tab, $sectionId, $optionElements);
  }
}