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

if (!include __DIR__ . '/../vendor/autoload.php') {
  echo 'Install The Core Framework using `composer install`';
  exit(1);
}

// Init
ModuleCache::init(tmpfile());

ModuleCache::setKeyCache('abc');

$keyCache = ModuleCache::getKeyCache();

ModuleCache::$api->save($keyCache, [1, 2, 3]);

$myCached = ModuleCache::$api->load($keyCache);

bdump($myCached);