<?php

namespace queasy\db\query;

interface QueryInterface
{
    public function run(array $params = array());
}

