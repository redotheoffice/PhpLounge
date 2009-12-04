<?php
class CouchException extends Exception
{
  protected
    $data = null;
  
  public function __construct($message = null, $code = null, $previous = null, CouchData $data = null)
  {
    parent::__construct($message, $code, $previous);
    $this->data = $data;
  }
  
  public function getData()
  {
    return $this->data;
  }
}
