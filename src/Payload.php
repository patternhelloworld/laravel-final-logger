<?php

namespace Arwg\FinalLogger;


use Arwg\FinalLogger\Exceptions\FinalException;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;



class Payload
{
    use Renderer;
//
    public static function reportError(\Exception $exception, $custom_func = null) : void
    {
        if (!$exception instanceof FinalException) {
            app(ErrorLogHandlerInterface::class)->createFinalException($exception, $custom_func);
        } else {
            $request = request();
            self::processFinalErrorLog($exception->getCode(), $exception->getMessage(), app(GeneralLogHandlerInterface::class)->createGeneralLogText($request));
        }

    }

    // Error Log should include general log as we want to know general data just when the error occurs
    public static function processFinalErrorLog(int $final_error_code, string $final_error_message, $generalLogText = null) : void
    {
        app(ErrorLogHandlerInterface::class)->processFinalErrorLog($final_error_code, $final_error_message, $generalLogText);

    }
    public static function createFinalException(\Exception $e, $custom_func = null)
    {
        app(ErrorLogHandlerInterface::class)->createFinalException($e, $custom_func);

    }


    public static function renderSuccess($data, $success_code)
    {
       return self::returnSuccess($data, $success_code);
    }
    public static function renderError(\Exception $exception, $safe_return = true, $request = null)
    {
        return self::returnError($exception, $safe_return, $request);
    }



}
