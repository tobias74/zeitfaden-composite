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


  protected function getStationIndexName()
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

  protected function getElasticaIndex()
  {
    $client = $this->getElasticaClient();
    $index = $client->getIndex($this->getStationIndexName());

    if (!$index->exists())
    {
        $index->create(array());
        $index->refresh();    
    }

    return $index;
  }


  public function getStationType()
  {
    $index = $this->getElasticaIndex();
      $type = $index->getType('station');
      return $type;

  }



  public function deleteStationDocument($station)
  {
    $this->getStationType()->deleteIds(array($station->getId()));

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
      
  	  error_log(json_encode($query));
	  
      $path = $this->getElasticaIndex()->getName() . '/' . $this->getStationType()->getName() . '/_search';
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












