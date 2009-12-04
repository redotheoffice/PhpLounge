<?php
class CouchConflictException extends CouchException
{
  public function __construct($message = null, $code = null, $previous = null, CouchData $data = null)
  {
    ob_start();
    var_dump($data->getRequest()->getData()->getData());
    $var_dump = ob_get_contents();
    ob_end_clean();
    if (is_null($message))
    {
      $message = "CouchConflictException:";
    }
    $message = $message . "\n". $var_dump;
    parent::__construct($message, $code, $previous, $data);
  }
}
