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

    protected function getApplicationId()
    {
        return $this->getApplication()->getApplicationId();
    }


  protected function getElasticSearchConfiguration()
  {
    $values = $this->getApplication()->getApplicationIni();
    
    return array(
      'host' => $values['elastic_search_host']
    );
  }

  protected function getElasticaClient()
  {
      $client = new \Elastica\Client($this->getElasticSearchConfiguration());
      return $client;

  }

  protected function getElasticSearchNativeConfiguration()
  {
    $values = $this->getApplication()->getApplicationIni();
    
    return array(
      'hosts' => array($values['elastic_search_host'])
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

  public function getStationTypeToSearch()
  {
  	return "station";
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
	  
	  return $this->getNativeClient()->search($params);
  }


  public function old__searchStations($filter,$sort,$limiter)
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
      
  	  error_log(json_encode($query));
	  
      $path = $this->getStationIndexName() . '/' . $this->getStationTypeName() . '/_search';
      $response = $this->getElasticaClient()->request($path, \Elastica\Request::GET, $query);
      $responseArray = $response->getData();
      return $responseArray;
  }



  public function searchUsers($filter,$sort,$limiter)
  {
      $query = array(
          'query' => array(
            'matchAll' => new \stdClass()
          ),

          'filter' => $filter,

/*
 "aggs": {
    "userIds": {
      "filter": {
        "range": {
          "startDateWithId": {
            "gte": "2014-01-01 00:00:00_521e7be2c295b854591"
          }
        }
      },
      "aggs": {
        "Users_with_stations": {
          "terms": {
            "field": "userId"
          }
        }
      }
    }
  },

*/


          'sort' => $sort
          
          ,'from' => $limiter->getOffset()
      ,'size' => $limiter->getLength()
 
      );
      
      error_log(json_encode($query));
    
      $path = $this->getElasticaIndex()->getName() . '/' . $this->getStationType()->getName() . '/_search';
      $response = $this->getElasticaClient()->request($path, \Elastica\Request::GET, $query);
      $responseArray = $response->getData();
      
      return $responseArray;
        

  }



}












