<?php

namespace  Youtrackinterfacer;

require_once __DIR__ . '/../vendor/autoload.php';

use Ddeboer\DataImport\Workflow as BaseWorkflow;

class Workflow extends BaseWorkflow
{
    protected $reader;
}
