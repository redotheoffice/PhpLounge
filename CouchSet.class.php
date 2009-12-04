<?php

/**
 * Iterates over a list of documents
 *
 * @package default
 * @author The Young Shepherd
 **/
class CouchSet extends CouchData implements Iterator, Countable
{
  protected
    $source = null; // CouchQueryable source of listing
    
  public function __construct(CouchQueryable $source, $data)
  {
    $this->source = $source;
    parent::__construct($data, true); // create a read only document
  }
  
  /**
  * A switch to keep track of the end of the array
  */
  protected
    $index = 0;
  
  /**
  * Return the array "pointer" to the first element
  * PHP's reset() returns false if the array has no elements
  */
  function rewind(){
    $this->index = 0;
  }
  
  /**
  * Return the current array element
  */
  function current(){
    return $this->data['rows'][$this->index];
  }
  
  /**
  * Return the key of the current array element
  */
  function key(){
    return $this->index + $this->data['offset'];
  }
  
  /**
  * Move forward by one
  */
  function next(){
    $this->index++;
  }
  
  /**
  * Is the current element valid?
  */
  function valid(){
    return $this->data['total_rows'] > $this->index + $this->data['offset'];
  }
  
  public function count()
  {
    return count($this->data['rows']);
  }

  /**
   * implements array access, forwards numeric keys to the rows to ease access
   *
   * @return void
   * @author The Young Shepherd
   **/
  public function offsetGet($key)
  {
    if (is_numeric($key) && isset($this[$key]))
    {
      return $this->data['rows'][(int)$key];
    }
    else
    {
      return parent::offsetGet($key);
    }
  }
  
  public function offsetUnset($key)
  {
    if (is_numeric($key) && isset($this[$key]))
    {
      unset($this->data['rows'][(int)$key]);
    }
    else
    {
      parent::offsetUnset($key);
    }
  }

  public function offsetExists($key)
  {
    if (is_numeric($key))
    {
      return isset($this->data['rows'][(int)$key]);
    }
    else
    {
      return parent::offsetExists($key);
    }
  }
  
  public function offsetSet($key, $value)
  {
    if (is_numeric($key))
    {
      $this->data['rows'][(int)$key] = $value;
    }
    else
    {
      parent::offsetSet($key, $value);
    }
  }
}