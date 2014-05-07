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



  public function getByIdsAction()
  {
    return $this->getByRequest();
  }

  public function getAction()
  {
    return $this->getByRequest();
  }


  protected function getByRequest()
  {
    $nodes = $this->getCompositeService()->getSubNodes();

    $returnEntities = array();
    
    foreach ($nodes as $node)
    {
      $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByRequest($node,$this->_request));
    }

    $returnEntities = $this->sortEntitiesByRequest($returnEntities, $this->_request);
    $returnEntities = $this->limitEntitiesByRequest($returnEntities, $this->_request);
    
    $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    
    $this->_response->setHash(array_values($returnEntities));
    
  }
  
  public function getByQueryAction()
  {
    $query = $this->_request->getParam('query', 'missing query');

    $useEngine = $this->_request->getParam('useEngine', 'native');
    
    $url = 'http://query-interpreter.zeitfaden.com/query/translateQuery/query/'.urlencode($query);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    switch ($useEngine)
    {
      case 'elastic':
        
        $potteryQueryString = $values['potteryQuery'];
  
        $queryEngine = new \PhpQueryLanguage\QueryEngine();
        $stationQuery = $queryEngine->translateQuery($potteryQueryString);
        
        
        $spec = $stationQuery->getSpecification();
  
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
        
        $stationsIds = $this->getStationIdsFromElasticResponse($responseArray);
  
  
        $nodes = $this->getCompositeService()->getSubNodes();
        $returnEntities = array();
        foreach ($nodes as $node)
        {
          $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByIds($node,$stationsIds));
        }
  
        $returnEntities = $this->sortStationsByElasticSearch($returnEntities, $responseArray);
        
        break;

      default:      

        $nodes = $this->getCompositeService()->getSubNodes();
    
        $returnEntities = array();
        
        foreach ($nodes as $node)
        {
          $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByQuery($node,$query));
        }
    
        $returnEntities = $this->sortEntitiesByQuery($returnEntities, $values);
        
        break;
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


  protected function getEntitiesOfNodeByQuery($node,$query)
  {
    $url = $node.'/getStationsByQuery/'.urlencode($query);
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    return $values;
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
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    $r->addQueryData($_GET);
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
    $limit = $request->getParam('limit',100);
    $limit = 100;
    $entities = array_slice($entities,0,$limit);


    return $entities;
  }


}




