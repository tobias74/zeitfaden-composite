<?php
namespace Zeitfaden\ElasticSearch;


class ElasticSearchOrderArray
{
  protected $orderClause;
  protected $clauseParts = array();
  
  
  public function __construct($context = null)
  {
    $this->context = $context;
  }
  
  public function visitChainedOrderer($chainedOrderer)
  {
    throw new \ErrorException('not implemented yet');
    /*
    $firstClause = $this->getClauseForOrderer($chainedOrderer->getFirstOrderer());
    $secondClause = $this->getClauseForOrderer($chainedOrderer->getSecondOrderer());
                  
    $orderClause = "  ".$firstClause. " , ".$secondClause. "  ";
    $this->setClauseForOrderer($chainedOrderer, $orderClause);
    */    
  }
  
  
  public function visitSingleOrderer($singleOrderer)
  {
    $column = $this->context->getColumnForField($singleOrderer->getField());

    $sortHash = array(
      $column => array(
        'order' => strtolower($singleOrderer->getDirection()),
        'ignore_unmapped' => true
      )
    );

    $this->setArrayForOrderer($singleOrderer, $sortHash);
  }


  public function visitDistanceToPinOrderer($orderer)
  {
    $column = $this->context->getColumnForField($orderer->getField());
            
	$direction = $orderer->getDirection();
	if (!in_array($direction, array('asc','desc')))
	{
		$direction = 'asc';
	}
	
    $sortHash = array(
      '_geo_distance' => array(
          $column => array(
         	'lat' => floatval($orderer->getLatitude()) , 
         	'lon' => floatval($orderer->getLongitude())
		  ),
        'order' => $direction,
        'unit' => 'km'
      ),
      'id' => array(
	  	'order' => 'asc'
	  )
    );
    

    $this->setArrayForOrderer($orderer, $sortHash);
  }

  
  
  
  public function getArrayForOrderer($orderer)
  {
    return $this->clauseParts[$orderer->getKey()];
  }

  protected function setArrayForOrderer($orderer,$clause)
  {
    $this->clauseParts[$orderer->getKey()] = $clause;
  }
    
} 












