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
		
		
    
    
		$parameterArray = new SL\ParameterArray();
    $parameter = new SL\ManagedParameter('DatabaseProvider');
    $parameterArray->appendParameter($parameter);
		
    $depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('ZeitfadenOAuth2', 'ZeitfadenOAuth2',$parameterArray));

    
            		
		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('UserController'));
        $depList->addDependency('CompositeService', new SL\ManagedComponent('CompositeService'));
        $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
        $depList->addDependency('ShardingService', new SL\ManagedComponent('ZeitfadenShardingService'));
        $depList->addDependency('Database', new SL\ManagedComponent('DatabaseProvider'));
		//$depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));

		
		$depList = $dm->registerDependencyManagedService(new SL\ManagedService('StationController'));
        $depList->addDependency('CompositeService', new SL\ManagedComponent('CompositeService'));
        $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
        $depList->addDependency('ShardingService', new SL\ManagedComponent('ZeitfadenShardingService'));
		//$depList->addDependency('Profiler', new SL\ManagedComponent('PhpProfiler'));
				



    
    $depList = $dm->registerDependencyManagedService(new SL\ManagedSingleton('ZeitfadenShardingService','ZeitfadenShardingService'));
    $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
    //$depList->addDependency('ShardProvider', new SL\ManagedComponentProvider('Shard'));
    // leave this in! currently we do not need the ShardProvider here, because we are composiote,
    // but it is not nice.




    // at this point again the problem, that the unmanaged instances

    $depList = $dm->registerDependencyManagedService(new SL\ManagedService('CompositeService','CompositeServiceFacade'));
    $depList->addDependency('Config', new SL\UnmanagedValue($application->getConfig()));
    $depList->addDependency('ApplicationIni', new SL\UnmanagedValue($application->getApplicationIni()));
    $depList->addDependency('ApplicationId', new SL\UnmanagedValue($application->getApplicationId()));
        


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

