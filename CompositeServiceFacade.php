<?php

class CompositeServiceFacade
{
	public function setConfig($val)
	{
	  $this->config = $val;
	}

	public function getConfig()
	{
	  return $this->config;
	}

	public function getSubNodes()
	{
	  return $this->getConfig()->getSubNodes();
	}

	public function setApplicationId($val)
	{
		$this->applicationId = $val;
	}

	public function getApplicationId()
	{
		return $this->applicationId;
	}

  public function setApplicationIni($val)
  {
    $this->applicationIni = $val;
  }
  
  public function getFrontEndUrls()
  {
    return $this->getConfig()->getFronEndUrls();
  }

  public function getProfiler()
  {
    return $this->profiler; 
  }
  
  public function setProfiler($val)
  {
    $this->profiler = $val; 
  }

  public function setShardingService($val)
  {
    $this->shardingService = $val;
  }
  
  public function getShardingService()
  {
    return $this->shardingService;
  }


  public function getRandomSubNode()
  {
    $nodes = $this->getSubNodes();
    $random = mt_rand(0, count($nodes)-1);
    return $nodes[$random];
  }
  
	protected function prepareUrls($commandPath)
	{
	  $preparedUrls = array();
	  foreach ($this->getSubNodes() as $subnode)
	  {
	    array_push($preparedUrls, $subnode.'/'.$commandPath);

	  }

	  return $preparedUrls;

	}
	  
	protected function fetchResults($urls,$postHash)
	{
	    $pool = new HttpRequestPool();

	  foreach ($urls as $url)
	    {
	    	$request = new HttpRequest($url);
	    	$request->addPostFields($postHash);
	      	$pool->attach($request);
	    }

	    $pool->send();

	    $shardResults = array();

	    foreach($pool as $request) 
	    {
	      $values = json_decode($request->getResponseBody(),true);
	      $shardResults[$request->getUrl()] = $values;
	    }

	    return $shardResults;

	}


	public function performQuery($requestPath,$postHash=array())
	{
	    $timer = $this->getProfiler()->startTimer('Request: '.$requestPath);
	    $urls = $this->prepareUrls($requestPath);
	    $results = $this->fetchResults($urls,$postHash);
      $timer->stop();
      
	    return $results;
	}



	public function whereLivesUserByEmail($email)
	{
		$requestPath = '/user/isEmailRegistered/email/'.$email.'/';

		$results = $this->performQuery($requestPath);

		foreach ($results as $url => $response)
		{
			if ($response['status'] === 'registered')
			{
				return $response;
			}
		}

    
		throw new ZeitfadenNoMatchException('email not found anywhere...');

	}


	public function whereLivesUserById($userId)
	{
		$requestPath = '/user/isUserIdRegistered/id/'.$userId.'/';

		$results = $this->performQuery($requestPath);

		foreach ($results as $url => $response)
		{
			if ($response['status'] === 'registered')
			{
				return $response;
			}
		}

		throw new ZeitfadenNoMatchException('userId -'.$userId.'- not found anywhere...');

	}


  public function getShardUrlById($shardId)
  {
    return $this->getShardingService()->getShardById($shardId)->url;
  }



	public function getAccountDataBasedOnEmail($email)
	{
		$data = $this->whereLivesUserByEmail($email);

    	$request = new HttpRequest('http://'.$data['shardUrl'].'/user/getMyAccountData/');
	    $request->send();
	    $values = json_decode($request->getResponseBody(),true);

	    return $values;
	}

	public function getUserByFacebookUserId($facebookUserId)
	{
		
	}

	public function introduceUserWithFacebookUserId($facebookUserId)
	{
		
	}


}



