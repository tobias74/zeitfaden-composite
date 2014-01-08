<?php

class CompositeConfigLoader
{

	public function __construct()
	{

	}

	public function setConfigurationService($val)
	{
		$this->configurationService = $val;
	}

	public function getConfigurationService()
	{
		return $this->configurationService;
	}

	public function getNewConfigInstance()
	{
	    return new CompositeConfig();
	}


  public function loadConfiguration($hostUrl,$applicationId, $configInstance )
  {
  	$config = $configInstance;
    $config->mongoDbConfig->dbName = $applicationId;
    $config->mongoDbConfig->serverUrl = '';
  	

    switch ($hostUrl)
    {
      case "test.zeitfaden.de":
      case "test.zeitfaden.com":
      case "srv_1_test.zeitfaden.com":
      case "srv_2_test.zeitfaden.com":
      case "srv_3_test.zeitfaden.com":
      case "srv_4_test.zeitfaden.com":
        
        $config->subNodes = array(
          'http://test.db-shard-one.zeitfaden.com',
          'http://test.db-shard-two.zeitfaden.com'
        );

        break;
  
      case "www.zeitfaden.de":
      case "www.zeitfaden.com":
        die('in application, no live yet.');
        break;
        
      case "live.zeitfaden.com":
      case "live.zeitfaden.de":
      case "livetest.zeitfaden.de":
      case "livetest.zeitfaden.com":
      case "srv_1_live.zeitfaden.com":
      case "srv_2_live.zeitfaden.com":
      case "srv_3_live.zeitfaden.com":
      case "srv_4_live.zeitfaden.com":
        $config->subNodes = array(
          'http://live.db-shard-one.zeitfaden.com',
          'http://live.db-shard-two.zeitfaden.com'
        );
                
        break;
        
      default:
        throw new \ErrorException("no configuration for this domain: ".$hostUrl);
        break;
        
    }


    return $config;
  }





}
