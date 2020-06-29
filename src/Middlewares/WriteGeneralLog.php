<?php

namespace Arwg\FinalLogger\Middlewares;

use Arwg\FinalLogger\Exceptions\CommonExceptionModel;

use Closure;

use Arwg\FinalLogger\ErrorLogHandlerInterface;
use Arwg\FinalLogger\GeneralLogHandlerInterface;

class WriteGeneralLog
{
    protected $generalLogHandler;
    protected $errorLogHandler;

    public function __construct(GeneralLogHandlerInterface $generalLogHandler, ErrorLogHandlerInterface $errorLogHandler)
    {
        $this->generalLogHandler = $generalLogHandler;
        $this->errorLogHandler = $errorLogHandler;
    }


    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $status_code = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;
        $status_data = method_exists($response, 'getData') ? $response->getData() : null;

        $data = '';

        try {

            $data = $this->generalLogHandler->createGeneralLogText($request, $status_code, $status_data);

            if (!config('final-logger.general_log_path')) {
                throw new \Exception("general_log_path is not set in config/final-logger.php");
            }

            $log_path = config('final-logger.general_log_path');

            $this->generalLogHandler->processFinalGeneralLog($data, $log_path);


        } catch (\Throwable $e) {


            $this->errorLogHandler->processFinalErrorLog(config('final-logger.error_code')['Internal Server Error'], \Arwg\FinalLogger\Exceptions\CommonExceptionModel::getExceptionMessage('Failed to log all_request_response',
                $e->getMessage(),
                'lowlevelcode : ' . $e->getCode(), config('final-logger.error_user_code')['all unexpected errors'], $e->getTraceAsString()), $data);

        }

        return $response;
    }
}
