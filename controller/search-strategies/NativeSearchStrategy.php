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


	  $pool = new HttpRequestPool();
	  
	  $nodes = $this->getMyControllerContext()->getCompositeService()->getSubNodes();
      foreach ($nodes as $node)
      {
	    $r = $this->producePassOnHttpRequest($node,$request);
		$pool->attach($r);
      }
	  
	  $pool->send();
	  
      
      foreach ($pool as $r)
      {
        $returnEntities = array_merge($returnEntities, json_decode($r->getResponseBody(),true));
		
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
	    $datetime = $request->getParam('datetime',false);
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
	          $entityData['syntheticStartDateWithId'] = $entityData['startDate'].'_'.$entityData['id'];
	          $sortFieldValues[$key] = $entityData['syntheticStartDateWithId'];
	        }  
	        array_multisort($sortFieldValues, $sorter, $entities);
	      }
	      else 
	      {
	        $sortFieldValues = array();
	        foreach ($entities as $key => &$entityData)
	        {
	          $sortFieldValues[$key] = $entityData['startDate'];
	        }  
	        array_multisort($sortFieldValues, $sorter, $entities);
	      }
	    }
	    else 
	    {
	      //throw new WrongRequestException();
	    }
	    
	    
	    return $entities;
	}
	
	protected function limitEntitiesByRequest($entities,$request)
  	{
    	$limit = $request->getParam('limit',1000);
    	$entities = array_slice($entities,0,$limit);
    	return $entities;
	}
	
  protected function producePassOnHttpRequest($node,$request)
  {
      $params = "";
      foreach ($request->getParams() as $name => $value)
      {
        $params.=$name.'/'.urlencode($value).'/';
      }
  	
      $url = $node.'/'.$request->getController().'/'.$request->getAction().'/'.$params;
	
	
      $requestMethods = array(
        'GET' => HttpRequest::METH_GET,
        'POST' => HttpRequest::METH_POST
      );
      
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



