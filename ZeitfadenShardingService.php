<?php 

class ZeitfadenShardingService
{
  public function __construct()
  {
    $this->cachedByUserId = array();
  }

  public function setApplicationId($val)
  {
    $this->applicationId = $val;
  }

  public function getApplicationId()
  {
    return $this->applicationId;
  }
  
  public function setDatabase($val)
  {
    $this->database = $val;
  }
  
  protected function getMongoDatabase()
  {
    return $this->database->getMongoDbService();
  }

  protected function getUserToShardCollection()
  {
    return new MongoCollection($this->getMongoDatabase(), 'users_to_shards');
  }

  protected function getShardCollection()
  {
    return new MongoCollection($this->getMongoDatabase(), 'shards');
  }
  
  public function getShardByUserId($userId)
  {
    if (!isset($this->cachedByUserId[$userId]))
    {
      $assoc = $this->getUserToShardCollection()->findOne(array(
        'userId' => $userId
      ));
      
      if ($assoc === null)
      {
        throw new ZeitfadenNoMatchException('did not find the shard');
      }
      
      $shard = $this->getShardCollection()->findOne(array(
        'shardId' => $assoc['shardId']
      ));
      
      if ($shard === null)
      {
        throw new \ErrorException('did not find shard by ID, that is strange.');        
      }
      
      $this->cachedByUserId[$userId] = $shard;
    }
    return $this->cachedByUserId[$userId];
  }
    


  public function assignUserToShard($userId,$shardId)
  {
    $status = $this->getUserToShardCollection()->insert(array(
      'userId' => $userId,
      'shardId' => $shardId
    ));
    
    if ($status === false)
    {
      throw new \ErrorException('assigning user to shard did fail '.$userId.' ... '.$shardId);
    }
  }



    
  
  public function getLeastUsedShard()
  {
    $cursor = $this->getShardCollection()->find(array());
    
    $shards = array();
    foreach ($cursor as $doc){
      $shards[] = $doc;
    }    
    
    shuffle($shards);
    
    return $shards[0];

  }



  
}




