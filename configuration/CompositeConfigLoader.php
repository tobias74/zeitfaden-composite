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


  public function loadConfiguration($iniConfiguration, $config )
  {
    $config->mongoDbConfig->dbName = $iniConfiguration['application_id'];
    $config->mongoDbConfig->serverUrl = 'services.zeitfaden.com';
    $config->redisHost = 'services.zeitfaden.com';
  	$config->subNodes = $iniConfiguration['sub_nodes'];
	$config->elasticSearchHost = $iniConfiguration['elastic_search_host'];
	
	$config->frontEndUrls = $iniConfiguration['front_end_urls'];



    return $config;
  }





}
