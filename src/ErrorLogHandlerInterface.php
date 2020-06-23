<?php

namespace Arwg\FinalLogger;

interface ErrorLogHandlerInterface
{
    /* error log trait */
    public function reportErrorLog($exception, $custom_func = null);

    public function createFinalException(\Exception $e, $custom_func = null);

    // Error Log should include general log as we want to know general data just when the error occurs
    public function processFinalErrorLog(int $final_error_code, string $final_error_message, $generalLogText = null);

}
