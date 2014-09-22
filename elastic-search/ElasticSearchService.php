<?php
namespace Zeitfaden\ElasticSearch;

class ElasticSearchService
{

    public function setApplication($val)
    {
        $this->application = $val;
    }

    protected function getApplication()
    {
        return $this->application;
    }


    public function setProfiler($val)
    {
        $this->profiler = $val;
    }

    protected function getProfiler()
    {
        return $this->profiler;
    }

    protected function getApplicationId()
    {
        return $this->getApplication()->getApplicationId();
    }


  protected function getElasticSearchConfiguration()
  {
    return array(
      'host' => $this->getApplication()->getConfig()->getElasticSearchHost()
    );
  }

  protected function getElasticSearchNativeConfiguration()
  {
    $values = $this->getApplication()->getApplicationIni();
    
    return array(
      'host' => $this->getApplication()->getConfig()->getElasticSearchHost()
    );
  }

  protected function getNativeClient()
  {
  	$client = new \Elasticsearch\Client($this->getElasticSearchNativeConfiguration());
	return $client;
	
  }

  protected function getStationIndexName()
  {
    switch ($this->getApplicationId())
    {
      case "zeitfaden_test": 
        return "clojure-stations-test";//,clojure-stations-anonymous-test";
        break;
      
      case "zeitfaden_live";
        return "clojure-stations-live";//,clojure-stations-anonymous-live";
        break;
      
    }
  }



  public function getStationTypeName()
  {
  	return "station";//, station-anonymous";
  }

  public function getUserTypeName()
  {
    return "user";//, station-anonymous";
  }


  protected function getStationIndexToSearch()
  {
    switch ($this->getApplicationId())
    {
      case "zeitfaden_test": 
        return "clojure-stations-test,clojure-stations-anonymous-test";
        break;
      
      case "zeitfaden_live";
        return "clojure-stations-live,clojure-stations-anonymous-live";
        break;
      
    }
  }

  protected function getUserStationIndexToSearch()
  {
    switch ($this->getApplicationId())
    {
      case "zeitfaden_test": 
        return "clojure-stations-test";
        break;
      
      case "zeitfaden_live";
        return "clojure-stations-live";
        break;
      
    }
  }


  public function getStationTypeToSearch()
  {
  	return "station";
  }

  public function getUserTypeToSearch()
  {
  	return "user";
  }


  public function searchStations($filter,$sort,$limiter)
  {
      $query = array(
          'query' => array(
            'matchAll' => new \stdClass()
          ),

          'filter' => $filter,


          'sort' => $sort
          
          ,'from' => $limiter->getOffset()
		  ,'size' => $limiter->getLength()
 
      );
      
	  $params = array();
	  $params['index'] = $this->getStationIndexToSearch();
	  $params['type'] = $this->getStationTypeToSearch();
	  $params['body'] = $query;
	  
  	  error_log(json_encode($params));
	  
    $timer = $this->getProfiler()->startTimer('elastic searching stations');
	  $returnValue = $this->getNativeClient()->search($params);
	  $timer->stop();
    
    return $returnValue;
  }



  public function searchUserStations($filter,$sort,$limiter)
  {
      $query = array(
          'query' => array(
            'matchAll' => new \stdClass()
          ),

          'filter' => $filter,


          'sort' => $sort
          
          ,'from' => $limiter->getOffset()
		  ,'size' => $limiter->getLength()
 
      );
      
	  $params = array();
	  $params['index'] = $this->getUserStationIndexToSearch();
	  $params['type'] = $this->getStationTypeToSearch();
	  $params['body'] = $query;
	  
  	  error_log(json_encode($params));
	  
    $timer = $this->getProfiler()->startTimer('elastic searching userstations');
    $returnValue = $this->getNativeClient()->search($params);
    $timer->stop();
    
    return $returnValue;

  }


  public function searchUsers($filter,$sort,$limiter)
  {
      $query = array(
          'query' => array(
            'matchAll' => new \stdClass()
          ),

          'filter' => $filter,


          'sort' => $sort
          
          ,'from' => $limiter->getOffset()
      ,'size' => $limiter->getLength()
 
      );
      
    $params = array();
    $params['index'] = $this->getUserStationIndexToSearch();
    $params['type'] = $this->getUserTypeToSearch();
    $params['body'] = $query;
    
      error_log(json_encode($params));
    
    $timer = $this->getProfiler()->startTimer('elastic searching users');
    $returnValue = $this->getNativeClient()->search($params);
    $timer->stop();
    
    return $returnValue;
  }




}












