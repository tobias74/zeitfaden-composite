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
	
	
	protected function performSearchOnNodes($request)
	{
	  $nodes = $this->getMyControllerContext()->getCompositeService()->getSubNodes();
  
      $returnEntities = array();
      
      foreach ($nodes as $node)
      {
        $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByRequest($node,$request));
      }
  
      $returnEntities = $this->sortEntitiesByRequest($returnEntities, $request);
      $returnEntities = $this->limitEntitiesByRequest($returnEntities, $request);
		
		return $returnEntities;		
	}
	
	protected function getEntitiesOfNodeByRequest($node,$request)
	{
	    $params = "";
	    foreach ($request->getParams() as $name => $value)
	    {
	      $params.=$name.'/'.urlencode($value).'/';
	    }
	    
	    $url = $node.'/'.$request->getController().'/'.$request->getAction().'/'.$params;
	    //die($url);
	    $r = $this->producePassOnHttpRequest($url,$request);
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
	
  protected function producePassOnHttpRequest($url,$request)
  {
      //$r = new HttpRequest($url, HttpRequest::METH_GET);
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



