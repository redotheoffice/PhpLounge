<?php

class CouchDb implements CouchQueryable , ArrayAccess
{
  protected static
    $connection = null, // CouchConnection where this DB resides
    $name = null;   // the database name

  /**
   * Constructor
   * 
   * @return void
   */
  public function __construct(CouchConnection $connection, $name)
  {
    $this->connection = $connection;
    $this->name = $name;
  }

  public function getConnection()
  {
    return $this->connection;
  }

  public function getName()
  {
    return $this->name;
  }

  /**
   * send sends a couchrequest to the connection and returns the couchresponse 
   *
   * @return CouchResponse The reponse
   * @author The Young Shepherd
   **/
  public function send(CouchRequest $request)
  {
    $request = clone $request;
    $request->setQuery($this->name.'/'.$request->getQuery());
    return new CouchResponse($this, $request);
  }

  /**
   * Compact a database
   * 
   * @param  null|string $db 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception when fails or no database specified
   */
  public function compact()
  {
    $request = new CouchRequest('POST','_compact');
    $response = $this->send($request);
    $status = $response->getStatus();
    if (202 !== $status) {
      throw new Phly_Couch_Exception(sprintf('Failed compacting database "%s": received response code "%s"', $db, (string) $response->getStatus()));
    }
    return $response;
  }

  /**
   * Get database info
   * 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception when fails
   */
  public function getInfo()
  {
    $request = new CouchRequest('GET','');
    $response = $this->send($request);
    if (!$response->isSuccessful()) {
      throw new CouchException(sprintf('Failed querying database "%s"; received response code "%s"', $this->name, (string) $response->getStatus()));
    }
    return $response;
  }
  
  /**
   * Drop database
   * 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception when fails
   */
  public function drop()
  {
    return $this->connection->drop($this);
  }

  // CRUD
  
  public function create($doc, $id = null)
  {
    if (!$doc instanceof CouchDocument)
    {
      if (!is_array($doc))
      {
        $doc = Zend_JSON::decode($doc);
      }

      $data = new CouchData($doc);
      $doc = CouchDocument::create($this, $data);
    }
    
    if (!is_null($id))
    {
      $doc->id = $id;
    }
    
    if (isset($doc->id))
    {
      $request = new CouchRequest('PUT', urlencode($doc->id), $doc);
    }
    else
    {
      $request = new CouchRequest('POST', "", $doc);
    }
    $response = $this->send($request);
    $responseObject = $response->getObject();
    
    $doc->id = $responseObject['id'];
    $doc->rev = $responseObject['rev'];
    
    return $doc;
  }

  public function update(CouchDocument $doc)
  {
    if (!isset($doc->id) || !isset($doc->rev))
    {
      throw new CouchException('Cannot update document without an id and prior rev');
    }
    
    $request = new CouchRequest('PUT', urlencode($doc->id), $doc);
    $response = $this->send($request);
    $responseObject = $response->getObject();

    $doc->rev = $responseObject['rev'];
    
    return $doc;
  }
  
  public function delete($id)
  {
    if ($id instanceof CouchDocument)
    {
      $doc = $id;
      $id = $doc->id;
    }
    $request = new CouchRequest('PUT', urlencode($id));
    $this->send($request);
    
    return true;
  }

  public function get($id, array $params = array())
  {
    $request = new CouchRequestDocument(urlencode($id), $params);
    $response = $this->send($request);
    return $response->getObject();
  }

  // Document API methods

  /**
   * Retrieve all documents for a give database
   * 
   * @param  null|array $options Query options
   * @return Phly_Couch_DocumentSet
   * @throws Phly_Couch_Exception on failure or bad db
   */
  public function getAll(array $options = null)
  {
    $request = new CouchRequestSet('GET', '_all_docs', $options);
    $response = $this->send($request);
    return $response->getObject();
  }

  /**
   * Remove a document
   * 
   * @param  string $id 
   * @param  array $options 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception on failed call
   */
  public function docRemove($id, array $options = null)
  {
      $db = null;
      if (is_array($options) && array_key_exists('db', $options)) {
          $db = $options['db'];
          unset($options['db']);
      }
      $db = $this->_verifyDb($db);

      $response = $this->_prepareAndSend($db . '/' . $id, 'DELETE', $options);
      if (!$response->isSuccessful()) {
          require_once 'Phly/Couch/Exception.php';
          throw new Phly_Couch_Exception(sprintf('Failed deleting document with id "%s" from database "%s"; received response code "%s"', $id, $db, (string) $response->getStatus()));
      }

      require_once 'Phly/Couch/Result.php';
      return new Phly_Couch_Result($response);
  }

  /**
   * Bulk save many documents at once
   * 
   * @param  array|Phly_Couch_DocumentSet $documents 
   * @param  array $options 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception on failed save
   */
  public function docBulkSave($documents, array $options = null)
  {
      $db = null;
      if (is_array($options) && array_key_exists('db', $options)) {
          $db = $options['db'];
          unset($options['db']);
      }
      $db = $this->_verifyDb($db);

      if (is_array($documents)) {
          require_once 'Phly/Couch/DocumentSet.php';
          $documents = new Phly_Couch_DocumentSet($documents);
      } elseif (!$documents instanceof Phly_Couch_DocumentSet) {
          require_once 'Phly/Couch/Exception.php';
          throw new Phly_Couch_Exception('Invalid document set provided to bulk save operation');
      }

      $this->getHttpClient()->setRawData($documents->toJson());
      $response = $this->_prepareAndSend($db . '/_bulk_docs', 'POST', $options);
      if (!$response->isSuccessful()) {
          require_once 'Phly/Couch/Exception.php';
          throw new Phly_Couch_Exception(sprintf('Failed deleting document with id "%s" from database "%s"; received response code "%s"', $id, $db, (string) $response->getStatus()));
      }

      require_once 'Phly/Couch/Result.php';
      return new Phly_Couch_Result($response);
  }

  /**
   * Retrieve a view
   *
   * @param  string $name
   * @param  null|array $options 
   * @return Phly_Couch_DocumentSet
   * @throws Phly_Couch_Exception on failure or bad db
   */
  public function view($name, array $options = null)
  {
      $db = null;
      if (is_array($options) && array_key_exists('db', $options)) {
          $db = $options['db'];
          unset($options['db']);
      }
      $db = $this->_verifyDb($db);

      $response = $this->_prepareAndSend($db . '/_view/'.$name, 'GET', $options);
      if (!$response->isSuccessful()) {
          require_once 'Phly/Couch/Exception.php';
          throw new Phly_Couch_Exception(sprintf('Failed querying database "%s"; received response code "%s"', $db, (string) $response->getStatus()));
      }

      require_once 'Phly/Couch/DocumentSet.php';
      return new Phly_Couch_DocumentSet($response->getBody());
  }
 
  public function offsetGet($id)
  {
    try {
      $doc = $this->get($id);
    } catch (CouchException $e) {
      if ($e->getCode() != 404)
      {
        throw $e;
      }
      // if not found, return a new document with provided id
      $doc = new CouchDocument($this, (string)$id);
    }
    return $doc;
  }
  
  public function offsetSet($id, $doc)
  {
    if (!$doc instanceof CouchDocument)
    {
      $doc = CouchDocument::create($this, $doc);
    }
    $doc->id = $id;
    $doc->save();
  }
  
  public function offsetExists($id)
  {
    try {
      $this->get($id);
      return true;
    } catch (Exception $e) { 
      return false;
    }
  }
  
  public function offsetUnset($id)
  {
    try {
      $doc = $this->get($id);
      $doc->delete();
    } catch (Exception $e) { 
    }
  }
}