<?php

namespace Arwg\FinalLogger\Exceptions;


class CommonExceptionModel
{

    public function __construct()
    {

    }

    public function stringifyExceptionModel($userMessage, $internalMessage, $info, $userCode = 0, $stackTraceString = null)
    {
        $_model = ['errors' => ['userMessage' => $userMessage, 'internalMessage' => $internalMessage,
            'userCode' => $userCode, 'info' => $info, 'stackTraceString' => $stackTraceString]];

        return \GuzzleHttp\json_encode($_model, JSON_UNESCAPED_UNICODE);
    }

    public static function getExceptionMessage($userMessage, $internalMessage, $info, $userCode = 0, $stackTraceString = null)
    {
        $commonExceptionModel = app(CommonExceptionModel::class);
        return $commonExceptionModel->stringifyExceptionModel($userMessage, $internalMessage, $info, $userCode, $stackTraceString);
    }

}
