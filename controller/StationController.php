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


  public function getByIdsAction()
  {
  	$returnEntities = $this->getSearchStrategy($this->_request)->getStationsByIds($this->_request);
    $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    $this->_response->setHash(array_values($returnEntities));
  }
  
  public function getAction()
  {
  	$returnEntities = $this->getSearchStrategy($this->_request)->getStationsByRequest($this->_request);
    $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    $this->_response->setHash(array_values($returnEntities));
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




