<?php

class StationController extends AbstractCompositeController
{
  protected $idName = 'stationId';
  protected $controllerPath='station';

	protected function declareActionsThatNeedLogin()
	{
		return array(
			'setLocation',
			'setGroups',
			'create'
			);
	}

  protected function getEntityDataByRequest($request)
  {
    $userId = $request->getParam('userId',0);
    $stationId = $request->getParam('stationId',0);
    $stationData = $this->getStationDataById($stationId, $userId);
    return $stationData;
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

  public function createAction()
  {
    $this->passToMyShard();
  }

  public function updateAction()
  {
    $this->passToMyShard();
  }

  public function deleteAction()
  {
    $this->passToMyShard();
  }

	public function upsertAction()
	{
    $this->passToMyShard();
 	}
  
  public function getByIdsAction()
  {
    return $this->getByRequest($this->_request);
  }

  public function getAction()
  {
    return $this->getByRequest($this->_request);
  }






  
  protected function sortStationsByElasticSearch($entities, $responseArray)
  {
    return $entities;
  }


  protected function getStationIdsFromElasticResponse($responseArray)
  {
    if (!isset($responseArray['hits']['hits']))
    {
      return array();
    }
    $hits = $responseArray['hits']['hits'];
    $ids = array();
    foreach ($hits as $hit)
    {
      $ids[] = $hit['_id'];
    }
    
    return $ids;
  }


  protected function getEntitiesUsingElasticSearch($request)
  {
    
    $spec = $this->getSpecificationByRequest($this->_request);
    
    if ($spec->hasCriteria())
    {
      $whereArrayMaker = new \Zeitfaden\ElasticSearch\ElasticSearchQueryArray();
      $spec->getCriteria()->acceptVisitor($whereArrayMaker);
      $filter = $whereArrayMaker->getArrayForCriteria($spec->getCriteria());
      error_log(json_encode($filter));
    }
    else 
    {
      $filter=array();  
    }

    $responseArray = $this->getElasticSearchService()->performQuery($filter);

    $finalResponse = array();
    foreach ($responseArray['hits']['hits'] as $index => $data)
    {
      $finalResponse[$index] = $data['_source'];
    }
    
    return $finalResponse;
    //$stationsIds = $this->getStationIdsFromElasticResponse($responseArray);

    /*
    $nodes = $this->getCompositeService()->getSubNodes();
    $returnEntities = array();
    foreach ($nodes as $node)
    {
      $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByIds($node,$stationsIds));
    }

    $returnEntities = $this->sortStationsByElasticSearch($returnEntities, $responseArray);
    */
    
  }
  

  protected function isElasticSearch($request)
  {
    $engine = $this->_request->getParam('engine', 'native');
    return ($engine === 'elastic');
  }

  protected function getByRequest($request)
  {
    if ($this->isElasticSearch($request))
    {
      $returnEntities = $this->getEntitiesUsingElasticSearch($request);
    }
    else 
    {
      $nodes = $this->getCompositeService()->getSubNodes();
  
      $returnEntities = array();
      
      foreach ($nodes as $node)
      {
        $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByRequest($node,$request));
      }
  
      $returnEntities = $this->sortEntitiesByRequest($returnEntities, $request);
      $returnEntities = $this->limitEntitiesByRequest($returnEntities, $request);
      
    }
    
    $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    $this->_response->setHash(array_values($returnEntities));
    
  }
  



/*
  protected function getEntitiesOfNodeByIds($node,$ids)
  {
    $url = $node.'/station/getByIds/';
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addQueryData(array('stationIds' => $ids));
    $r->addCookies($_COOKIE);
    $r->send();
    
    error_log('we have code '.$r->getResponseCode());
    if ($r->getResponseCode() == 404)
    {
      return array();
    }
    else
    {
      $values = json_decode($r->getResponseBody(),true);
      return $values;
    }
    
  }
*/


  protected function getEntitiesOfNodeById($node,$id)
  {
    $url = $node.'/station/getById/stationId/'.$id;
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    $r->send();

    if ($r->getResponseCode() == 404)
    {
      return array();
    }
    else
    {
      $values = json_decode($r->getResponseBody(),true);
      $values['shardUrl'] = substr($node,7);
      
      return array($values);
    }
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


}




