<?php 
use SugarLoaf as SL;

abstract class AbstractCompositeController extends AbstractZeitfadenController
{
  protected $redisClient = false;
  protected $reverseGeocoderCache = false;
  	

  protected function getUniqueId()
  {
      $uid=uniqid();
      $uid.=rand(100000,999999);
      return $uid;
  }

  protected function getBrowserLanguage()
  {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    return $lang;
  }

  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }

  public function setRedisClientProvider($val)
  {
  	$this->redisClientProvider = $val;
  }

  protected function getRedisClient()
  {
  	if (!$this->redisClient)
	{
		$map = array(
			'tcp' => 'tcp',
			'host' => $this->getApplication()->getConfig()->redisHost,
			'port' => 6379
		);
		
		$this->redisClient = $this->redisClientProvider->provide($map);
	}
  	return $this->redisClient;
  }


  public function setReverseGeocoderCacheProvider($val)
  {
    $this->reverseGeocoderCacheProvider = $val;
  }

  protected function getReverseGeocoderCache()
  {
    $prefix = "GEOCODING-lang-".$this->getBrowserLanguage();
    if (!$this->reverseGeocoderCache)
    {
      $this->reverseGeocoderCache = $this->reverseGeocoderCacheProvider->provide($this->getRedisClient(),50,$prefix);
    }
    return $this->reverseGeocoderCache;
  }

  protected function getLocationDescription($latitude,$longitude)
  {
    $timer = $this->getProfiler()->startTimer('getting location description from redis');
    $value = $this->getReverseGeocoderCache()->get($latitude,$longitude);
    $timer->stop();
    if ($value != "")  
    {
      return $value;
    }
    else 
    {
      try
      {
        $description = $this->produceLocationDescription($latitude,$longitude);
        $this->getReverseGeocoderCache()->set($latitude,$longitude,$description);
      }
      catch (\ErrorException $e)
      {
        error_log('no location from google.');
        $description = "";
      }
      
      return $description;
    }
  }


  protected function produceLocationDescription($latitude,$longitude)
  {
    $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=".$latitude.",".$longitude."&language=".$this->getBrowserLanguage()."&sensor=true";
    $dataString = @file_get_contents($url);
    $data = json_decode($dataString,true);
    return $data['results'][0]['formatted_address'];
  }




  final public function getByIdsAction()
  {
    $loadBalancedUrls = $this->_request->getParam('loadBalancedUrls',1);
    $returnEntities = $this->getEntitiesByIds($this->_request);
    if ($loadBalancedUrls)
    {
      $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    }
    $this->_response->setHash(array_values($returnEntities));
  }
  
  public function getAction()
  {
    $loadBalancedUrls = $this->_request->getParam('loadBalancedUrls',1);
    $returnEntities = $this->getEntitiesByRequest($this->_request);
    if ($loadBalancedUrls)
    {
      $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    }
    $this->_response->setHash(array_values($returnEntities));
  }

  public function reverseGeoCodeAction()
  {
    $latitude = $this->_request->getParam('latitude',0);
    $longitude = $this->_request->getParam('longitude',0);
    
    $this->_response->appendValue('description',$this->getLocationDescription($latitude,$longitude));
  }


  protected function getAttachmentUrlByEntityId($entityId)
  {
    error_log('is this called Abstract COmpistie COntroller 2487687682364876876');
    $entityData = $this->getMyEntityDataById($entityId);
    $serveAttachmentUrl = 'http://'.$entityData['shardUrl'].'/'.$this->controllerPath.'/serveAttachment/'.$this->idName.'/'.$entityId;
    return $serveAttachmentUrl;
  }

  
  protected function attachLoadBalancedUrls($returnEntities)
  {
    $frontEndUrls = $this->getCompositeService()->getFrontEndUrls();

    foreach($returnEntities as &$entity)
     {
         if (isset($entity['smallFrontImageUrl']))
         {
           $relativeUrl = $entity['smallFrontImageUrl'];
           $frontEndNumber = (crc32($relativeUrl) % 4);
           $frontEndUrl = $frontEndUrls[$frontEndNumber];
           $entity['smallFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['smallFrontImageUrl'];
           $entity['mediumFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['mediumFrontImageUrl'];
           $entity['bigFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['bigFrontImageUrl'];
         }

     }
    
    return $returnEntities;
  }


  public function getByIdAction()
  {
    try
    {
      $entityData = $this->getEntityDataByRequest($this->_request);
      $returnEntities = array($entityData);
      
      $loadBalancedUrls = $this->_request->getParam('loadBalancedUrls',1);
      if ($loadBalancedUrls)
      {
        $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
      }
      $this->_response->setHash($returnEntities[0]);
    }
    catch (ZeitfadenNoMatchException $e)
    {
      $this->_response->addHeader('HTTP/1.0 404 Not Found');
    }
    
  }
  

  protected function getMyShardUrl()
  {
      $userId = $this->getUserSession()->getLoggedInUserId();
      $homeShard = $this->getCompositeService()->whereLivesUserById($userId);
      return $homeShard['shardUrl'];
  }  	



  protected function passToMyShard()
  {
    if ($this->getUserSession()->isUserLoggedIn())
    {
      $userId = $this->getUserSession()->getLoggedInUserId();
      $homeShard = $this->getCompositeService()->whereLivesUserById($userId);
  
      $shardUrl = $homeShard['shardUrl'];
    }
    else
    {
      throw new \ErrorException('this is anonymous and cant be done.');
    }

    $this->passCurrentRequestToShardUrl($shardUrl);
  }



  protected function passCurrentRequestToShardUrl($shardUrl)
  {
    session_write_close();

    $methods = array(
      'POST' => HttpRequest::METH_POST,
      'GET' =>HttpRequest::METH_GET,
      'PUT' => HttpRequest::METH_PUT,
      'DELETE' => HttpRequest::METH_DELETE
    );

    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    $request = new HttpRequest('http://'.$shardUrl.$requestUri, $methods[$requestMethod]);

    switch ($requestMethod)
    {
      case 'POST':
        //error_log('passing along a post');
        //error_log(print_r($_POST,true));
        $request->setPostFields($_POST);
        foreach ($_FILES as $paramName => $file)
        {
          $request->addPostFile($paramName, $file['tmp_name'], $file['type']);
        }
        break;

      case 'PUT':
        $request->setPutFields($_PUT);
        foreach ($_FILES as $paramName => $file)
        {
          $request->addPutFile($paramName, $file['tmp_name'], $file['type']);
        }
        break;


    }


    $request->addCookies($_COOKIE);
    $request->addQueryData($_GET);

    $request->send();
    $values = json_decode($request->getResponseBody(),true);

    if (!is_array($values))
    {
      error_log($request->getResponseBody());
      throw new \ErrorException('shard responed with error: ... '.$values);  
    }
    
    
    foreach ($values as $name => $value)
    {
      $this->_response->appendValue($name,$value);
    }

    $r = $request;

    foreach ($r->getResponseHeader() as $header)
    {
      if (is_array($header))
      {
        foreach($header as $h)
        {
          error_log('adding array header '. $h);
          $this->_response->addHeader($h); 
        }
      }
      else
      {
        $this->_response->addHeader($header); 
      }
    }

    foreach ($r->getResponseCookies() as $cookie)
    {
      $this->_response->addHeader('Set-Cookie: '.http_build_cookie((array)$cookie));
    }
  }


  protected function isElasticSearch($request)
  {
    $engine = $this->_request->getParam('engine', 'native');
    return ($engine === 'elastic');
  }

  
	protected function getSearchStrategy($request)
	{
	    if ($this->isElasticSearch($request))
	    {
	    	return $this->getElasticSearchStrategyProvider()->provide($this);
	    }
	    else 
	    {
	    	return $this->getNativeSearchStrategyProvider()->provide($this);
	    }
	}


  protected function getShardDataByUserId($userId)
  {
    try
    {
      $values = $this->getShardingService()->getShardByUserId($userId);
      return array(
        'shardId' => $values['shardId'],
        'shardUrl' => $values['shardUrl']
      );
    }
    catch (ZeitfadenNoMatchException $e)
    {
      error_log('userid '.$userId.' was not found in the shardmaster, brute forcing it now...');
      try 
      {
        $response = $this->getCompositeService()->whereLivesUserById($userId);
        return array(
          'shardId' => $response['shardId'],
          'shardUrl' => $response['shardUrl']
        );
      }
      catch (ZeitfadenNoMatchException $e)
      {
        error_log('this user does not live in any of my shards...');
        throw new ErrorException('unkonw user.');
      }
      
    }
  }
  
  

  protected function performQuery($requestPath)
  {
    return $this->getCompositeService()->performQuery($requestPath);
  }

  
	protected function declareActionsThatNeedLogin()
	{
		return array();
	}
	
	
	public function getRequestParameter($name,$default)
	{
		return $this->_request->getParam($name,$default);
	}
	
	
  public function setApplicationId($val)
  {
    $this->applicationId = $val;
  }

  public function getApplicationId()
  {
    return $this->applicationId;
  }

  public function setCompositeService($val)
  {
    $this->compositeService = $val;
  }

  public function getCompositeService()
  {
    return $this->compositeService;
  }

  public function setShardingService($val)
  {
    $this->shardingService = $val;
  }

  public function getShardingService()
  {
    return $this->shardingService;
  }
  
  public function setElasticSearchStrategyProvider($val)
  {
  	$this->elasticSearchStrategyProvider = $val;
  }
  
  public function getElasticSearchStrategyProvider()
  {
  	return $this->elasticSearchStrategyProvider;
  }
  
  public function setNativeSearchStrategyProvider($val)
  {
  	$this->nativeSearchStrategyProvider = $val;
  }
  
  public function getNativeSearchStrategyProvider()
  {
  	return $this->nativeSearchStrategyProvider;
  }
  
  
}






