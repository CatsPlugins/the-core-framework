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
 *
 * Better Comments (aaron-bond.better-comments):
 * ! Alerts
 * ? Queries
 * * Highlights
 * TODO: write the next code
 * //// This is an old comment
 */

namespace CatsPlugins\TheCore;

use Nette\Loaders\RobotLoader;

// Blocking called multiple plugin core
defined('TCF_PATH_BASE') and die('Multiple plugin core loaded!');

// Boolean clear
defined('ENABLE') or define('ENABLE', true);
defined('DISABLE') or define('DISABLE', false);

// DIRECTORY_SEPARATOR
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Initialization plugin path
define('TCF_PATH_BASE', realpath(__DIR__) . DS);
define('TCF_PATH_TEMP', TCF_PATH_BASE . 'temp' . DS);
define('TCF_PATH_MODULES', TCF_PATH_BASE . 'modules' . DS);
define('TCF_PATH_TEMPLATES', TCF_PATH_BASE . 'templates' . DS);

// Autoload all module files in TCF_PATH_MODULES
$TCF_Loader = new RobotLoader;
$TCF_Loader->addDirectory(TCF_PATH_MODULES);
$TCF_Loader->setTempDirectory(TCF_PATH_TEMP);
$TCF_Loader->setAutoRefresh(true);
$TCF_Loader->register();