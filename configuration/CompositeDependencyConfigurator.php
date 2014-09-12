<?php
use SugarLoaf as SL;

class CompositeDependencyConfigurator
{
  public function configureDependencies($dm,$application)
  {







      
    $depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('Facebook','Facebook', new SL\ConstantParameterArray(array($application->getFacebookConfig()))));
		
		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('ZeitfadenSystemWrapper'));
				
		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('SqlProfiler','\Tiro\Profiler'));
		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('PhpProfiler','\Tiro\Profiler'));

    //$depList = $dm->registerDependencyManagedService(new SL\ManagedService('\BrokenPottery\DbService'));
    //$depList->addDependency('Profiler', new SL\ManagedComponent('SqlProfiler'));
		
		
    
    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('ElasticSearchQueryArray','\Zeitfaden\ElasticSearch\ElasticSearchQueryArray'));
    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('ElasticSearchSortArray','\Zeitfaden\ElasticSearch\ElasticSearchOrderArray'));
	
    
        
    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('ElasticSearchService','\Zeitfaden\ElasticSearch\ElasticSearchService'));
    $depList->addDependency('Application', new SL\UnmanagedInstance($application));
    $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));
        
    
		$parameterArray = new SL\ParameterArray();
    $parameter = new SL\ManagedParameter('DatabaseProvider');
    $parameterArray->appendParameter($parameter);
		
    $depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('ZeitfadenOAuth2', 'ZeitfadenOAuth2',$parameterArray));

    
            		
		
		
		
		
		
    $mainDataMap = new \Zeitfaden\ElasticSearch\DataMap();
    $mainDataMap->addColumn('id', 'id', 'station');
    $mainDataMap->addColumn('userId', 'userId', 'user');
    $mainDataMap->addColumn('description', 'description', 'station');
    $mainDataMap->addColumn('publishStatus', 'publishStatus', 'station');
    $mainDataMap->addColumn('zuluStartDateString', 'startDate', 'station');
    $mainDataMap->addColumn('startLocation.lat', 'startLatitude', 'station');
    $mainDataMap->addColumn('startLocation.lon', 'startLongitude', 'station');
    $mainDataMap->addColumn('startTimezone', 'startTimezone', 'station');
    $mainDataMap->addColumn('zuluEndDateString', 'endDate', 'station');
    $mainDataMap->addColumn('endLocation.lat', 'endLatitude', 'station');
    $mainDataMap->addColumn('endLocation.lon', 'endLongitude', 'station');
    $mainDataMap->addColumn('endTimezone', 'endTimezone', 'station');
    $mainDataMap->addColumn('startLocation', 'startLocation', 'station');
    $mainDataMap->addColumn('endLocation', 'endLocation', 'station');
    $mainDataMap->addColumn('startDateWithId', 'startDateWithId', 'station');
    $mainDataMap->addColumn('fileType', 'fileType', 'station');
    $mainDataMap->addColumn('userFileType', 'fileType', 'user');
    
    $mainDataMap->addColumn('distanceToPin', 'distanceToPin','station');
				
		
		
		
		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('StationController'));
        $depList->addDependency('CompositeService', new SL\ManagedComponent('CompositeService'));
        $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
        $depList->addDependency('ShardingService', new SL\ManagedComponent('ZeitfadenShardingService'));
        $depList->addDependency('ElasticSearchStrategyProvider', new SL\ManagedComponentProvider('ElasticSearchStrategy'));
        $depList->addDependency('NativeSearchStrategyProvider', new SL\ManagedComponentProvider('NativeSearchStrategy'));
        $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));
		
						

		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('UserController'));
        $depList->addDependency('CompositeService', new SL\ManagedComponent('CompositeService'));
        $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
        $depList->addDependency('ShardingService', new SL\ManagedComponent('ZeitfadenShardingService'));
        $depList->addDependency('Database', new SL\ManagedComponent('DatabaseProvider'));
        $depList->addDependency('ElasticSearchStrategyProvider', new SL\ManagedComponentProvider('ElasticSearchStrategy'));
        $depList->addDependency('NativeSearchStrategyProvider', new SL\ManagedComponentProvider('NativeSearchStrategy'));
		    $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));
		
		

		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('ElasticSearchStrategy'));
        $depList->addDependency('ElasticSearchService', new SL\ManagedComponent('ElasticSearchService'));
        $depList->addDependency('ElasticSearchQueryArrayProvider', new SL\ManagedComponentProvider('ElasticSearchQueryArray'));
        $depList->addDependency('ElasticSearchSortArrayProvider', new SL\ManagedComponentProvider('ElasticSearchSortArray'));
	      $depList->addDependency('ElasticSearchStationDataMap', new SL\UnmanagedInstance($mainDataMap));
        $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));



		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('NativeSearchStrategy'));
    $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));


    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('ZeitfadenSimpleShard'));
    
    
    $depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('ZeitfadenShardingService','ZeitfadenShardingService'));
    $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
    $depList->addDependency('ShardProvider', new SL\ManagedComponentProvider('ZeitfadenSimpleShard'));




    // at this point again the problem, that the unmanaged instances

    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('CompositeService','CompositeServiceFacade'));
    $depList->addDependency('Config', new SL\UnmanagedValue($application->getConfig()));
    $depList->addDependency('ApplicationIni', new SL\UnmanagedValue($application->getApplicationIni()));
    $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
    $depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));
    $depList->addDependency('ShardingService', new SL\ManagedComponent('ZeitfadenShardingService'));
    
    
        


    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('UserSessionRecognizer','UserSessionRecognizer'));
    $depList->addDependency('OAuth2Service', new SL\ManagedComponent('ZeitfadenOAuth2'));
    $depList->addDependency('Facebook', new SL\ManagedComponent('Facebook'));



    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('UserSession'));
    $depList->addDependency('CompositeService', new SL\ManagedComponent('CompositeService'));

    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('DatabaseProvider','\PivoleUndPavoli\DatabaseProvider'));
    $depList->addDependency('Profiler', new SL\ManagedComponent('SqlProfiler'));
    $depList->addDependency('MongoDbConfig', new SL\UnmanagedInstance($application->getConfig()->getMongoDbConfig()));
  



    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('OAuth2Controller'));
    $depList->addDependency('OAuth2Service', new SL\ManagedComponent('ZeitfadenOAuth2'));



  }


}

