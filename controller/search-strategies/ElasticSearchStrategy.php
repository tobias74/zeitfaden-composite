<?php

class ElasticSearchStrategy extends AbstractSearchStrategy
{
	
  
  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }

  
	public function getUsersByIds($request)
	{
		throw new \ErrorException('not yet implemented');
	}

	public function getUsersByRequest($request)
	{
		// we will load stations here in multiple reuqests, make them distinct, every new request filtering out the previous users
		// then we will load the corresponding users-data and join the thing.
	    
	    $spec = $this->getMyControllerContext()->getSpecificationByRequest($request);

		$allStations = array();
		
		do
		{
			$stations = $this->getUserStationsBySpecification($spec);

      $groupedStations = __::groupBy($stations, function($station){return $station['userId'];});
      
      $bestStations = __::map($groupedStations, function($stationGroup){return $stationGroup[0];});
      $allStations = array_merge($allStations, $bestStations);

			$userIds = __::map($bestStations, function($station, $key){
				return $station['userId'];	
			});
			
			foreach ($userIds as $userId)
			{
				$criteria = $spec->getCriteria();
				$criteria = $criteria->logicalAnd(new \VisitableSpecification\NotEqualCriteria('userId',$userId));
				$spec->setCriteria($criteria);
			}
			
		}
		while ((count($stations) > 0) && (count($allStations) < 100));
		
    
    $spec = new \VisitableSpecification\Specification();
    $criteria = new \VisitableSpecification\EqualCriteria('id',-1);
    $spec->setCriteria($criteria);
    
    $userIds = __::map($allStations, function($station){return $station['userId'];});
    foreach ($userIds as $userId)
    {
      $criteria = $spec->getCriteria();
      $criteria = $criteria->logicalOr(new \VisitableSpecification\EqualCriteria('id',$userId));
      $spec->setCriteria($criteria);
    }
    
    $spec->setLimiter(new \VisitableSpecification\Limiter(0,count($allStations)+1));
    $allUsers = $this->getUsersBySpecification($spec);
    
    
    $enrichedUsers = __::map($allStations, function($station) use ($allUsers){
      $currentUserId = $station['userId'];  
      $userData = __::find($allUsers,function($user) use ($currentUserId){
        return ($user['id'] == $currentUserId);
      });
      
      $userData['matchedStationData']=$station;
      return $userData;
    });

    return $enrichedUsers;
    
	}
	
	public function getStationsByIds($request)
	{
		throw new \ErrorException('not yet implemented');
	}
	
	public function getStationsByRequest($request)
	{
	    $spec = $this->getMyControllerContext()->getSpecificationByRequest($request);
		return $this->getStationsBySpecification($spec);	    
	}
	
	
	
	protected function getStationsBySpecification($spec)
	{
	    if ($spec->hasCriteria())
	    {
	      $whereArrayMaker = $this->getElasticSearchQueryArray();
	      $spec->getCriteria()->acceptVisitor($whereArrayMaker);
	      $filter = $whereArrayMaker->getArrayForCriteria($spec->getCriteria());
	    }
	    else 
	    {
	      $filter=array();  
	    }
	    
	    if ($spec->hasOrderer())
	    {
	      $sortArrayMaker = $this->getElasticSearchSortArray();
	      $spec->getOrderer()->acceptVisitor($sortArrayMaker);
	      $sortHash = $sortArrayMaker->getArrayForOrderer($spec->getOrderer());
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
	


	protected function getUserStationsBySpecification($spec)
	{
	    if ($spec->hasCriteria())
	    {
	      $whereArrayMaker = $this->getElasticSearchQueryArray();
	      $spec->getCriteria()->acceptVisitor($whereArrayMaker);
	      $filter = $whereArrayMaker->getArrayForCriteria($spec->getCriteria());
	    }
	    else 
	    {
	      $filter=array();  
	    }
	    
	    if ($spec->hasOrderer())
	    {
	      $sortArrayMaker = $this->getElasticSearchSortArray();
	      $spec->getOrderer()->acceptVisitor($sortArrayMaker);
	      $sortHash = $sortArrayMaker->getArrayForOrderer($spec->getOrderer());
	    }
	    else 
	    {
	      $sortHash = array();  
	    }
	
	    $responseArray = $this->getElasticSearchService()->searchUserStations($filter,$sortHash,$spec->getLimiter());
	    
	
	    $finalResponse = array();
	    foreach ($responseArray['hits']['hits'] as $index => $data)
	    {
	      $finalResponse[$index] = $data['_source'];
	    }
	    
	    return $finalResponse;
		
	}	



  protected function getUsersBySpecification($spec)
  {
      if ($spec->hasCriteria())
      {
        $whereArrayMaker = $this->getElasticSearchQueryArray();
        $spec->getCriteria()->acceptVisitor($whereArrayMaker);
        $filter = $whereArrayMaker->getArrayForCriteria($spec->getCriteria());
      }
      else 
      {
        $filter=array();  
      }
      
      if ($spec->hasOrderer())
      {
        $sortArrayMaker = $this->getElasticSearchSortArray();
        $spec->getOrderer()->acceptVisitor($sortArrayMaker);
        $sortHash = $sortArrayMaker->getArrayForOrderer($spec->getOrderer());
      }
      else 
      {
        $sortHash = array();  
      }
  
      $responseArray = $this->getElasticSearchService()->searchUsers($filter,$sortHash,$spec->getLimiter());
      
  
      $finalResponse = array();
      foreach ($responseArray['hits']['hits'] as $index => $data)
      {
        $finalResponse[$index] = $data['_source'];
      }
      
      return $finalResponse;
    
  } 

	
	
	
  public function setElasticSearchService($val)
  {
    $this->elasticSearchService = $val;
  }

  protected function getElasticSearchService()
  {
    return $this->elasticSearchService;
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
  
	
	
}
