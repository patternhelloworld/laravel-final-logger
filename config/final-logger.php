<?php

return [


    'general_logger' => \Arwg\FinalLogger\GeneralLogHandler::class, // necessary

    'error_logger' => \Arwg\FinalLogger\ErrorLogHandler::class, // necessary

    'general_log_path' => '', // necessary

    'success_code' => [
        'OK' => 200,
        'No Content' => 204
    ],

    'error_code' => [
        'Internal Server Error' => 500, // necessary
        'Bad Request' => 400, //
        'Unauthorized' => 401,
        'Not Found' => 404,
        'Request Timeout' => 408,
        'Precondition Failed' => 412,
        'Unprocessable Entity' => 422 // validattion
    ],

    'error_user_code' => [
        'all unexpected errors' => 900 // necessary
    ],

    'request_excepted_log_data' => [
       // 'final-test-uri' => [['password'],['password_reset']]
    ],

    'response_excepted_log_data' => [
        //'final-test-uri' => [['a','b', 'c'], ['a','d']]
    ],


];
