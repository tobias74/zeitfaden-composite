<?php 
class UserController extends AbstractCompositeController
{
	
  
  	protected function facebookIntroducer_Only_For_Main_Node()
    {
      try
      {
        if ($facebookUserId == false)
        {
          throw new ZeitfadenException("no facebookUserId");
        }
        
        try
        {
          $loggedInUser = $this->getControllerService()->getUserByFacebookUserId($facebookUserId);
          $loggedInUserId = $loggedInUser->getId();
          $userSession = new FacebookUserSession();
        }
        catch (ZeitfadenException $e)
        {
          $loggedInUser = $this->zeitfadenFacade->introduceUserWithFacebookUserId($facebookUserId);
          $loggedInUserId = $loggedInUser->getId();
          $userSession = new FacebookUserSession();
        }
        
        

      }
      catch (ZeitfadenException $e)
      {
        $loggedInUserId = 0;
        $userSession = new AnonymousUserSession();
      }
    }


  protected function getEntityDataByRequest($request)
  {
    $userId = $request->getParam('userId',0);
    $userData = $this->getUserDataById($userId);
    return $userData;

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
	
  
  
  public function uploadAttachmentAction()
  {
    $userId = $this->_request->getParam('userId',$this->getLoggedInUser()->getId());
    $user = $this->getService()->getUserById($userId, $this->getLoggedInUser()->getId());

    $file = new UserFile();
    $user->setAttachment($file);
    $this->workUploadedFile($this->_request->getUploadedFile('inputImage'), $user);
    
    $this->getService()->mergeUser($user);

  }
  
	
	
	public function getMyAccountDataAction()
	{
		$email = $this->_request->getSessionVar('email','');
		echo "email is ".$email;
		$data = $this->getCompositeService()->getAccountDataBasedOnEmail($email);
		print_r($data);
		die('end');
		$this->_response->setHash(array());

	}

	
	public function registerAction()
	{
		$inputEmail = $this->_request->getParam('email','');
		$inputPassword = $this->_request->getParam('password','');
		if (!$this->isEmailRegistered($inputEmail))
		{
			$shardData = $this->getShardingService()->getLeastUsedShard();
		    $shardUrl = $shardData['url'];
		    $shardId = $shardData['shardId'];

    		$this->passCurrentRequestToShardUrl($shardUrl);

		    $responseHash = $this->_response->getHash();
		    $userId = $responseHash['user']['id'];

		    $values = $this->getShardingService()->assignUserToShard($userId,$shardId);
		    $this->_response->appendValue('shardRequestStatus',$values['status']);
			
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
		$this->passToMyShard();
	}
	
	
	public function getByIdAction()
	{
		$userId = $this->_request->getParam('userId',0);
		$user = $this->getService()->getUserById($userId);
		
		$userDTO = $this->getService()->getUserDTO($user);
		$this->_response->appendValue('user', $userDTO);
		
		
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

    protected function getShardForUserId($userId)
	{
		// call the shardmaster here.
	    $url = 'http://shardmaster.butterfurz.de/shard/getShardForUser/userId/'.$userId.'/applicationId/'.$this->getApplicationId();
	    die($url);
	    $r = new HttpRequest($flyUrl, HttpRequest::METH_GET);
	    $r->send();
	    
	    $values = json_decode($r->getResponseBody(),true);



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


  
	
	public function assignToGroupsAction()
	{
		$friendId = $this->_request->getParam('userId','');
		$groupIds = $this->_request->getParam('groupIds',array());
		
		$friend = $this->getService()->getUserById($friendId);
		$this->removeFriendFromAllGroups($friend);
		
		foreach ($groupIds as $groupId)
		{
			$group = $this->getService()->getGroupById($groupId, $this->getLoggedInUser()->getId());
			$group->addFriend($friend);
			$this->getService()->mergeGroup($group);
		}

		$friendDTO = $this->getService()->getDTOBySpecification($friend);				
		$this->_response->appendValue('user', $friendDTO);
		
	}
	
	
	
	protected function removeFriendFromAllGroups($friend)
	{
		$allGroups = $this->getService()->getAllMyGroups();
		foreach ($allGroups as $group)
		{
			if ($group->isFriend($friend))
			{
				$group->removeFriend($friend);
				$this->getService()->mergeGroup($group);
			}
		}
	}
	
	
	public function getByGroupAction()
	{
		$groupId = $this->_request->getParam('groupId','');

		$limiter = new Limiter($this->_request->getParam('offset',0),
							   $this->_request->getParam('length',100));
							   
		$userOrderer = $this->userOrderer->byAscendingId();
		$userOrderer = new \BrokenPottery\SingleAscendingOrderer('startDate');
		try
		{
			$group = $this->getService()->getGroupById($groupId, $this->getLoggedInUser()->getId());
			$userCriteria = $this->userCriteria->none();
			
			foreach ($group->getAssignedFriendsIds() as $friendId)
			{
				$userCriteria = $userCriteria->logicalOr( $this->userCriteria->hasId($friendId) );
			}
		}
		catch (ZeitfadenNoMatchException $e)
		{
			// we did not find the given group, we ignore that.
			$userCriteria = $this->userCriteria->any();
		}
		
		$userCriteria = $this->appendDisplaySpecification($this->_request, $userCriteria);
				
		$spec = new Specification($userCriteria, $userOrderer, $limiter);
		$users = $this->getService()->getUsersBySpecification($spec);
		
		
		$userDTOs = $this->getService()->getUserDTOs($users);
		
		$this->_response->appendValue('allGroups', $this->getService()->getGroupsDTOs( $this->getService()->getAllMyGroups() ) );
		$this->_response->appendValue('users', $userDTOs);
		
		
		$ownerDTO = $this->getService()->getUserDTO($this->getLoggedInUser());				
		$this->_response->appendValue('owner', $ownerDTO);
		
		
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



	
	
	
	
	
	
	
	

  public function getByQueryAction()
  {
    $query = $this->_request->getParam('query', 'missing query');

    $useEngine = $this->_request->getParam('useEngine', 'native');
    
    $url = 'http://query-interpreter.zeitfaden.com/query/translateQuery/query/'.urlencode($query);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    switch ($useEngine)
    {
      case 'elastic':
        throw new ErrorException('not impleeted yet elastic user search');        
        break;

      default:      

        $nodes = $this->getCompositeService()->getSubNodes();
    
        $returnEntities = array();
        
        foreach ($nodes as $node)
        {
          $returnEntities = array_merge($returnEntities, $this->getEntitiesOfNodeByQuery($node,$query));
        }
    
        $returnEntities = $this->sortEntitiesByQuery($returnEntities, $values);
        
        break;
    }   
    

    $returnEntities = $this->attachLoadBalancedUrls($returnEntities);
    
    $this->_response->setHash(array_values($returnEntities));
  }


  protected function getEntitiesOfNodeByIds($node,$ids)
  {
    $url = $node.'/user/getByIds/';
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addQueryData(array('userIds' => $ids));
    $r->addCookies($_COOKIE);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    return $values;
  }


  protected function getEntitiesOfNodeByQuery($node,$query)
  {
    $url = $node.'/getUsersByQuery/'.urlencode($query);
    //die($url);
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->addCookies($_COOKIE);
    $r->send();

    $values = json_decode($r->getResponseBody(),true);
    
    return $values;
  }

	
	
}