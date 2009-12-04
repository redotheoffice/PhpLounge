<?php

class CouchData implements ArrayAccess
{
  static public
    $specialFields = array(
      '_id' => true, 
      '_rev' => true, 
      '_attachments' => false, 
      '_deleted' => false, 
      '_revisions' => false, 
      '_rev_infos' => false, 
      '_conflicts' => false, 
      '_deleted_conflicts' => false
      );
  
  protected
    $data = array(), // variable holding all data 
    $readOnly = false;  // is this a readonly object?

  /**
   * makeArray converts the argument to an array if possible
   * 
   * @param mixed $date is a CouchData object, JSON string, null or array
   * @return array The argument in array format
   * @author The Young Shepherd
   **/
  static public function makeArray($data)
  {
    if ($data instanceof CouchData)
    {
      $data = $data->getData();
    }
    elseif (is_string($data))
    {
      $data = Zend_Json::decode($data);
    }
    
    if (is_null($data))
    {
      $data = array();
    }
    
    if (is_array($data))
    {
      return $data;
    }

    throw new CouchException('Error converting value to array');
  }

  public function __construct($data = array(), $readOnly = false)
  {
    $this->setData(self::makeArray($data));
    $this->readOnly = $readOnly;
  }

  public function isReadOnly()
  {
    return $this->readOnly;
  }

  public function __toString()
  {
    return Zend_JSON::encode($this->getData());
  }

  public function getData()
  {
    return $this->data;
  }
  
  /**
   * tests if this is a writeable object, otherwise throw exception
   *
   * @return true
   * @throws CouchException when the object is not writable
   * @author The Young Shepherd
   **/
  public function assertWritable()
  {
    if ($this->readOnly)
    {
      throw new CouchException('Cannot write to a readonly CouchData object');
    }
    return true;
  }

  public function setData(array $data)
  {
    $this->assertWritable();
    $this->data = $data;
  }

  public function get($key, $default = null)
  {
    return isset($this[$key]) ? $this[$key] : $default;
  }

  /**
   * __get implements object access to metadata properties of this dat object
   *
   * @return mixed the value
   * @author The Young Shepherd
   **/
  public function __get($name)
  {
    if (isset($this->$name))
    {
      return $this->data['_'.$name];
    }
    throw new RuntimeException(sprintf('Property \'%s\' does not exist', $name));
  }

  public function __set($name, $value)
  {
    if (in_array('_'.$name, self::$specialFields))
    {
      $this->assertWritable();
      $this->data['_'.$name] = $value;
    }
    else
    {
      throw new RuntimeException(sprintf('Property \'%s\' does not exist', $name));
    }
  }

  public function __isset($name)
  {
    return isset($this->data['_'.$name]) && array_key_exists('_'.$name, self::$specialFields);
  }

  public function __unset($name)
  {
    if (isset(self::$specialFields['_'.$name]) && !self::$specialFields['_'.$name])
    {
      $this->assertWritable();
      unset($this->data['_'.$name]);
    }
    else
    {
      throw new RuntimeException(sprintf('Property \'%s\' does not exist', $name));
    }
  }

  //implementing ArrayAccess  
  public function offsetGet($name)
  {
    if (isset($this[$name]))
    {
      return $this->data[$name];
    }
    throw new RuntimeException(sprintf('Key \'%s\' does not exist', $name));
  }
  
  public function offsetSet($name, $value)
  {
    $this->assertWritable();
    $this->data[$name] = $value;
  }
  
  public function offsetExists($name)
  {
    return array_key_exists($name, $this->data);
  }
  
  public function offsetUnset($name)
  {
    $this->assertWritable();
    unset($this->data[$name]);
  }
}
