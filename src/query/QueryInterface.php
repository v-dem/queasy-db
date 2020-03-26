<?php

namespace queasy\db\query;

interface QueryInterface
{
    public function run(array $params = array(), array $options = array());

    public function __invoke(array $params = array(), array $options = array());
}

