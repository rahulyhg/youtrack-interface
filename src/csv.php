<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
use Ddeboer\DataImport\Writer\CsvWriter;


class csvClass
{
    /**
     * creates a csv.
     *
     * @param array  $data               simple array of csv data
     * @param string $outputFileLocation file location
     * @param string $delimiter
     * @param array  $header             header fields
     *
     * @return bool false if unable to write to folder
     */
    public function createCsv($data, $outputFileLocation, $delimiter = ',', $header = [])
    {
        if (!is_writable(dirname($outputFileLocation))) {
            echo 'Unable to create file '.$outputFileLocation.' . Please check write permissions for your web server (apache/nginx/..)'.$GLOBALS['newline'];

            return false;
        } else {
            $writer = new CsvWriter($delimiter);
            $writer->setStream(fopen($outputFileLocation, 'w'));
            foreach ($data as $col) {
                if (empty($header)) {
                    $header = array_keys($col);
                    $writer->writeItem($header);
                }
                $writer->writeItem($col);
            }
            $writer->finish();
            $filePermissions = $GLOBALS['filePermissions'];
            chmod($outputFileLocation, $filePermissions);
        }
    }
}
