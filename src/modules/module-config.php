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

use CatsPlugins\TheCore\ModuleHelper;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use \stdClass;

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
   * @return bool
   */
  public static function add(...$configPaths): bool {
    $findFiles = Finder::findFiles('*.neon')->in(...$configPaths);
    if ($findFiles->count() > 0) {
      self::$configFiles = self::makeConfigFiles($findFiles);
      return true;
    }

    return false;
  }

  /**
   * Make files by finder result to array type
   *
   * @param Finder $findFiles Result of Finder
   *
   * @return array
   */
  private static function makeConfigFiles(Finder $findFiles): array{
    $configFiles = [];
    foreach ($findFiles as $file) {
      $configFiles[$file->getBasename('.neon')] = $file->getPathname();
    }

    //bdump($configFiles, 'makeConfigFiles');
    return $configFiles;
  }

  /**
   * Get config form file path
   *
   * @param array $configFiles List path of file neon
   *
   * @return callable
   */
  private static function getConfigFormFilePath(array $configFiles): callable {
    return function (string $name) use ($configFiles) {
      return $configFiles[strtolower($name)] ?? '';
    };
  }

  /**
   * Get content neon config file
   *
   * @param string $name Name of config file
   *
   * @return array
   */
  private static function getConfigValue(string $name): array{
    $config = [];

    $currentPath = self::getConfigFormFilePath(self::$configFiles);
    $filePath    = $currentPath($name);

    if (empty($filePath)) {
      return ['error' => "Config $name not found"];
    }

    $fileContent = FileSystem::read($filePath);

    // Returns the constant value from the variable neon
    $fileContent = self::returnValueConstants($fileContent);

    // Returns the variable from the variable neon
    $fileContent = self::returnValueVariable($fileContent);

    try {
      $config = Neon::decode($fileContent);
    } catch (Exception $e) {
      // ! remove $e when release;
      $config = [];
      bdump($e, 'getConfigValue');
    }

    //bdump($config, 'getConfigValue: ' . $name);
    return $config;
  }

  /**
   * Returns the constant value from the variable neon: %name%
   *
   * @param string $contents The Neon content file
   *
   * @return void
   */
  public static function returnValueConstants(string $contents): string {
    $variables = self::findVariablesConfig($contents);

    foreach ($variables as $name) {
      $value = ModuleHelper::constant($name);
      //bdump($value, 'returnValueConstants');
      $contents = str_replace($name, $value, $contents);
    }

    return $contents;
  }

  /**
   * Returns the variable value from the variable neon: %class/variable%
   *
   * @param string $contents The Neon content file
   *
   * @return void
   */
  public static function returnValueVariable(string $contents): string {
    $variables = self::findVariablesConfig($contents);

    foreach ($variables as $name) {
      $variable = Strings::substring($name, 1, -1);
      $value    = ModuleHelper::getVariableFormString($variable);
      //bdump($value, 'returnValueVariable');

      // TODO: Need test
      if (is_array($value)) {
        $value = Json::encode($value);
        $name  = '"' . $name . '"';
      }

      $contents = str_replace($name, $value, $contents);
    }

    return $contents;
  }

  /**
   * Returns the value from filter with the variable neon: %@[name]%
   *
   * @param string $event    The event name of filter
   * @param string $contents The Neon content file
   *
   * @return string
   */
  public static function returnValueFilter(string $event, string $contents): string {
    // Get data from a filter
    $filterData = ModuleEvent::filter($event);

    if (empty($filterData)) {
      return $contents;
    }

    // Replace value define by %@[name]%
    $prefix = '%@';
    foreach ($filterData as $name => $value) {
      $value  = Validators::isNumeric($value) ? floatval($value) : $value;
      $search = $prefix . $name . '%';

      if (is_array($value)) {
        $value  = Json::encode($value);
        $search = '"' . $search . '"';
      }

      $contents = str_replace($search, $value, $contents);

    }
    return $contents;
  }

  /**
   * Find variables in content neon config
   *
   * @param string $contents The Neon content file
   *
   * @return array
   */
  public static function findVariablesConfig(string $contents): array{
    $variables = Strings::matchAll($contents, '/\%[A-Z_ :->\\\$]+\%/im');

    if (is_null($variables)) {
      return [];
    }

    $variables = Arrays::flatten($variables);

    $variables = array_unique($variables);

    //bdump($variables, 'findVariablesConfig');

    return $variables;
  }

  /**
   * Get and set config file
   *
   * @param string $configName Name of neon file
   * @param array  $arguments  An option_key and option_value for update WordPress option
   *
   * @return mixed
   */
  public static function __callStatic(string $configName, array $arguments) {
    $name        = strtolower($configName);
    $optionKey   = $arguments[0] ?? null;
    $optionValue = $arguments[1] ?? null;

    // Special mode for set WP Option
    if ($name === 'option' && $optionKey !== null && $optionKey !== 'raw' && $optionValue !== null) {
      return self::setOption($optionKey, $optionValue);
    }

    // Only read configuration file in first time
    // * Speed up!
    if (!isset(self::$config[$name])) {
      // Get config
      self::$config[$name] = self::getConfigValue($name);

      // Convert to object type
      self::$config[$name] = ModuleHelper::arrayToObject(self::$config[$name]);
    }

    // Get all wp options with value formated
    if ($name === 'option' && $optionKey === 'all') {
      return self::getOptions(true);
    }

    // Mode get option structure value by name
    if ($name === 'option' && $optionValue === null && $optionKey !== 'raw' && $optionKey !== null) {
      return self::getOptionsStructureValue($optionKey);
    }

    // If config name is an Option WP
    if ($name === 'option' && $optionKey === null) {
      return self::getOptions();
    }

    return self::$config[$name];
  }

  /**
   * Get all option structure value
   *
   * @param string $structName Struct name of option
   *
   * @return stdClass
   */
  private static function getOptionsStructureValue(string $structName): stdClass {
    $configOption      = self::Option('raw');
    $optionStructValue = new stdClass;

    foreach ($configOption as $optionKey => $optionStruct) {
      $optionStructValue->$optionKey = $optionStruct->$structName;
    }

    return $optionStructValue;
  }

  /**
   * Get all option managed with value formated
   *
   * @param bool $all Get all wp options
   *
   * @return stdClass
   */
  private static function getOptions(bool $all = null): stdClass {
    if ($all !== true) {
      // Get default value options
      $optionsManaged = ModuleHelper::objectToArray(self::Option('default'));
    } else {
      // Load all wp option
      $optionsManaged = wp_load_alloptions();
    }

    // Formated all option managed if not correct
    array_walk(
      $optionsManaged,
      function (&$value, $key) {
        $value = self::getOption($key);
      }
    );

    //bdump($optionsManaged, 'getOptions');
    return ModuleHelper::arrayToObject($optionsManaged);
  }

  /**
   * Get WP Option with data strict type
   *
   * @param string  $key      WP Option key
   * @param boolean $forceRaw Force get option with raw data
   *
   * @return mixed
   */
  private static function getOption(string $key, bool $forceRaw = null) {
    if (empty($key)) {
      return null;
    }

    // Get Option config with raw data
    $configOption = self::Option('raw')->$key;
    $type         = $configOption->type ?? '';

    // Get current value or default value
    $value = get_option($key, $configOption->default ?? false);

    // Returns raw data if a data type is not set, just like the WP Option is not managed by this plugin
    if ($forceRaw === true) {
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
  private static function setOption(string $key, $value): bool {
    // Get type Option
    $type = self::Option('type')->$key ?? '';

    $valueFormated = self::formatOptionValue($value, $type, self::WRITE);

    return update_option($key, $valueFormated);
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
  private static function formatOptionValue($value, string $type, int $mode) {
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
      if ($mode === self::READ) {
        if (is_string($value)) {
          try {
            $value = Json::decode($value, Json::FORCE_ARRAY);
          } catch (JsonException $e) {
            $value = ['error' => $e->getMessage()];
          }
        }
        $value = (is_array($value) || is_object($value)) ? $value : [$value];
      } elseif ($mode === self::WRITE) {
        $value = (is_array($value) || is_object($value)) ? $value : [$value];
        $value = Json::encode($value);
      }
      break;
    }

    return $value;
  }
}