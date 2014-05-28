<?php 
use SugarLoaf as SL;

abstract class AbstractCompositeController extends AbstractZeitfadenController
{

  protected function getUniqueId()
  {
      $uid=uniqid();
      $uid.=rand(100000,999999);
      return $uid;
  }


  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }


  public function getByIdsAction()
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




  protected function getAttachmentUrlByRequest($request)
  {
    $entityId = $request->getParam($this->idName,0);
    $entityData = $this->getEntityDataByRequest($request);
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
      $returnEntities = $this->attachLoadBalancedUrls(array($entityData));
      $this->_response->setHash($returnEntities[0]);
    }
    catch (ZeitfadenNoMatchException $e)
    {
      $this->_response->addHeader('HTTP/1.0 404 Not Found');
    }
    
  }
  
  	



  protected function passToMyShard()
  {
    $shardUrl = $this->getHomeShardUrl();
    $this->passCurrentRequestToShardUrl($shardUrl);
  }



  protected function getHomeShardUrl()
  {
    if ($this->getUserSession()->isUserLoggedIn())
    {
      $userId = $this->getUserSession()->getLoggedInUserId();
      $homeShard = $this->getCompositeService()->whereLivesUserById($userId);
  
      $returnUrl = $homeShard['shardUrl'];
    }
    else
    {
      $returnUrl = substr($this->getCompositeService()->getRandomSubNode(),7);
    }

    error_log('return url from getHomeShardUrl: '.$returnUrl);
    
    return $returnUrl;
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
        error_log('passing along a post');
        error_log(print_r($_POST,true));
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


  protected function getShardByUserId($userId)
  {
    $values = $this->getShardingService()->getShardDataByUserId($userId);
    if ($values['status'] == 'not_found')
    {
      error_log('userid '.$userId.' was not found in the shardmaster, brute forcing it now...');
      $response = $this->getCompositeService()->whereLivesUserById($userId);
  
      return array(
        'shardId' => $response['shardId'],
        'shardUrl' => $response['shardUrl']
      );
    }
    else
    {
      return array(
        'shardId' => $values['shard']['shardId'],
        'shardUrl' => $values['shard']['url']
      );
  
    }
  
  }
  
  
  protected function getStationDataById($stationId, $userId = false)
  {
    if ($userId != false)
    {
      $shardData = $this->getShardByUserId($userId);
      $url = 'http://'.$shardData['shardUrl'].'/station/getById/stationId/'.$stationId.'/userId/'.$userId;
      
      $r = new HttpRequest($url, HttpRequest::METH_GET);
      $r->addCookies($_COOKIE);
      $r->send();
      $values = json_decode($r->getResponseBody(),true);
      
      $returnValues = $values;
      
      $returnValues['shardUrl'] = $shardData['shardUrl'];
  
      return $returnValues;
      
    }
    else 
    {
      $nodes = $this->getCompositeService()->getSubNodes();
      $returnEntities = array();
      foreach ($nodes as $node)
      {
        $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeById($node,$stationId));
      }
        
      if (count($returnEntities) > 1)
      {
        throw new \ErrorException('found too many.');
      }
      else if (count($returnEntities) === 0)
      {
        throw new ZeitfadenNoMatchException();
      }
      else 
      {
        $entityData = $returnEntities[0];
        return $entityData;
      }
    }
  }



  protected function getUserDataById($userId)
  {
    $shardData = $this->getShardByUserId($userId);
    $url = 'http://'.$shardData['shardUrl'].'/user/getById/userId/'.$userId;
    
    //echo $url;
    
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    
    $returnValues = $values['user'];
    
    $returnValues['shardUrl'] = $shardData['shardUrl'];

    return $returnValues;
  
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








