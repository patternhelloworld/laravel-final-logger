<?php

namespace Arwg\FinalLogger;

use Arwg\FinalLogger\Exceptions\CommonExceptionModel;
use Illuminate\Http\Request;

interface GeneralLogHandlerInterface
{
    public function nullifyPropertyForLogging($data, $uri, $to_be_nullified_uri_property_set);
    public function createGeneralLogText($request, $response_status = null, $response_data = null): string;
    public function processFinalGeneralLog(string $log_data, string $log_path): void;

}
