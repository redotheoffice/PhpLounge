<?php

/**
* 
*/
class CouchRequestSet extends CouchRequest
{
  public function buildObjectFromResponse(CouchResponse $response)
  {
    return new CouchSet($response->getSource(), $response->getBody());
  }
}