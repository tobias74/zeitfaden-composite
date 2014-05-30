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

	protected function getMyEntityDataById($stationId, $userId = false)
	{
		return $this->getStationDataById($stationId,$userId);
	}

  protected function getEntitiesByIds($request)
  {
    return $this->getSearchStrategy($request)->getStationsByIds($request);
  }

  protected function getEntitiesByRequest($request)
  {
    return $this->getSearchStrategy($request)->getStationsByRequest($request);
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
        throw new ZeitfadenNoMatchException();
      }
      else 
      {
        $entityData = $returnEntities[0];
        return $entityData;
      }
    }
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




