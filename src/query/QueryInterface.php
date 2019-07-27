<?php

namespace queasy\db\query;

interface QueryInterface
{
    public function run(array $params = array());

    public function __invoke(array $params = array());
}

