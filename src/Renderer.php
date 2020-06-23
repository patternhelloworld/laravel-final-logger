<?php

namespace Arwg\FinalLogger;

use Arwg\FinalLogger\Exceptions\CommonExceptionModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait Renderer
{
    public static function returnSuccess($data, $success_code) : JsonResponse
    {
        //return response()->json(['baseData' => $data, 'successCode' => $successCode], $successCode);
        return response()->json(['baseData' => $data, 'successCode' => $success_code], $success_code, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            JSON_UNESCAPED_UNICODE);
    }

    public static function returnError($exception, $safe_return = true, $request = null) : JsonResponse
    {

        $err_re = json_decode($exception->getMessage());

        if ($safe_return) {
            // security
            if (isset($err_re->errors->internalMessage)) {
                $err_re->errors->internalMessage = '';
            }

            // security
            if (isset($err_re->errors->stackTraceString)) {
                $err_re->errors->stackTraceString = '';
            }
        }

        return response()->json($err_re, $exception->getCode(), ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            JSON_UNESCAPED_UNICODE);

    }

}
