<?php

class NativeSearchStrategy extends AbstractSearchStrategy
{
	
	public function getUsersByIds($request)
	{
		return $this->performSearchOnNodes($request);
	}

	public function getUsersByRequest($request)
	{
		return $this->performSearchOnNodes($request);
	}
	
	public function getStationsByIds($request)
	{
		return $this->performSearchOnNodes($request);
	}
	
	public function getStationsByRequest($request)
	{
		return $this->performSearchOnNodes($request);
	}
	
  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }


	
	protected function performSearchOnNodes($request)
	{
      $returnEntities = array();


	  $pool = new \HttpRequestPool();
	  
	  $nodes = $this->getMyControllerContext()->getCompositeService()->getSubNodes();
    foreach ($nodes as $node)
    {
	    $r = $this->producePassOnHttpRequest($node,$request);
      $pool->attach($r);
    }
	  
	  $pool->send();
	  
      
    foreach ($pool as $r)
    {
      $responseHash = json_decode($r->getResponseBody(),true);
      if (!is_array($responseHash))
      {
        error_log("response was an error in native search startegy: ".$responseHash);
        error_log("this is the url called: ".$r->getUrl());
        //error_log(print_r($request,true));
      }
      else 
      {
        $returnEntities = array_merge($returnEntities, $responseHash);
      }
	
    }

    $returnEntities = $this->sortEntitiesByRequest($returnEntities, $request);
    $returnEntities = $this->limitEntitiesByRequest($returnEntities, $request);
	
		return $returnEntities;		
	}
	
	protected function getEntitiesOfNodeByRequest($node,$request)
	{
	    $r = $this->producePassOnHttpRequest($node,$request);
	    $r->send();
	    $values = json_decode($r->getResponseBody(),true);
	    return $values;
	}
	
	
	
	protected function sortEntitiesByRequest($entities,$request)
	{
	    $sort = $request->getParam('sort',false);
	    $direction = $request->getParam('direction',false);
	    $lastId = $request->getParam('lastId',false);
	    
	    if ($sort === 'byTime')
	    {
	      if ($direction === 'intoTheFuture')
	      {
	        $sorter = SORT_ASC;
	      }
	      else 
	      {
	        $sorter = SORT_DESC;
	      }
	      
	      if ($lastId)
	      {
	        $sortFieldValues = array();
	        foreach ($entities as $key => &$entityData)
	        {
	          $entityData['sortDateWithId'] = $entityData['sortDateString'].'_'.$entityData['id'];
	          $sortFieldValues[$key] = $entityData['sortDateWithId'];
	        }  
	        array_multisort($sortFieldValues, $sorter, $entities);
	      }
	      else 
	      {
	        $sortFieldValues = array();
	        foreach ($entities as $key => &$entityData)
	        {
	          $sortFieldValues[$key] = $entityData['sortDateString'];
	        }  
	        array_multisort($sortFieldValues, $sorter, $entities);
	      }
	    }
		else if ($sort == "byDistanceToPin")
		{
	      if ($direction === 'farFirst')
	      {
	        $sorter = SORT_DESC;
	      }
	      else 
	      {
	        $sorter = SORT_ASC;
	      }
          $sortFieldValues = array();
	      foreach ($entities as $key => &$entityData)
	      {
	        $sortFieldValues[$key] = $entityData['distanceToPin'];
	      }  
	      array_multisort($sortFieldValues, $sorter, $entities);
			
		}
	    else 
	    {
	      //throw new WrongRequestException();
	    }
	    
	    
	    return $entities;
	}
	
	protected function limitEntitiesByRequest($entities,$request)
  	{
    	$limit = $request->getParam('limit',100);
    	$entities = array_slice($entities,0,$limit);
    	return $entities;
	}
	
  protected function producePassOnHttpRequest($node,$request)
  {
	    $url = 'http://'.$node.$_SERVER['REQUEST_URI'];
	
      $requestMethods = array(
        'GET' => HttpRequest::METH_GET,
        'POST' => HttpRequest::METH_POST
      );
      
      error_log('doing this '.$url);
      error_log('having this in get: '.print_r($_GET,true));
      
      $r = new HttpRequest($url, $requestMethods[$_SERVER['REQUEST_METHOD']]);
      $r->addCookies($_COOKIE);
      $r->addQueryData($_GET);
    
      switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
          $r->setPostFields($_POST);
          break;
      }
    
      return $r;    
  }
	
	
}



