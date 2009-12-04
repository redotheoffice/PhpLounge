<?php

interface CouchQueryable
{
  public function send(CouchRequest $query);
}
