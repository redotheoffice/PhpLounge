<?php

/**
* 
*/
class CouchRequestDocument extends CouchRequest
{
  public function __construct($id, $parameters = array(), CouchData $data = null)
  {
    parent::__construct("GET", $id, $parameters, $data);
  }
  
  public function buildObjectFromResponse(CouchResponse $response)
  {
    if (!$response->getSource() instanceof CouchDb)
    {
      throw new CouchException("Can only build a document object from a database source, a '"
        .get_class($response->getSource())."' source object was provided");
    }
    
    return CouchDocument::create($response->getSource(), $response->getBody());
  }
}
