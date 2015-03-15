<?php 
class UserController extends AbstractCompositeEntityController
{
  protected $idName = 'userId';
  protected $controllerPath='user';
	

  
  protected function getEntitiesByIds($request)
  {
    return $this->getSearchStrategy($request)->getUsersByIds($request);
  }

  protected function getEntitiesByRequest($request)
  {
    return $this->getSearchStrategy($request)->getUsersByRequest($request);
  }



  protected function getMyEntityDataById($id)
  {
    return $this->getUserDataById($id);
  }

  private function getUserDataById($userId)
  {
    $shardData = $this->getShardDataByUserId($userId);
    $url = 'http://'.$shardData['shardUrl'].'/user/getById/userId/'.$userId;
    
    //echo $url;
    
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    
    $returnValues = $values['user'];
    
    $returnValues['shardUrl'] = $shardData['shardUrl'];

    return $returnValues;
  
  }




  public function showExportAction()
  {
    $this->passToMyShard();
  }
  
  
  public function requestExportAction()
  {
    $this->passToMyShard();
  }
  
  
  public function downloadExportAction()
  {
      
    $mongoDb = $this->database->getMongoDbService();
    $gridFS = $mongoDb->getGridFS();
    
    $gridFile = $gridFS->findOne(array('metadata.user_id' => $this->getLoggedInUserId(), 'metadata.content_description' => 'zeitfaden_export'));
    
    if (!$gridFile)
    {
      throw new \Exception('not found'); 
    }

    $this->_response->addHeader('Content-Length: '.$gridFile->getSize());
    $this->_response->setStream($gridFile->getResource());
    $this->_response->addHeader('Content-Disposition: attachment; filename="zeitfaden_archive_'.$this->getLoggedInUserId().'_at_'.$gridFile->file['metadata']['timestamp'].'"');

  }
  
  
  
  
  
  
  
  
	

  public function serveAttachmentAction()
  {
    $userId = $this->_request->getParam('userId',0);
    
    try
    {
      $entity = $this->getService()->adminGetUserById($userId);
    }
    catch (\Exception $e)
    {
      throw $e;
    }
    
    $this->produceAttachment($entity);
    
  }
	
  
  
  public function uploadProfileImageAction()
  {
    $this->passToMyShard();
  }
  
	
	public function getMyAccountDataAction()
	{
	  try
	  {
      $loggedInUserId = $this->_request->getSessionVar('loggedInUserId','');
      $userData = $this->getUserDataById($loggedInUserId);
      
      $this->_response->setHash($userData);
	  }
    catch (ZeitfadenNoMatchException $e)
    {
      $this->_response->setHash(array());
    }
    

	}

	
	public function registerAction()
	{
		$inputEmail = $this->_request->getParam('email','');
		$inputPassword = $this->_request->getParam('password','');
		if (!$this->isEmailRegistered($inputEmail))
		{
			$shardData = $this->getShardingService()->getLeastUsedShard();
		    $shardUrl = $shardData['shardUrl'];
		    $shardId = $shardData['shardId'];

    		$this->passCurrentRequestToShardUrl($shardUrl);

		    $responseHash = $this->_response->getHash();
		    $userId = $responseHash['user']['id'];

		    $this->getShardingService()->assignUserToShard($userId,$shardId);
		}
		else
		{
			throw new ZeitfadenException('emails is already taken. Composite. ', ZeitfadenApplication::STATUS_EMAIL_ALREADY_TAKEN);
		}
		
	    $this->_response->appendValue('in_composite', true);
    
	}
	
	
  public function loginStatusAction()
  {
      $this->_response->appendValue('loggedInUserId', $this->getUserSession()->getLoggedInUserId());
    
  }

  
  
	public function loginAction()
	{
		$email = $this->_request->getParam('email', '');
		$password = $this->_request->getParam('password','');

    error_log($email);
    error_log(print_r($_POST,true));

		$response = $this->getCompositeService()->whereLivesUserByEmail($email);
		// now we know whre the user live, now we log him into that.
		// the session will be updated there and be valid here, too.

		error_log('found the user in '.$response['shardUrl']);
    
		$this->passCurrentRequestToShardUrl($response['shardUrl']);
		
    
    $values = $this->_response->getHash();
        
		$this->_response->setHash($values);
    $this->_response->appendValue('originalResponseFromShard', $values);
    $userSession = new NativeUserSession();
    $userSession->setLoginPerformedByShard($response['shardUrl']);
    $userSession->setLoggedInUserId($values['loggedInUserId']);
    $this->getApplication()->setUserSession($userSession);
    
		
	}
	
  public function logoutAction()
  {
    error_log('logging out!');
    
    //$this->setAnonymousSession();
    $_SESSION['loginPerformedByShard'] = '';
    $_SESSION['loggedInUserId'] = '';
    $_SESSION['loginType'] = '';
    
    
    session_destroy();
  }

	
	
	


  protected function isEmailRegistered($email)
  {
    	$requestPath = '/user/isEmailRegistered/email/'.$email.'/';

    	$results = $this->performQuery($requestPath);

        $count = 0;
        foreach($results as $url => $values) 
        {
          if ($values['status'] == 'registered')
          {
            $count++;
          }
        }

        if ($count === 0)
        {
			return false;        
        }
        else if ($count === 1)
        {
        	return true;
        }
        else
        {
        	throw new ErrorException('found too many shards for email');
        }


  }

	public function isEmailRegisteredAction()
	{

    	$email = $this->_request->getParam('email','');

        try
        {
	        if ($this->isEmailRegistered($email))
	        {
	          $this->_response->appendValue('email',$email);
	          $this->_response->appendValue('status','registered');
	        }
	        else
	        {
	          $this->_response->appendValue('email',$email);
	          $this->_response->appendValue('status','unknown');
	        }
        }
        catch (ErrorException $e)
        {
          $this->_response->appendValue('email',$email);
          $this->_response->appendValue('status','error');
          $this->_response->appendValue('message',$e->getMessage());
        }

	}


	protected function searchShardForUserId($userId)
	{
		$requestPath = '/user/existsId/userId/'.$userId.'/';
    	$results = $this->performQuery($requestPath);

        $count = 0;
        foreach($results as $url => $values) 
        {
          if ($values['status'] == 'found')
          {
          	$shardId = $values['shardId'];
          	$shardUrl = $url;
            $count++;
          }
        }

        if ($count === 0)
        {
          $this->_response->appendValue('userId',$userId);
          $this->_response->appendValue('status','unknown');
        }
        else if ($count ===1)
        {
          $this->_response->appendValue('userId',$userId);
          $this->_response->appendValue('status','found');
          $this->_response->appendValue('shardId',$shardId);
          $this->_response->appendValue('shardUrl',$shardUrl);
        }
        else
        {
          $this->_response->appendValue('userId',$userId);
          $this->_response->appendValue('status','error');
          $this->_response->appendValue('count',$count);
        }

	}


  
	
	
	
	
	
	
	
	
	protected function appendDisplaySpecification($request, $criteria)
	{
		if ($request->getParam('mustHaveProfileImage',false) == true)
		{
			$criteria = $criteria->logicalAnd($this->userCriteria->mustHaveFile());
		}
		
		return $criteria;
	}
		
	public function deleteAttachmentAction()
	{
		$userId = $this->_request->getParam('userId','');
		$user = $this->getService()->getUserById($userId);
		$user->deleteAllAttachments();
		$this->getService()->mergeUser($user);
	}



	



	
	
}