<?php

class sfSphinxPager extends sfPager
{
  protected
    $peer_method_name       = 'retrieveByPKsJoinAll', // FIXME: change to retrieveByPKs
    $peer_count_method_name = 'doCount',
    $keyword = null,
    $sphinx = null;
    //$res = null;
    

  public function __construct($class, $maxPerPage = 10)
  {
    parent::__construct($class, $maxPerPage);

    $this->tableName = constant($this->getClassPeer().'::TABLE_NAME');
    $options = array(
	    'limit'   => $maxPerPage,
	    'offset'  => 0,
	  	'mode'    => sfSphinxClient::SPH_MATCH_EXTENDED,
	    'weights' => array(100, 1, 10), // FIXME: change the weight
	    'sort'    => sfSphinxClient::SPH_SORT_EXTENDED,
	    'sortby'  => '@weight DESC',
	  );
	  $this->sphinx = new sfSphinxClient($options);
  }

  public function init()
  {	 
	$hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
    $maxRecordLimit = $this->getMaxRecordLimit();

    $res = $this->sphinx->Query($this->keyword, $this->tableName);
    $count = $res["total_found"];

    $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

    if (($this->getPage() == 0 || $this->getMaxPerPage() == 0))
    {
      $this->setLastPage(0);
    }
    else
    {
      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

      if ($hasMaxRecordLimit)
      {
        $maxRecordLimit = $maxRecordLimit - $offset;
        if ($maxRecordLimit > $this->getMaxPerPage())
        {
          $limit = $this->getMaxPerPage();
        }
        else
        {
          $limit = $maxRecordLimit;
        }
      }
      else
      {
        $limit= $this->getMaxPerPage();
      }
      $this->sphinx->SetLimits($offset, $limit);
    }
  }

	protected function retrieveObject($offset)
  {
		$this->sphinx->SetLimits($offset - 1, 1);
		
  	$res = $this->sphinx->Query($this->keyword, $this->tableName);
  	if ($res['total_found'])
  	{
  		$ids = array();
		  foreach ($res['matches'] as $match)
		  {
		    $ids[] = $match['id'];
		  }
	
	    $results = call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $ids);
	    return is_array($results) && isset($results[0]) ? $results[0] : null;
  	}
  	else
  	{
  		return null;
  	}
  }

  public function getResults()
  {
  	$res = $this->sphinx->Query($this->keyword, $this->tableName);
  	if ($res['total_found'])
  	{
  		$ids = array();
		  foreach ($res['matches'] as $match)
		  {
		    $ids[] = $match['id'];
		  }
	
	    return call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $ids);
  	}
  	else
  	{
  		return array();
  	}
    
  }

  public function getPeerMethod()
  {
    return $this->peer_method_name;
  }

  public function setPeerMethod($peer_method_name)
  {
    $this->peer_method_name = $peer_method_name;
  }

  public function getPeerCountMethod()
  {
    return $this->peer_count_method_name;
  }

  public function setPeerCountMethod($peer_count_method_name)
  {
    $this->peer_count_method_name = $peer_count_method_name;
  }

  public function getClassPeer()
  {
    return constant($this->class.'::PEER');
  }
  
	public function setKeyword($k)
  {
    $this->keyword = $k;
  }
  
  /**
   * A proxy for Sphinx::SetSortMode()
   * set sort mode
   * @param integer $mode
   * @param string  $sortby
   */
  public function setSortMode($mode, $sortby = '')
  {
  	$this->sphinx->SetSortMode($mode, $sortby);
  }
  
  /**
   * A proxy for Sphinx::SetFilter()
   * set values set filter
   * only match records where $attribute value is in given set
   * @param string  $attribute
   * @param array   $values
   * @param boolean $exclude
   */
  public function setFilter($attribute, $values, $exclude = false)
  {
    $this->sphinx->SetFilter($attribute, $values, $exclude);
  }
  
  /**
   * set range filter
   * only match those records where $attribute column value is beetwen $min and $max
   * (including $min and $max)
   * @param string  $attribute
   * @param integer $min
   * @param integer $max
   * @param boolean $exclude
   */
  public function setFilterRange($attribute, $min, $max, $exclude = false)
  {
  	$this->sphinx->SetFilterRange($attribute, $min, $max, $exclude);
  }
  
}
