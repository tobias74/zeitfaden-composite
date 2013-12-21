<?php

class StationController extends AbstractCompositeController
{
	protected function declareActionsThatNeedLogin()
	{
		return array(
			'setLocation',
			'setGroups',
			'create'
			);
	}



  public function getImageAction()
  {
    
    $userId = $this->_request->getParam('userId',0);
    $stationId = $this->_request->getParam('stationId',0);
    $imageSize = $this->_request->getParam('imageSize','medium');

    $stationData = $this->getStationDataById($stationId, $userId);
    

    $serveAttachmentUrl = 'http://'.$stationData['shardUrl'].'/station/serveAttachment/userId/'.$userId.'/stationId/'.$stationId;
    
    $flyUrl = 'http://flyservice.butterfurz.de/image/getFlyImageId/imageSize/'.$imageSize.'?imageUrl='.$serveAttachmentUrl;
    
    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
    $r->send();
    
    $values = json_decode($r->getResponseBody(),true);
    
    $this->sendGridFile($values);    
  }




  public function getVideoAction()
  {
    $userId = $this->_request->getParam('userId',0);
    $stationId = $this->_request->getParam('stationId',0);
    $format = $this->_request->getParam('format','webm');

    $stationData = $this->getStationDataById($stationId, $userId);

    $serveAttachmentUrl = 'http://'.$stationData['shardUrl'].'/station/serveAttachment/userId/'.$userId.'/stationId/'.$stationId;
    $flyUrl = 'http://flyservice.butterfurz.de/video/getFlyVideoId/format/'.$format.'?videoUrl='.$serveAttachmentUrl;
    
    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    $this->sendGridFile($values);    
    
  }

	
		
	public function getByIdAction()
	{
    $this->passToMyShard();
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
  







  public function getByQueryAction()
  {
    $query = $this->_request->getParam('query', 'missing query');
    
    $url = 'http://query-interpreter.zeitfaden.com/query/translateQuery/query/'.urlencode($query);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    $sortDirection = $values['sortDirection'];
    $sortField = $values['sortField'];
    
    $limit = $values['limit'];

    $nodes = $this->getCompositeService()->getSubNodes();

    $returnEntities = array();
    
    foreach ($nodes as $node)
    {
      $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNode($node,$query));
    }
    



    // hier noch die eintraege sortieren.
    $band = array();
    $auflage = array();
    
    if ($sortField != false)
    {
      error_log('going int for the psciala sorrtinesdfkjjhkj applying special sort');
      foreach ($returnEntities as $key => $row) 
      {
          $band[$key]    = $row['id'];
          $auflage[$key] = $row[$sortField];
      }
      if ($sortDirection == 'ASC')
      {
        array_multisort($band, SORT_ASC, $auflage, SORT_ASC, $returnEntities);
      }
      else if ($sortDirection == 'DESC')
      {
        array_multisort($band, SORT_ASC, $auflage, SORT_DESC, $returnEntities);
      }
      else
      {
        throw new \ErrorException('why no direction?');
      }
    }
    else
    {
      error_log('not applying special sort');
      foreach ($returnEntities as $key => $row) 
      {
          $band[$key]    = $row['id'];
      }
      array_multisort($band, SORT_ASC, $returnEntities);
    }      
          
        
    
    $returnEntities = array_slice($returnEntities,0,$limit);

    $frontEndUrls = $this->getCompositeService()->getFrontEndUrls();

    foreach($returnEntities as &$entity)
     {
         if (isset($entity['smallFrontImageUrl']))
         {
           $relativeUrl = $entity['smallFrontImageUrl'];
           $frontEndNumber = (crc32($relativeUrl) % 4);
           $frontEndUrl = $frontEndUrls[$frontEndNumber];
           $entity['smallFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['smallFrontImageUrl'];
           $entity['mediumFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['mediumFrontImageUrl'];
           $entity['bigFrontImageUrl'] = 'http://'.$frontEndUrl.$entity['bigFrontImageUrl'];
         }

     }

    
    $this->_response->setHash(array_values($returnEntities));
    
    
  }


  protected function getEntitiesOfNode($node,$query)
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




