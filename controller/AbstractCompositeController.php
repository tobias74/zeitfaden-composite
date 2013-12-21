<?php 
use SugarLoaf as SL;

abstract class AbstractCompositeController extends AbstractZeitfadenController
{

  protected function getUniqueId()
  {
      $uid=uniqid();
      $uid.=rand(100000,999999);
      return $uid;
  }
  

  public function setApplicationId($val)
  {
    $this->applicationId = $val;
  }

  public function getApplicationId()
  {
    return $this->applicationId;
  }

  public function setCompositeService($val)
  {
    $this->compositeService = $val;
  }

  public function getCompositeService()
  {
    return $this->compositeService;
  }



  public function setShardingService($val)
  {
    $this->shardingService = $val;
  }

  public function getShardingService()
  {
    return $this->shardingService;
  }

	
  
  	



  protected function passToMyShard()
  {
    $shardUrl = $this->getHomeShardUrl();
    $this->passCurrentRequestToShardUrl($shardUrl);
  }



  protected function getHomeShardUrl()
  {
    $userId = $this->getUserSession()->getLoggedInUserId();
    $homeShard = $this->getCompositeService()->whereLivesUserById($userId);

    return $homeShard['shardUrl'];

  }


  protected function passCurrentRequestToShardUrl($shardUrl)
  {
    session_write_close();

    $methods = array(
      'POST' => HttpRequest::METH_POST,
      'GET' =>HttpRequest::METH_GET,
      'PUT' => HttpRequest::METH_PUT,
      'DELETE' => HttpRequest::METH_DELETE
    );

    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    $request = new HttpRequest('http://'.$shardUrl.$requestUri, $methods[$requestMethod]);

    switch ($requestMethod)
    {
      case 'POST':
        $request->setPostFields($_POST);
        foreach ($_FILES as $paramName => $file)
        {
          $request->addPostFile($paramName, $file['tmp_name'], $file['type']);
        }
        break;

      case 'PUT':
        $request->setPutFields($_PUT);
        foreach ($_FILES as $paramName => $file)
        {
          $request->addPutFile($paramName, $file['tmp_name'], $file['type']);
        }
        break;


    }


    $request->addCookies($_COOKIE);
    $request->addQueryData($_GET);

    $request->send();
    $values = json_decode($request->getResponseBody(),true);

    foreach ($values as $name => $value)
    {
      $this->_response->appendValue($name,$value);
    }

    $r = $request;

    foreach ($r->getResponseHeader() as $header)
    {
      $this->_response->addHeader($header); 
    }

    foreach ($r->getResponseCookies() as $cookie)
    {
      $this->_response->addHeader('Set-Cookie: '.http_build_cookie((array)$cookie));
    }




  }



  protected function getShardByUserId($userId)
  {
    $values = $this->getShardingService()->getSHardDataByUserId($userId);
    if ($values['status'] == 'not_found')
    {
      error_log('userid '.$userId.' was not found in the shardmaster, brute forcing it now...');
      $response = $this->getCompositeService()->whereLivesUserById($userId);
  
      return array(
        'shardId' => $response['shardId'],
        'shardUrl' => $response['shardUrl']
      );
    }
    else
    {
      return array(
        'shardId' => $values['shard']['url'],
        'shardUrl' => $values['shard']['shardId']
      );
  
    }
  
  }
  
  
  protected function getStationDataById($stationId, $userId)
  {
    $shardData = $this->getShardByUserId($userId);
    $url = 'http://'.$shardData['shardUrl'].'/station/getById/stationId/'.$stationId.'/userId/'.$userId.'/applicationId/'.$this->getApplicationId();
    
    //echo $url;
    
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    
    $returnValues = $values['station'];
    
    $returnValues['shardUrl'] = $shardData['shardUrl'];

    return $returnValues;
  
  }
  
  
  protected function performQuery($requestPath)
  {
    return $this->getCompositeService()->performQuery($requestPath);
  }
  	
	
  public function xxx_still_used_getVideoUrl($item)
  {
    return "http://".$_SERVER['HTTP_HOST']."/station/serveAttachment/stationId/".$item->getId()."/userId/".$item->getUserId()."";
  }
  
  
	protected function declareActionsThatNeedLogin()
	{
		return array();
	}
	
	
	
	public function getRequestParameter($name,$default)
	{
		return $this->_request->getParam($name,$default);
	}
	
	
	
  
  
  
}








