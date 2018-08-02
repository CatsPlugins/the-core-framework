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

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use CatsPlugins\TheCore\ModuleHelper;

/**
 * The Module Config
 *
 * Caching code, api,...
 *
 * @category Framework
 * @package  CatsPlugins\TheCore\ModuleConfig
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 */
final class ModuleConfig {
  private static $configFiles;
  private static $config;
  
  public const add = self::class . '::add';

  /**
   * Add and initialization multiple configuration file
   *
   * @param mixed ...$configPaths List config path
   *
   * @return void
   */
  public static function add(...$configPaths): void {
    $findFiles         = Finder::findFiles('*.neon')->in(...$configPaths);
    self::$configFiles = self::makeConfigFiles($findFiles);
  }

  /**
   * Make files by finder result to array type
   *
   * @param Finder $findFiles Result of Finder
   *
   * @return array
   */
  private static function makeConfigFiles(Finder $findFiles): array{
    return array_flip(
      array_map(
        function ($file) {
          return $file->getBasename('.neon');
        },
        $findFiles
      )
    );
  }

  /**
   * Get config file path
   *
   * @param string $name Name of neon file
   * 
   * @return string
   */
  private static function getConfigFilePath(string $name): string {
    return function(array $configFiles) use($name) {
      return $configFiles[$name];
    };
  }

  /**
   * Get content neon config file
   *
   * @param string   $name          Name of config file
   * @param array    $configFiles   List config file
   * @param callable $readFile      Read content file
   * @param callable $decodeContent Decode content to array
   *
   * @return array
   */
  private static function get(string $name, array $configFiles, callable $readFile, callable $decodeContent): array{
    $getPath     = self::getConfigFilePath($configFiles);
    $fileContent = $readFile($getPath($name));

    try {
      $config = $decodeContent($fileContent);
    } catch (NE $e) {
      // ! remove $e when release;
      $config = [$e];
    }

    return $config;
  }

  /**
   * Get and set config file
   *
   * @param string $name      Name of neon file
   * @param array  $arguments hum.. researching
   *
   * @return stdClass
   */
  public static function __callStatic(string $name, array $arguments): stdClass {
    // Method set value to config file
    if (!empty($arguments)) {
      // ! magic method __get not work with static variant
      // TODO: research!

      // Unset current data config
      //unset(self::$config);
    }

    // Only read configuration file in first time
    if (!isset(self::$config[$name])) {

      // Get config
      self::$config[$name] = self::get($name, self::$config, [FileSystem::class, 'read'], [Neon::class, 'decode']);

      // Convert to object type
      self::$config[$name] = ModuleHelper::arrayToObject(self::$config[$name]);

    }

    return self::$config[$name];
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
}