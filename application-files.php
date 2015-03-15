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


require_once($baseDir.'/../frameworks/predis/autoload.php');
Predis\Autoloader::register();


spl_autoload_register(function($class) use ($baseDir){

  $fileName = $baseDir.'/../frameworks/Elastica/lib/' . str_replace('\\',DIRECTORY_SEPARATOR,$class) . '.php';
  if (file_exists($fileName)) 
  {
    require_once($fileName);
  }

});



$nowTime = microtime(true);
$duration = $nowTime-$startTime;
header('ZEITFADEN-SPRINT-LOAD-1: '.$duration);


require_once($baseDir.'/../frameworks/Underscore.php/underscore.php');


require_once($baseDir.'/../vendor/autoload.php');


require_once($baseDir.'/../my-frameworks/reverse-geocoder-cache/src/include.php');

require_once($baseDir.'/../my-frameworks/php-visitable-specification/src/php-visitable-specification.php');

$nowTime = microtime(true);
$duration = $nowTime-$startTime;
header('ZEITFADEN-SPRINT-LOAD-1a: '.$duration);

//require_once($baseDir.'/../my-frameworks/simple-parser-base/src/simple-parser-base.php');
//require_once($baseDir.'/../my-frameworks/php-query-language/src/php-query-language.php');

require_once($baseDir.'/../my-frameworks/sugarloaf/lib/sugarloaf.php');
require_once($baseDir.'/../my-frameworks/tiro-php-profiler/src/tiro.php');
require_once($baseDir.'/../my-frameworks/pivole-und-pavoli/src/pivole-und-pavoli.php');
require_once($baseDir.'/../my-frameworks/zeitfaden-base/src/zeitfaden-base.php');


require_once($baseDir.'/configuration/CompositeConfig.php');
require_once($baseDir.'/configuration/CompositeConfigLoader.php');
require_once($baseDir.'/configuration/CompositeDependencyConfigurator.php');

//require_once($baseDir.'/access-control/UserSession.php');

require_once($baseDir.'/CompositeServiceFacade.php');
require_once($baseDir.'/ZeitfadenShardingService.php');

require_once($baseDir.'/controller/AppsController.php');
require_once($baseDir.'/controller/AbstractCompositeEntityController.php');
require_once($baseDir.'/controller/UserController.php');
require_once($baseDir.'/controller/StationController.php');

require_once($baseDir.'/controller/search-strategies/AbstractSearchStrategy.php');
require_once($baseDir.'/controller/search-strategies/ElasticSearchStrategy.php');
require_once($baseDir.'/controller/search-strategies/NativeSearchStrategy.php');

require_once(dirname(__FILE__).'/elastic-search/DataMap.php');
require_once(dirname(__FILE__).'/elastic-search/ElasticSearchQueryArray.php');
require_once(dirname(__FILE__).'/elastic-search/ElasticSearchOrderArray.php');
require_once(dirname(__FILE__).'/elastic-search/ElasticSearchService.php');


$nowTime = microtime(true);
$duration = $nowTime-$startTime;
header('ZEITFADEN-SPRINT-LOAD-2: '.$duration);

