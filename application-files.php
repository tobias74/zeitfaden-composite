<?php 
error_reporting(E_ALL);

function exception_error_handler($errno, $errstr, $errfile, $errline ) 
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

date_default_timezone_set('Europe/Berlin');



$baseDir = dirname(__FILE__);

require_once($baseDir.'/../frameworks/oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

require_once($baseDir.'/../frameworks/facebook/src/facebook.php');
//require_once($baseDir.'/../frameworks/predis/autoload.php');
//Predis\Autoloader::register();

//neo4jphp
/*
spl_autoload_register(function ($sClass) use ($baseDir) {
  $sLibPath = $baseDir.'/../frameworks/neo4jphp/lib/';
  $sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
  $sClassPath = $sLibPath.$sClassFile;
  if (file_exists($sClassPath)) {
    require($sClassPath);
  }
});
*/


//thrift
//require_once($baseDir.'/../frameworks/thrift/php/src/Thrift.php');

/*
spl_autoload_register(function ($sClass) use ($baseDir) {
  $sLibPath = $baseDir.'/../frameworks/thrift/php/lib/';
  $sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
  $sClassPath = $sLibPath.$sClassFile;
  if (file_exists($sClassPath)) {
    require($sClassPath);
  }
});
*/




require_once($baseDir.'/../my-frameworks/sugarloaf/lib/sugarloaf.php');
require_once($baseDir.'/../my-frameworks/tiro-php-profiler/src/tiro.php');
require_once($baseDir.'/../my-frameworks/pivole-und-pavoli/src/pivole-und-pavoli.php');
require_once($baseDir.'/../my-frameworks/zeitfaden-base/src/zeitfaden-base.php');


require_once($baseDir.'/configuration/CompositeConfig.php');
require_once($baseDir.'/configuration/CompositeConfigLoader.php');
require_once($baseDir.'/configuration/CompositeDependencyConfigurator.php');

//require_once($baseDir.'/access-control/UserSession.php');

require_once($baseDir.'/CompositeServiceFacade.php');

require_once($baseDir.'/controller/AbstractCompositeController.php');
require_once($baseDir.'/controller/UserController.php');
require_once($baseDir.'/controller/StationController.php');
require_once($baseDir.'/controller/GroupController.php');


