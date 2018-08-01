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
 *
 * Better Comments (aaron-bond.better-comments):
 * ! Alerts
 * ? Queries
 * * Highlights
 * TODO: todo
 * //// This is an old comment
 */

declare (strict_types = 1);

use Nette\Loaders\RobotLoader;

namespace CatsPlugins\TheCore;

// Blocking access direct to the plugin
defined('ABSPATH') or die('No script kiddies please!');

// Blocking called direct to the plugin
defined('WPINC') or die('No script kiddies please!');

// Boolean clear
defined('ENABLE') or define('ENABLE', true);
defined('DISABLE') or define('DISABLE', false);

// DIRECTORY_SEPARATOR
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Initialization plugin path
define('TCF_PATH_BASE', realpath(__FILE__) . DS);
define('TCF_PATH_MODULES', TCF_PATH_BASE . 'modules' . DS);
define('TCF_PATH_TEMPLATES', TCF_PATH_BASE . 'templates' . DS);

// Autoload all module file in TCF_PATH_MODULES
$TCF_Loader = new RobotLoader;
$TCF_Loader->addDirectory(TCF_PATH_MODULES);
$TCF_Loader->setAutoRefresh(true);
$TCF_Loader->register();