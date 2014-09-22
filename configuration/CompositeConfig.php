<?php 

class CompositeConfig
{
	
  public function __construct()
  {
    $this->mongoDbConfig = new \PivoleUndPavoli\MongoDbConfig();
  }

	
  public function getShardId()
  {
    return $this->shardId;  
  }
  
  public function getShardUrl()
  {
    return $this->shardUrl;  
  }
  
  public function getSubNodes()
  {
      return $this->subNodes;
  }

  public function getFrontEndUrls()
  {
  	return $this->frontEndUrls;
  }

  public function getElasticSearchHost()
  {
  	return $this->elasticSearchHost;
  }

  public function getMongoDbConfig()
  {
    return $this->mongoDbConfig;
  }

  public function setMongoDbConfig($val)
  {
    $this->mongoDbConfig = $val;
  }
 
  public function getApplicationId()
  {
    return $this->applicationId;
  } 
  

  public function getFacebookAppId()
  {
      return $this->facebookAppId;
  }
  
  public function getFaceBookAppSecret()
  {
      return $this->facebookAppSecret;
  }
  
  public function getFacebookConfig()
  {
      return array(
            'appId' => $this->getFacebookAppId(),
            'secret' => $this->getFacebookAppSecret()
        );
  }


}