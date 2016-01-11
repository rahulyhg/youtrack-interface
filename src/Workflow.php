<?php

namespace Juno;
use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\Reader\ReaderInterface;
use Ddeboer\DataImport\Workflow as BaseWorkflow;
use Psr\Log\LoggerInterface;

class Workflow extends BaseWorkflow
{
    protected $reader;
}