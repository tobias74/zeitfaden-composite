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

  
  public function getShardByUserId($userId)
  {
    if (!isset($this->cachedByUserId[$userId]))
    {
      $url = 'http://shardmaster.zeitfaden.com/shard/getShardForUser/userId/'.$userId.'/applicationId/'.$this->applicationId;
      $r = new HttpRequest($url, HttpRequest::METH_GET);
      $r->send();
      
      $values = json_decode($r->getResponseBody(),true);
      if ($values === null)
      {
        throw new ZeitfadenNoMatchException('did not find the shard');
      }
      
      $shard = array();
      $shard['shardId'] = $values['shard']['shardId'];
      $shard['shardUrl'] = $values['shard']['url'];
      $this->cachedByUserId[$userId] = $shard;
    }
    return $this->cachedByUserId[$userId];
  }
    
    
  
  public function getLeastUsedShard()
  {
    $url = 'http://shardmaster.zeitfaden.com/shard/getLeastUsedShard/applicationId/'.$this->getApplicationId();
    $r = new HttpRequest($url, HttpRequest::METH_GET);
    $r->send();
    $values = json_decode($r->getResponseBody(),true);
    //$shardUrl = $values['shard']['url'];
    //$shardId = $values['shard']['shardId'];
    return $values['shard'];

  }


  public function assignUserToShard($userId,$shardId)
  {
      $url = 'http://shardmaster.zeitfaden.com/shard/assignUserToShard/shardId/'.$shardId.'/userId/'.$userId.'/applicationId/'.$this->getApplicationId();
      $r = new HttpRequest($url, HttpRequest::METH_POST);
      $r->send();
      $values = json_decode($r->getResponseBody(),true);
      return $values;
  }

  
}




