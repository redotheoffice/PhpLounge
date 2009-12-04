<?php

/*
idee is om een restrictie op een property te zetten waaraan dat veld moet voldoen
dus 
naam => test

en naam => validatorstring(required true)

valideert en blijft valideren ook na toevoegen
*/

class CouchDocument extends CouchData
{
  protected
    $db = null;

  static public function create(CouchDb $db, $data, $class = null)
  {
    if (null === $class)
    {
      $data = CouchData::makeArray($data);
      $class = isset($data['php_class']) ? $data['php_class'] : __CLASS__;
    }
    return new $class($db, $data);
  }
  
  public function __construct(CouchDb $db, $rawData = array())
  {
    parent::__construct($rawData);
    
    $this->db = $db;
    // store as metadata the PHP class of this document
    $this['php_class'] = get_class($this);
  }

  public function exists()
  {
    return isset($this->rev);
  }

  public function save()
  {
    if ($this->exists())
    {
      // Updating an existing document
      return $this->db->update($this);
    }
    else
    { 
      // Create a new document
      return $this->db->create($this);
    }
  }

  public function delete()
  {
    if ($this->exists())
    {
      return $this->db->delete($this);
    }
  }  
}