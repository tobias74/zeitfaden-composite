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
      $stationId = $this->_request->getParam('stationId',0);
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
        $this->_response->addHeader('HTTP/1.0 404 Not Found');
      }
      else 
      {
        $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
        $entityData = $returnEntities[0];
        $this->_response->setHash($entityData);
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
    $url = $node.'/station/getById/';
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addQueryData(array('stationId' => $id));
    $r->addCookies($_COOKIE);
    $r->send();

    if ($r->getResponseCode() == 404)
    {
      return array();
    }
    else
    {
      $values = json_decode($r->getResponseBody(),true);
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



}




