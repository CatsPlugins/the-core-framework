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

use CatsPlugins\TheCore\ModuleHelper;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Json;

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

  // Option mode format
  private const READ  = 1;
  private const WRITE = 2;

  public const ADD = self::class . '::add';

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
    return function (array $configFiles) use ($name) {
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
  private static function getConfigValue(string $name, array $configFiles, callable $readFile, callable $decodeContent): array{
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
   * @param string $configName Name of neon file
   * @param array  $arguments  An option_key and option_value for update WordPress option
   *
   * @return mixed
   */
  public static function __callStatic(string $configName, array $arguments): mixed {
    $optionKey   = $arguments[0] ?? null;
    $optionValue = $arguments[1] ?? null;

    // Special mode for WP Option
    if ($configName === 'Option' && $optionKey !== null && $optionKey !== 'raw') {

      // Data update mode
      if ($optionValue !== null) {
        return self::setOption($optionKey, $optionValue);
      }
    }

    // Only read configuration file in first time
    if (!isset(self::$config[$name])) {

      // If config name is an Option WP
      if ($configName === 'Option' && $optionKey !== 'raw') {
        self::$config[$name] = self::getOptions();
      } else {
        // Get config
        self::$config[$name] = self::getConfigValue($name, self::$config, [FileSystem::class, 'read'], [Neon::class, 'decode']);
      }

      // Convert to object type
      self::$config[$name] = ModuleHelper::arrayToObject(self::$config[$name]);
    }

    // Mode get option structure value by name
    if ($configName === 'Option' && $optionValue === null) {
      return self::getOptionsStructureValue($optionKey);
    }

    return self::$config[$name];
  }

  /**
   * Get all option structure value
   *
   * @param string $structName Struct name of option
   *
   * @return array
   */
  private static function getOptionsStructureValue(string $structName): array{
    $configOption      = self::Option('raw');
    $optionStructValue = [];

    foreach ($configOption as $optionKey => $optionStruct) {
      $optionStructValue[$optionKey] = $optionStruct[$structName] ?? null;
    }

    return $optionStructValue;
  }

  /**
   * Get all option managed
   *
   * @return array
   */
  private static function getOptions(): array{
    // Load all wp option
    $wpOptions = wp_load_alloptions();

    // Load all option managed
    $allOptionManaged = array_intersect_key($wpOptions, self::Option('raw'));

    return $allOptionManaged;
  }

  /**
   * Get WP Option with data strict type
   *
   * @param string  $key      WP Option key
   * @param boolean $forceRaw Force get option with raw data
   *
   * @return mixed
   */
  private static function getOption(string $key, bool $forceRaw = null): mixed {
    if (empty($key)) {
      return null;
    }

    // Get Option config with raw data
    $configOption = self::Option('raw')->$key;
    $type         = $configOption['type'] ?? null;

    // Get current value or default value
    $value = get_option($key, $configOption['default']);

    // Returns raw data if a data type is not set, just like the WP Option is not managed by this plugin
    if ($forceRaw === true || $type === null) {
      return $value;
    }

    return self::formatOptionValue($value, $type, self::READ);
  }

  /**
   * Set WP Option whether or not data strict type
   *
   * @param string $key   WP Option key
   * @param mixed  $value New value
   *
   * @return boolean
   */
  private static function setOption(string $key, mixed $value): bool {
    // Get Option config with raw data
    $configOption = self::Option('raw')->$key;
    $type         = $configOption['type'] ?? null;

    $value = self::formatOptionValue($value, $type, self::WRITE);

    return update_option($key, $value);
  }

  /**
   * Formated option value by type has been defined
   *
   * @param mixed  $value Raw option value
   * @param string $type  Type of option value
   * @param int    $mode  Format data mode
   *
   * @return mixed
   */
  private static function formatOptionValue(mixed $value, string $type, int $mode): mixed {
    switch ($type) {
      case 'string':
        $value = strval($value);
        break;
      case 'integer':
        $value = intval($value);
        break;
      case 'number':
        $value = floatval($value);
        break;
      case 'boolean':
        $value = boolval($value) ? 1 : 0;
        break;
      case 'array':
        if (is_array($value)) {
          $value = Json::encode($value);
        } else {
          $value = $mode === self::WRITE ? Json::decode($value, Json::FORCE_ARRAY) : [$value];
        }
        break;
    }

    return $value;
  }
}