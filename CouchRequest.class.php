<?php

/**
* 
*/
class CouchRequest
{
  protected
    $query = null,
    $parameters = null,
    $method = "get",
    $data = null;
  
  public function __construct($method = "GET", $query = "", $parameters = array(), CouchData $data = null)
  {
    $this->method = $method;
    $this->query = $query;

    if ($parameters instanceof CouchData)
    {
      $data = $parameters;
      $parameters = array();
    }

    $this->parameters = $parameters;
    $this->data = $data;
  }

  /**
   * builds the requested object. This method should be overridden without calling
   * parent::buildObjectFromResponse
   * extend this method to request different objects from the response
   *
   * @return object
   * @author The Young Shepherd
   **/
  public function buildObjectFromResponse(CouchResponse $response)
  {
    return new CouchData($response->getBody(), true);
  }
    
  public function getQuery()
  {
    return $this->query;
  }
  
  public function setQuery($value)
  {
    $this->query = $value;
  }
  
  public function getParameters()
  {
    return $this->parameters;
  }
  
  public function setParameters(array $value)
  {
    $this->parameters = $value;
  }
  
  public function getMethod()
  {
    return $this->method;
  }
  
  public function setMethod($value)
  {
    $this->method = $value;
  }
  
  public function getData()
  {
    return $this->data;
  }

  public function setData(CouchData $value)
  {
    $this->data = $value;
  }

  public function send(CouchQueryable $object)
  {
    return $object->send($this);
  }
}
