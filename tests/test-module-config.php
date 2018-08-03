<?php
/**
 * The Plugin Core Framework Testing
 *
 * PHP Version 7
 *
 * @category Framework
 * @package  CatsPlugins\TheCore
 * @author   Won <won.baria@email.com>
 * @license  GPLv2 https://www.gnu.org
 * @link     https://catsplugins.com
 *
 * Better Comments (aaron-bond.better-comments):
 * ! Alerts
 * ? Queries
 * * Highlights
 * TODO: todo
 * //// This is an old comment
 */

declare (strict_types = 1);

use Nette\Utils\FileSystem;
use CatsPlugins\TheCore\ModuleConfig;

if (!include __DIR__ . '/../vendor/autoload.php') {
  echo 'Install The Core Framework using `composer install`';
  exit(1);
}

// Add a special path config
ModuleConfig::add(['core', 'option']);

// Get a config
ModuleConfig::Core();

// Set a wp option
ModuleConfig::Option('MY_TOKEN', '123');

// Get a value wp option by struct name
ModuleConfig::Option('type')->MY_TOKEN;

// Get a raw wp option
ModuleConfig::Option('raw')->MY_TOKEN;