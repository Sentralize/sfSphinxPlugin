<?php
/**
 * sfSphinx pager class
 * @package sfSphinxPlugin
 * @author  Hung Dao <hungdao@mahshelf.com>
 */

class sfSphinxPager extends sfPager
{
  protected
    $peer_method_name       = 'retrieveByPKs',
    $peer_count_method_name = 'doCount',
    $keyword                = null,
    $sphinx                 = null;

  /**
   * Constructor
   * @param object         $class
   * @param integer        $maxPerPage
   * @param sfSphinxClient $sphinx
   */
  public function __construct($class, $maxPerPage = 10, sfSphinxClient $sphinx)
  {
    parent::__construct($class, $maxPerPage);
    $this->sphinx = $sphinx;
  }

  /**
   * A function to be called after parameters have been set
   */
  public function init()
  {
    $hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
    $maxRecordLimit = $this->getMaxRecordLimit();

    $res = $this->sphinx->getRes();
    if ($res === false)
    {
      return;
    }

    $count = $res['total_found'];

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

  /**
   * Retrieve an object of a certain model with offset
   * used internally by getCurrent()
   * @param  integer $offset
   * @return object
   */
  protected function retrieveObject($offset)
  {
    $this->sphinx->SetLimits($offset - 1, 1); // We only need one object

    $res = $this->sphinx->getRes();
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

  /**
   * Return an array of result on the given page
   * @return array
   */
  public function getResults()
  {
    $res = $this->sphinx->getRes();
    if ($res['total_found'])
    {
      // First we need to get the Ids
      $ids = array();
      foreach ($res['matches'] as $match)
      {
        $ids[] = $match['id'];
      }
      // Then we retrieve the objects correspoding to the found Ids
      return call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $ids);
    }
    else
    {
      return array();
    }

  }

  /**
   * Return the peer method name.
   * @return string
   */
  public function getPeerMethod()
  {
    return $this->peer_method_name;
  }

  /**
   * Set the peer method name.
   * @param string $peer_method_name
   */
  public function setPeerMethod($peer_method_name)
  {
    $this->peer_method_name = $peer_method_name;
  }

  /**
   * Return the peer count method name. Default is 'doCount'
   * @return string
   */
  public function getPeerCountMethod()
  {
    return $this->peer_count_method_name;
  }

  /**
   * Set the peer count method name.
   * @param string $peer_count_method_name
   */
  public function setPeerCountMethod($peer_count_method_name)
  {
    $this->peer_count_method_name = $peer_count_method_name;
  }

  /**
   * Return the current class peer.
   * @return string
   */
  public function getClassPeer()
  {
    return constant($this->class.'::PEER');
  }

}
