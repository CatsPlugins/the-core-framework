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
defined('TCPF_WP_PATH_BASE') and die('Multiple plugin core loaded!');

// Boolean clear
defined('ENABLE') or define('ENABLE', true);
defined('DISABLE') or define('DISABLE', false);

// DIRECTORY_SEPARATOR
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Initialization plugin path
define('TCPF_WP_PATH_BASE', realpath(__DIR__) . DS);
define('TCPF_WP_PATH_TEMP', TCPF_WP_PATH_BASE . 'temp' . DS);
define('TCPF_WP_PATH_MODULES', TCPF_WP_PATH_BASE . 'modules' . DS);
define('TCPF_WP_PATH_INCLUDES', TCPF_WP_PATH_BASE . 'includes' . DS);
define('TCPF_WP_PATH_TEMPLATES', TCPF_WP_PATH_BASE . 'templates' . DS);
define('TCPF_WP_PATH_TEMPLATES_ASSETS', TCPF_WP_PATH_TEMPLATES . 'assets' . DS);

define(
  'TCPF_WP_PATH_TEMPLATES_COMPONENTS',
  [
    'page'    => TCPF_WP_PATH_TEMPLATES . 'components' . DS . 'pages' . DS,
    'element' => TCPF_WP_PATH_TEMPLATES . 'components' . DS . 'elements' . DS,
  ]
);

// Autoload all module files in TCPF_WP_PATH_MODULES
$TCPF_WP_Loader = new RobotLoader;
$TCPF_WP_Loader->addDirectory(TCPF_WP_PATH_MODULES);
$TCPF_WP_Loader->addDirectory(TCPF_WP_PATH_INCLUDES);
$TCPF_WP_Loader->setTempDirectory(TCPF_WP_PATH_TEMP);
$TCPF_WP_Loader->setAutoRefresh(true);
$TCPF_WP_Loader->register();

require_once TCPF_WP_PATH_BASE . 'shortcuts.php';
