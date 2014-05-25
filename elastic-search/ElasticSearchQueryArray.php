<?php
namespace Zeitfaden\ElasticSearch;


class ElasticSearchQueryArray 
{
  //
  protected $whereClause;
  protected $clauseParts = array();
  
  
  public function __construct($context = null)
  {
    $this->context = $context;
  }
  

  protected function getTranslatedValue($column,$value)
  {
    switch ($column)
    {
      case 'zuluStartDateString':
      case 'zuluEndDateString':
        $date = \DateTime::createFromFormat('Y-m-d H:i:s',$value);
        return $date->format('Y-m-d').'T'.$date->format('H:i:s');
        break;

      default:
        return $value;         

    }
  }

  public function visitAndCriteria($andCriteria)
  {
    $firstArray = $this->getArrayForCriteria($andCriteria->getFirstCriteria());
    $secondArray = $this->getArrayForCriteria($andCriteria->getSecondCriteria());
   
    $whereArray = array('and' => array(
      $firstArray,
      $secondArray
    ));
      
                  
    $this->setArrayForCriteria($andCriteria, $whereArray);
        
  }
  
  public function visitOrCriteria($orCriteria)
  {
    //
    $firstArray = $this->getArrayForCriteria($orCriteria->getFirstCriteria());
    $secondArray = $this->getArrayForCriteria($orCriteria->getSecondCriteria());
   
    $whereArray = array('or' => array(
      $firstArray,
      $secondArray
    ));
      
                  
    $this->setArrayForCriteria($orCriteria, $whereArray);
              
  }

  
  public function visitEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'term' => array(
        $column => $criteria->getValue()
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
  
      
  public function visitGreaterThanCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gt' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }


  public function visitGreaterOrEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gte' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }

  
  public function visitLessThanCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'lt' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
    
    
  public function visitLessOrEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'lte' => $this->getTranslatedValue($column, $criteria->getValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
        
    
  public function visitNotEqualCriteria($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
		'not' => array(
      		'term' => array(
      			$column => $criteria->getValue()
			)
      	)
    );
    
    $this->setArrayForCriteria($criteria, $comp);
  }
    
        
    
  public function visitCriteriaBetween($criteria)
  {
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getField());

    $comp = array(
      'range' => array(
        $column => array(
          'gt' => $this->getTranslatedValue($column, $criteria->getStartValue()),
          'lt' => $this->getTranslatedValue($column, $criteria->getEndValue())
        )
      )
    );
    
    $this->setArrayForCriteria($criteria, $comp);
        
  }
  
  public function visitNotCriteria($criteria)
  {
    $comp = array('not' => array(
      $this->getArrayForCriteria($criteria->getNestedCriteria()),
    ));
        
    $this->setArrayForCriteria($criteria, $comp);
  }
  
  
  public function visitWithinDistanceCriteria($criteria)
  {
    
    $mapper = $this->context;
    $column = $mapper->getColumnForField($criteria->getGeometryField());

    $comp = array(
      'geo_distance' => array(
        'distance' => floatval($criteria->getMaximumDistance()),
        $column => array(
          'lat' => floatval($criteria->getLatitude()),
          'lon' => floatval($criteria->getLongitude())
        )
      )
    );


    
    $this->setArrayForCriteria($criteria, $comp);
          
  }
  
  
  public function getArrayForCriteria($criteria)
  {
    return $this->clauseParts[$criteria->getKey()];
  }

  protected function setArrayForCriteria($criteria,$clause)
  {
    $this->clauseParts[$criteria->getKey()] = $clause;
  }
    
} 










