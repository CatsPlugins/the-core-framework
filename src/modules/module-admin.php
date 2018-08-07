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
    // Get menus config
    $menusConfig = ModuleConfig::Admin();

    foreach ($menusConfig as $menuConfig) {
      // Create top-level token menu
      self::addMenu($menuConfig);

      // Create submenu
      // TODO: create method addSubMenu
      add_submenu_page('pwp-menus', __('Settings', PWP_TEXTDOMAIN), __('Settings', PWP_TEXTDOMAIN), 'manage_options', 'pwp-menus', [$this, 'parseAdminSetting']);
    }

    // Notification
    ModuleControl::event('admin_notices', [ModuleRender::class, 'showAdminNotification']);

    // Call setup settings
    ModuleControl::event('admin_init', [self::class, 'setupSettings']);
  }

  /**
   * Add a admin menu
   *
   * @param stdClass $menuConfig Menu Configs 
   * 
   * @return void
   */
  public static function addMenu(stdClass $menuConfig):void {
    add_menu_page(
      $menuConfig->title,
      $menuConfig->name,
      $menuConfig->capability,
      $menuConfig->slug,
      $menuConfig->function,
      $menuConfig->icon_url,
      $menuConfig->position
    );
  } 

  private static function setupSettings() {

    // TODO: update old code
    // Register section
    $aSectionSetting = $this->section;
    foreach ($aSectionSetting as $sKey => $aValue) {
      add_settings_section($sKey, $aValue['title'], null, $aValue['page']);
    }

    // Register setting
    $aSetting = $this->option;

    foreach ($aSetting as $sKey => $aValue) {
      register_setting(PWP_TEXTDOMAIN . '-settings-group', $sKey, [
        'type'    => $aValue['type'] ?? null,
        'default' => $aValue['value'] ?? null,
      ]);

      // Check elements, if ignored it will use set get set
      if (empty($aValue['elements'])) {
        continue;
      }

      // Show setting to HTML
      $sTitle   = !empty($aValue['title']) ? $aValue['title'] : '';
      $sSection = $aValue['section'];

      $aElements = $aValue['elements'];

      $aElements['name']  = $sKey;
      $aElements['value'] = $this->getOption($sKey);

      $sPage = $aSectionSetting[$sSection]['page'];

      add_settings_field($sKey, $sTitle, [$this->PWP->render, 'generateElementHTML'], $sPage, $sSection, $aElements);
    }
  }
}