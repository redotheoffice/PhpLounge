<?php
class CouchConnection implements ArrayAccess, CouchQueryable
{
  protected static 
    $defaultClient = null; // Zend_Http_Client Default HTTP client to use for CouchDB access
  
  protected
    $client = null,       // Zend_Http_Client HTTP client used for accessing server
    $host = null,         // string Database host; defaults to 127.0.0.1
    $port = null,         // int Database host port; defaults to 5984
    $databases = array(); // cache for all databases on this server

  /**
   * Constructor
   * 
   * 
   * @return void
   */
  public function __construct($host = '127.0.0.1', $port = 5984, Zend_Http_Client $client = null)
  {
    $this->host = $host;
    $this->port = $port;
    $this->client = $client;
  }

  /**
   * Set database host
   * 
   * @param  string $host 
   * @return Phly_Couch
   */
  public function setHost($host)
  {
      $this->host = $host;
      return $this;
  }

  /**
   * Retrieve database host
   * 
   * @return string
   */
  public function getHost()
  {
      return $this->host;
  }
  
  /**
   * Set database host port
   * 
   * @param  int $port 
   * @return Phly_Couch
   */
  public function setPort($port)
  {
      $this->port = (int) $port;
      return $this;
  }

  /**
   * Retrieve database host port
   * 
   * @return int
   */
  public function getPort()
  {
      return $this->port;
  }

  // HTTP client

  /**
   * Set HTTP client
   * 
   * @param  Zend_Http_Client $client 
   * @return Phly_Couch
   */
  public function setHttpClient(Zend_Http_Client $client)
  {
      $this->client = $client;
      return $this;
  }

  public function send(CouchRequest $query)
  {
    return new CouchResponse($this, $query);
  }

  /**
   * Set default HTTP client
   * 
   * @param  Zend_Http_Client $client 
   * @return void
   */
  public static function setDefaultHttpClient(Zend_Http_Client $client)
  {
      self::$defaultClient = $client;
  }

  /**
   * Get current HTTP client 
   * 
   * @return Zend_Http_Client
   */
  public function getHttpClient()
  {
      if (null === $this->client) {
          $client = self::getDefaultHttpClient();
          if (null === $client) {
              require_once 'Zend/Http/Client.php';
              $client = new Zend_Http_Client;
          }
          $this->setHttpClient($client);
      }
      $this->client->resetParameters();
      return $this->client;
  }

  /**
   * Retrieve default HTTP client
   * 
   * @return null|Zend_Http_Client
   */
  public static function getDefaultHttpClient()
  {
      return self::$defaultClient;
  }

  /**
   * getUri returns the uri to this CouchServer
   *
   * @param string Path The path that is appended to this URI
   * @return string The uri
   * @author The Young Shepherd
   **/
  public function getUri($query = "")
  {
    return 'http://'.$this->host.':'.$this->port.'/'.$query;
  }

  // Helper methods
  /**
   * Verify database parameter
   * 
   * @param  mixed $db 
   * @return string
   * @throws Phly_Couch_Exception for invalid database
   */
  protected function _verifyDb($db)
  {
      if (null === $db) {
          $db = $this->getDb();
          if (null === $db) {
              require_once 'Phly/Couch/Exception.php';
              throw new Phly_Couch_Exception('Must specify a database to query');
          }
      } else {
          $this->setDb($db);
      }
      return $db;
  }

  // Server API methods

  /**
   * Get server information
   */
  public function getInfo()
  {
    $request = new CouchRequest('GET', '');
    return $this->send($request)->getObject();
  }

  /**
   * Get list of all databases
   *
   * @return CouchData List of all DB names
   * @author The Young Shepherd
   **/
  public function getDbNames()
  {
    $request = new CouchRequest('GET', '_all_dbs');
    return $this->send($request)->getObject();
  }

  // Database API methods
  // ArrayAccess delivers the DB's on this server
  
  static public function checkDatabaseName($name)
  {
    if (!preg_match('/^[a-z][a-z0-9_$()+-\/]+$/', $name))
    {
      throw new CouchException(sprintf('Invalid database name specified: "%s"', htmlentities($db)));
    }
  }
  
  /**
   * Create database
   * 
   * @param  string $name of the new database 
   * @return CouchDb
   * @throws CouchException when fails or invalid database name
   */
  public function createDatabase($name)
  {
    self::checkDatabaseName($name);

    $request = new CouchRequest('PUT', $name);
    $response = $this->send($request);
    
    if (!$response->isSuccessful()) {
      throw new CouchException(sprintf('Failed creating database "%s"; received response code "%s"', $db, (string) $response->getStatus()));
    }
  
    $this->databases[$name] = new CouchDb($this, $name);
    
    return $this->databases[$name];
  }

  public function offsetGet($name)
  {
    self::checkDatabaseName($name);
    if (!isset($this->databases[$name]))
    {
      if (!isset($this[$name]))
      {
        //create the database
        $this->createDatabase($name);
      }
      else
      {
        $this->databases[$name] = new CouchDb($this, $name);
      }
    }
    return $this->databases[$name];
  }

  public function offsetSet($name, $value)
  {
    throw new CouchException('Cannot set a database on this server to a value');
  }

  /**
   * Drop database
   * 
   * @param  string $db 
   * @return Phly_Couch_Result
   * @throws Phly_Couch_Exception when fails
   */
  public function drop(CouchDb $db)
  {
    $name = $db->getName();
    $request = new CouchRequest('DELETE', $name);
    $response = $this->send($request);
    
    if (!$response->isSuccessful()) {
      throw new CouchException(sprintf('Failed dropping database "%s"; received response code "%s"', $db, (string) $response->getStatus()));
    }
  
    unset($this->databases[$name]);
  }

  public function offsetUnset($name)
  {
    if (isset($this[$name]))
    {
      $this->drop($this[$name]);
    }
  }
  
  public function offsetExists($name)
  {
    self::checkDatabaseName($name);
    $dbs = $this->getDbNames()->getData();
    return in_array($name, $dbs);
  }
}
