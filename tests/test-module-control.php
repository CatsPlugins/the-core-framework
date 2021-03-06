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
use CatsPlugins\TheCore\ModuleControl;

if (!include __DIR__ . '/../vendor/autoload.php') {
  echo 'Install The Core Framework using `composer install`';
  exit(1);
}

ModuleControl::handleEventActivatePlugin();

ModuleControl::handleEventDeactivatePlugin();