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
 */

declare (strict_types = 1);

use Nette\Utils\FileSystem;
use CatsPlugins\TheCore\ModuleDebugger;

if (!include __DIR__ . '/../vendor/autoload.php') {
  echo 'Install The Core Framework using `composer install`';
  exit(1);
}

ModuleDebugger::init(false);