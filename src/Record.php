<?php

namespace queasy\db;

class Record implements \Iterator, \ArrayAccess, \Countable
{
    private $isLoaded = false;

    private $isDirty = false;

    private $fields = array();

    public function __construct()
    {
        
    }

    public function save()
    {
        if (!($this->isLoaded && !$this->isDirty)) {
            return;
        }

        
    }

    public function toArray()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->fields;
    }
}

