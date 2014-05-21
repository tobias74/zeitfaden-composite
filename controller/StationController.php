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
  
  
  


  public function setElasticSearchQueryArrayProvider($provider)
  {
  	$this->elasticSearchQueryArrayProvider = $provider;
  }

  public function setElasticSearchSortArrayProvider($provider)
  {
  	$this->elasticSearchSortArrayProvider = $provider;
  }

  protected function getElasticSearchQueryArray()
  {
  	return $this->elasticSearchQueryArrayProvider->provide($this->getElasticSearchStationDataMap());
  }
  
  protected function getElasticSearchSortArray()
  {
  	return $this->elasticSearchSortArrayProvider->provide($this->getElasticSearchStationDataMap());
  }

  public function setElasticSearchStationDataMap($val)
  {
  	$this->elasticSearchStationMapper = $val;
  }
  
  protected function getElasticSearchStationDataMap()
  {
  	return $this->elasticSearchStationMapper;
  }
  

  protected function getEntitiesUsingElasticSearch($request)
  {
    
    $spec = $this->getSpecificationByRequest($this->_request);
    
    
    if ($spec->hasCriteria())
    {
      //$whereArrayMaker = new \Zeitfaden\ElasticSearch\ElasticSearchQueryArray();
      $whereArrayMaker = $this->getElasticSearchQueryArray();
      $spec->getCriteria()->acceptVisitor($whereArrayMaker);
      $filter = $whereArrayMaker->getArrayForCriteria($spec->getCriteria());
      //error_log(json_encode($filter));
    }
    else 
    {
      $filter=array();  
    }
    
    if ($spec->hasOrderer())
    {
      //$sortArrayMaker = new \Zeitfaden\ElasticSearch\ElasticSearchOrderArray();
      $sortArrayMaker = $this->getElasticSearchSortArray();
      $spec->getOrderer()->acceptVisitor($sortArrayMaker);
      $sortHash = $sortArrayMaker->getArrayForOrderer($spec->getOrderer());
      //error_log(json_encode($sortHash));
    }
    else 
    {
      $sortHash = array();  
    }

  
    $responseArray = $this->getElasticSearchService()->searchStations($filter,$sortHash,$spec->getLimiter());
    

    $finalResponse = array();
    foreach ($responseArray['hits']['hits'] as $index => $data)
    {
      $finalResponse[$index] = $data['_source'];
    }
    
    return $finalResponse;
    
  }
  





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





}




