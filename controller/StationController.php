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

  public function testAction()
  {
    echo $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    die();
    echo utf8_decode($this->produceLocationDescription(54,10));
    die();
    
    $cache = $this->getReverseGeocoderCache();
    $cache->set(47.6,22, "tobias asd ist here");
    echo $cache->get(47.6,22.0001);
    echo "<br>";
  	echo $this->getRedisClient()->get('tobias');
	  die();
  }
  
  
  protected function getEntityDataByRequest($request)
  {
    $userId = $request->getParam('userId',0);
    $stationId = $request->getParam('stationId',0);
    $stationData = $this->getStationDataById($stationId, $userId);
    return $stationData;
  }


  protected function getAttachmentUrlByRequest($request)
  {
    $entityData = $this->getEntityDataByRequest($request);
    $serveAttachmentUrl = 'http://'.$entityData['shardUrl'].'/'.$this->controllerPath.'/serveAttachment/'.$this->idName.'/'.$entityData['id'];
    return $serveAttachmentUrl;
  }


  protected function getEntitiesByIds($request)
  {
    return $this->getSearchStrategy($request)->getStationsByIds($request);
  }

  protected function getEntitiesByRequest($request)
  {
    $entityData = $this->getSearchStrategy($request)->getStationsByRequest($request);
    
    foreach ($entityData as &$data)
    {
      $data['locationDescription'] = $this->getLocationDescription($data['startLatitude'], $data['startLongitude']);
    }
    
    return $entityData;
  }
  

  public function createAction()
  {
    if ($this->getUserSession()->isUserLoggedIn())
    {
      $this->passCurrentRequestToShardUrl( $this->getMyShardUrl() );
    }
    else
    {
      $this->passToRandomShard();
    }
  }

  private function passToRandomShard()
  {
    $shardUrl = substr($this->getCompositeService()->getRandomSubNode(),7);
    $this->passCurrentRequestToShardUrl($shardUrl);
  }

  public function upsertAction()
  {
    if ($this->getUserSession()->isUserLoggedIn())
    {
      $this->passCurrentRequestToShardUrl( $this->getMyShardUrl() );
    }
    else
    {
      throw new \ErrorException('need userid for oauth upsert');
    }
    
  }


  public function updateAction()
  {
    $this->passToAccordingShard();
  }

  public function deleteAction()
  {
    $this->passToAccordingShard();
  }

  
  
  private function passToAccordingShard()
  {
    if ($this->getUserSession()->isUserLoggedIn())
    {
      $shardUrl = $this->getMyShardUrl();
    }
    else
    {
      $stationId = $this->_request->getParam('stationId',0);
      $shardUrl = $this->getShardUrlByStationId($stationId);
    }
    
    $this->passCurrentRequestToShardUrl($shardUrl);
  }
  


  protected function getShardUrlByStationId($stationId)
  {
    $stationData = $this->getStationDataById($stationId);
    return $stationData['shardUrl'];
    //return $this->getCompositeService()->getShardUrlById($shardId);
  }
  
  
  private function produceRequestForStation($node,$stationId)
  {
      $url = $node.'/station/getById/stationId/'.$stationId;
      
      $r = new HttpRequest($url, HttpRequest::METH_GET);
      $r->addCookies($_COOKIE);
    
      return $r;    
  }
  


  protected function getStationDataById($stationId, $userId = false)
  {
    if ($userId != false)
    {
      $shardData = $this->getShardByUserId($userId);
      $url = 'http://'.$shardData['shardUrl'].'/station/getById/stationId/'.$stationId.'/userId/'.$userId;
      
      $r = new HttpRequest($url, HttpRequest::METH_GET);
      $r->addCookies($_COOKIE);
      $r->send();
      $values = json_decode($r->getResponseBody(),true);
      
      $returnValues = $values;
      
      $returnValues['shardUrl'] = $shardData['shardUrl'];
  
      return $returnValues;
      
    }
    else 
    {
      $entities = array();
      $pool = new HttpRequestPool();
      
      $nodes = $this->getCompositeService()->getSubNodes();
      foreach ($nodes as $node)
      {
        $r = $this->produceRequestForStation($node,$stationId);
        $pool->attach($r);
      }
      
      $pool->send();
      foreach ($pool as $r)
      {
        $responseHash = json_decode($r->getResponseBody(),true);
        if (!is_array($responseHash))
        {
          error_log("response was an error in native search startegy: ".$responseHash);
          //error_log(print_r($request,true));
        }
        if ($r->getResponseCode() === 200)
        {
          $entities[] = $responseHash;
        }
      }
      
      if (count($entities) > 1)
      {
        throw new \ErrorException('found too many');
      }
      elseif (count($entities) === 0)
      {
        throw new \ZeitfadenNoMatchException('did not find any, but thats ok here');
      }
      else
      {
        $entity = $entities[0];
        //$shardId = $entity['shardId'];
      }
      
      return $entity;
    }
  }

}




