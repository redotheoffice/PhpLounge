<?php

/**
* Handles a response of a Couch object
*/
class CouchResponse
{
  protected
    $request = null,  //the request to which this is the response
    $response = null, // Zend_Http_Response
    $source = null,   // Couch Queryable
    $object = null;   // CouchData, the data returned

  /**
   * send sends a couchrequest to the source and returns the couchresponse 
   *
   * @throws CouchResponseErrorException If the response is was not succesful
   * @return CouchResponse The reponse
   * @author The Young Shepherd
   **/
  protected function send()
  {
    $source = $this->source instanceof CouchConnection ? $this->source : $this->source->getConnection();
    $client = $source->getHttpClient();
    $client->setUri($source->getUri($this->request->getQuery()));

    if ($this->request->getParameters())
    {
      $queryParams = $this->request->getParameters();
      foreach ($queryParams as $key => $value)
      {
        if (is_bool($value))
        {
          $queryParams[$key] = ($value) ? 'true' : 'false';
        }
      }
      $client->setParameterGet($queryParams);
    }
    $client->setRawData((string)$this->request->getData());
    
    if (sfConfig::get('sf_logging_enabled'))
    {
      sfContext::getInstance()->getLogger()->info(sprintf(
        "CouchRequest sent to %s with data %s",
        $client->getUri(),
        print_r((string)$this->request->getData(),true)
        ));
    }

    $this->response = $client->request($this->request->getMethod());
  }
  
  public function __construct(CouchQueryable $source, CouchRequest $request)
  {
    $this->source = $source;
    $this->request = $request;    
    $this->send();
    $this->checkSuccess();
  }
    
  /**
   * checks if the request was succesful, if not it throws a CouchException
   *
   * @return void
   * @throws CouchException The exception thrown by the server
   * @author The Young Shepherd
   **/
  protected function checkSuccess()
  {
    if (!$this->response->isSuccessful())
    {
      $responseBody = CouchData::makeArray($this->response->getBody());
      
      $error = isset($responseBody['error']) ? $responseBody['error']  : 'Unknown';
      // camelize the error
      $error = implode('', array_map('ucfirst',explode('_',$error)));
      
      $class = 'Couch'.$error.'Exception';
      if (!class_exists($class))
      {
        $class = 'CouchException';
      }
      
      $code = $this->response->getStatus();
      $message = strtr("Received error (%status%): %error% (%reason%).",
          array(
              '%status%'    => $code,
              '%error%'     => $error,
              '%reason%'    => isset($responseBody['reason']) ? $responseBody['reason'] : 'Unknown reason',
          ));

      throw new $class($message, $code, null, $this->getObject());
    }
  }

  /**
   * __call provides a basic way of decorating the Zend_HTTP_Response class
   *
   * @return mixed result of the call
   * @throws Exception if the method is not callable on the Zend_HTTP_Response object
   * @author The Young Shepherd
   **/
  public function __call($method, $args)
  {
    if (!method_exists($this->response, $method))
    {
      throw new CouchException(sprintf('Method "%s" not found', $method));
    }
    return call_user_func_array(array($this->response, $method), $args);
  }
    
  public function getRequest()
  {
    return $this->request;
  }
  
  public function getResponse()
  {
    return $this->response;
  }
  
  public function getSource()
  {
    return $this->source;
  }

  /**
   * returns the object which is to be built from this response
   * See the objects extending from CouchRequest, buildObjectFromResponse
   *
   * @return CouchData
   * @author The Young Shepherd
   **/
  public function getObject()
  {
    if (null === $this->object)
    {
      $this->object = $this->request->buildObjectFromResponse($this);
    }
    return $this->object;
  }
}