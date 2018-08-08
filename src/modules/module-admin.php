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

use \stdClass;

// Blocking access direct to the plugin
defined('TCF_PATH_BASE') or die('No script kiddies please!');

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
   * Init Module Admin
   *
   * @return void
   */
  public static function init(): void {
    if (is_admin()) {
      // Create admin menu
      ModuleControl::event('admin_menu', [self::class, 'createMenus']);
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
    ModuleControl::event('admin_notices', [ModuleRender::class, 'sendAdminNotification']);

    // Call setup settings
    ModuleControl::event('admin_init', [self::class, 'setupPages']);
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
    $menuConfig->callback = ModuleHelper::fixCallback($menuConfig->callback);
    bdump($menuConfig, 'addMenu');
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
    $menuConfig->callback = ModuleHelper::fixCallback($menuConfig->callback);
    bdump($menuConfig, 'addSubMenu');
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
   * Setup pages by configuration
   *
   * @return void
   */
  public static function setupPages(): void {
    $pagesConfig = ModuleConfig::Admin()->PAGES;

    foreach ($pagesConfig as $pageConfig) {
      self::addSettingsSections($pageConfig->sections);
    }
  }

  /**
   * Adds multiple settings sections
   *
   * @param stdClass $sectionsConfig Sections Config
   *
   * @return void
   */
  public static function addSettingsSections(stdClass $sectionsConfig): void {
    foreach ($sectionsConfig as $sectionId => $sectionConfig) {
      // Add a settings section
      self::addSettingsSection($sectionId, $sectionConfig);

      // Add settings for this section
      self::addSettings($sectionId, $sectionConfig);
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
    
    add_settings_section($sectionId, $sectionConfig->title, $sectionConfig->callback ?? null, $sectionConfig->tag);
  }

  /**
   * Add a setting
   *
   * @param string   $sectionId     The section id name
   * @param stdClass $sectionConfig Section Config
   *
   * @return void
   */
  public static function addSettings(string $sectionId, stdClass $sectionConfig): void {
    foreach ($sectionConfig->options as $optionId => $optionElements) {
      // Register a setting
      $optionStruct = ModuleConfig::Option('raw')->$optionId;
      
      self::registerSetting($sectionConfig->tab, $optionId, $optionStruct);

      // Add a setting field
      self::addSettingsField($optionId, $optionElements, $sectionId, $sectionConfig);
    }
  }

  /**
   * Register a setting
   *
   * @param string   $tabId        The ID of the tab contains this option
   * @param string   $optionId     Option ID
   * @param stdClass $optionStruct Option struct
   *
   * @return void
   */
  public static function registerSetting(string $tabId, string $optionId, stdClass $optionStruct): void {
    $finalTabId = ModuleCore::$textDomain . '_' . $tabId;
    register_setting(
      $finalTabId,
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
    // Ignore if there is no configurationuration
    if (empty($optionElements)) {
      return;
    }

    // Add more data field for generate html
    $optionElements['name']  = $optionId;
    $optionElements['value'] = ModuleConfig::Option()->$optionId;

    // Call generateElementHTML to generate html by $optionElements;
    add_settings_field($optionId, $sectionConfig->title, [ModuleTemplate::class, 'generateElements'], $sectionConfig->tab, $sectionId, $optionElements);
  }
}