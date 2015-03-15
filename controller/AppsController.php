<?php

class AppsController extends AbstractZeitfadenController
{
  
  protected function getUniqueId()
  {
      $uid=uniqid();
      $uid.=rand(100000,999999);
      return $uid;
  }
  
  protected function generateRandomString($length = 10) 
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }

  
  public function setDatabase($val)
  {
    $this->database = $val;
  }
  
  protected function getMongoDatabase()
  {
    return $this->database->getMongoDbService();
  }

  protected function getAppsCollection()
  {
    return new MongoCollection($this->getMongoDatabase(), 'oauth_clients');
  }
  
  protected function generateClientId()
  {
    return $this->getUniqueId();
  }

  protected function findById($appId)
  {
    $assoc = $this->getAppsCollection()->findOne(array(
      '$and' => array(
        array('client_id' => $appId),
        array('appOwner' => $this->getLoggedInUserId())
      )
    ));
    
    return $assoc;
  }


  protected function generateClientSecret()
  {
    return $this->generateRandomString(40);
  }
  
  protected function mapAppForResponse($appHash)
  {
    return array(
      'appOwner' => $appHash['appOwner'],
      'appName' => $appHash['appName'],
      'appDescription' => $appHash['appDescription'],
      'appId' => $appHash['client_id'],
      'appSecret' => $appHash['client_secret']
    );
  }
  
  public function createAction()
  {
    $this->needsLoggedInUser();
    
    $appHash = array(
      'appOwner' => $this->getLoggedInUserId(),
      'appName' => $this->_request->getParam('appName',''),
      'appDescription' => $this->_request->getParam('appDescription',''),
      'client_id' => $this->generateClientId(),
      'client_secret' => $this->generateClientSecret()
    );
    $this->getAppsCollection()->insert($appHash);

    $this->_response->setHash($this->mapAppForResponse($appHash));

  }
  
  
  public function getAction()
  {
    $this->needsLoggedInUser();

    $this->_response->setHash($this->mapAppForResponse($this->findById($this->_request->getParam('appId',''))));
  }
  
  
  
  public function updateAction()
  {
    $this->needsLoggedInUser();
    
    
    $where = array('client_id' => $this->_request->getParam('appId',''));
    
    $appHash = $this->findById($this->_request->getParam('appId',''));

    if ($this->_request->hasParam('appName'))
    {
      $appHash['appName'] = $this->_request->getParam('appName','');
    }

    if ($this->_request->hasParam('appDescription'))
    {
      $appHash['appDescription'] = $this->_request->getParam('appDescription','');
    }
    
    $this->getAppsCollection()->update($where, $appHash, array('upsert'=>false));

    $this->_response->setHash($this->mapAppForResponse($this->findById($this->_request->getParam('appId',''))));
    
  }


  public function deleteAction()
  {
    $this->needsLoggedInUser();
    
    
    $where = array(
      'client_id' => $this->_request->getParam('appId',''),
      'appOwner' => $this->getLoggedInUserId()      
    );
    
    $this->getAppsCollection()->remove($where);
    
  }


  
  
}
