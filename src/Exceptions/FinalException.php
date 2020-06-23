<?php

namespace Arwg\FinalLogger\Exceptions;


use Arwg\FinalLogger\Payload;
use Throwable;

class FinalException extends \Exception
{

    public function __construct($userMessage, $internalMessage, $userCode, $info, $code = 500, $stackTraceString=null, Throwable $previous = null)
    {
 ;
        parent::__construct(CommonExceptionModel::getExceptionMessage($userMessage, $internalMessage, $info, $userCode,
            $stackTraceString ? $stackTraceString : $this->getTraceAsString()), $code, $previous);

    }

}
