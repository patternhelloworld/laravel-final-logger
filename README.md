# Laravel-Final-Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/arwg/laravel-final-logger.svg?style=flat-square)](https://packagist.org/packages/arwg/laravel-final-logger)

## Overview

Laravel-final-logger provides unique and consistent formats throughout log (all Http, Ajax requests & responses), payload (all Http, Ajax requests & responses), and server-side error log. 
Also, you can nullify any children properties to reduce the size of logged files.

## Installation
```bash
composer require arwg/laravel-final-logger
php artisan vendor:publish --provider="Arwg\FinalLogger\FinalLoggerServiceProvider" --tag="config" 
```

## Usage

Simple to use

####1. How to error-log

```php

// app/Http/Exceptions/Handler.php (or your registered Handler)

namespace App\Exceptions;
use Arwg\FinalLogger\Payload;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
    ];

    public function report(Exception $exception)
    {
        // If you want to leave auth token invalidation info, don't comment this.
        if ($this->shouldntReport($exception)) {
            return;
        }

        // Can use only 'Payload::reportError($exception)' but recommends to create your own FinalException. So I have created a sample below.
        Payload::reportError($exception, function ($exception){
              return $this->createFinalException($exception);
           });

    }

    public function render($request, Exception $exception)
    {
        $message = null;

        /* XmlHttpRequest (Ajax) */
        if (\Request::wantsJson()) {
         
            if (Config('app.env') && Config('app.env') == 'local') {
                return Payload::renderError($exception, false);
            }else{
                return Payload::renderError($exception, true);
            }

        } else {
            /* HttpRequest */
            try{
                if (Config('app.env') && Config('app.env') == 'local') {
                    return parent::render($request, $exception->getPrevious() ? $exception->getPrevious() : $exception);
                }else{ 
                    // Follow Laravel auth in case of HttpRequest 401, 422.
                    if($exception->getCode() == 422 || $exception->getCode() == 401){
                        return parent::render($request, $exception->getPrevious() ? $exception->getPrevious() : $exception);
                    }
                    // Customize this according to your environment.
                    return response()->view('errors.error01', ['code' => '...', 'message' => 'Server error. Ask the administrator.']);
                }
            }catch (\Throwable $e){
                // Customize this according to your environment.
                return response()->view('errors.error01', ['code' => '...', 'message' => 'Server error. Ask the administrator.']);
            }


        }

    }

    // This is only sample. You can create your own FinalException.
    private function createFinalException(\Exception $e) : FinalException
    {
        $internalMessage = $e->getMessage();
        $lowLeverCode = $e->getCode();

        // 422: Laravel validation error
        if (isset($e->status) && $e->status == 422) {
            return new FinalException('Failed in Laravel validation check.',
                $internalMessage, config('final-logger.error_user_code')['paraemeter validation'], $e->errors(),
                config('final-logger.error_code')['Unprocessable Entity'], $e->getTraceAsString(), $e);
        }// 401 Oauth2 (id, password)
        else if ($e instanceof AuthenticationException || ($e instanceof ClientException && $lowLeverCode == 401)) {

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $internalMessage .= ' / ' . $_SERVER['HTTP_AUTHORIZATION'];
            }

            if (preg_match('/invalid.credentials|Unauthorized/', $internalMessage)) {
                return new FinalException('Wrong ID, Password.',
                    $internalMessage, null, "",
                    config('final-logger.error_code')['Unauthorized'], $e->getTraceAsString(), $e);

            } else if (preg_match('/Unauthenticated/', $internalMessage)) {
                return new FinalException('token not valid.',
                    $internalMessage, null, "",
                    config('final-logger.error_code')['Unauthorized'], $e->getTraceAsString(), $e);
            } else {
                return new FinalException('Auth error.',
                    $internalMessage, null, "",
                    config('final-logger.error_code')['Unauthorized'], $e->getTraceAsString(), $e);
            }
        } // Oauth2 (token)
        else if ($e instanceof OAuthServerException) {
            return new FinalException('Oauth2 token error.',
                $internalMessage, config('final-logger.error_user_code')['AccessToken error'], $e->getPayload(),
                config('final-logger.error_code')['Unauthorized'], $e->getTraceAsString(), $e);

        } else {

            $userCode = config('final-logger.error_user_code')['all unexpected errors'];

            return  new FinalException('Data (server-side) error has occurred.',
                $internalMessage, $userCode, "LowLeverCode : " . $lowLeverCode,
                config('final-logger.error_code')['Internal Server Error'], $e->getTraceAsString(), $e);
        }


    }

}
```

####2. How to general-log

##### IMPORTANT
Register logging path on 'config/final-logger.php'
```php
return [
    'general_log_path' => 'your-path',  // necessary
];
```
Register this Middleware to 'app/Http/Kernal.php'
```php
    protected $routeMiddleware = [
        //...
        'your-name' => \Arwg\FinalLogger\Middlewares\WriteGeneralLog::class
    ];
```
```php
// In api, web.php. Surround every route.
Route::group(['middleware' => 'your-name'], function () {
//...
});
```

##### Set certain properties to empty (due to reducing size of log files or whatever...)
The advantage of the library is that it is possible to set certain properties empty in payload hierarchy.
Let's say we are setting the property 'stats' empty and 'img_cnt' empty at an uri (api/v2/final-test-uri/images).

```json
{
   "baseData":{
      "data":{
         "stats":[
            {
               "id":18,
               "binary":"base64LDLDLDLS....",
               "cnt":1,
               "created_at":"2020-06-10 15:19:56",
               "updated_at":"2020-06-10 15:19:56"
            }
         ],
         "img_cnt":9,
         "img_total_cnt":100000
      }
   },
   "successCode":200
}
```
Modify 'config/final-logger.php'.
```php
// config/final-logger.php
return [
    'response_excepted_log_data' => [
        'api/v2/final-test-uri/images' => [['baseData', 'data','stats'],['baseData', 'data','img_cnt']]
    ],
    'request_excepted_log_data' => [
       // ... others
    ]
];
```
Now they all have been marked as "xxx" for 'api/v2/final-test-uri/images'.

```json
{
   "baseData":{
      "data":{
         "stats":[
            {
               "id":18,
               "binary":"xxx",
               "cnt":1,
               "created_at":"2020-06-10 15:19:56",
               "updated_at":"2020-06-10 15:19:56"
            }
         ],
         "img_cnt":"xxx",
         "img_total_cnt":100000
      }
   },
   "successCode":200
}
```

####3. Config file
Check the properties marked 'necessary' below. The others are just what I customized.
```php

// config/final-logger.php

return [

    'general_logger' => \Arwg\FinalLogger\GeneralLogHandler::class,  // necessary
    'error_logger' => \Arwg\FinalLogger\ErrorLogHandler::class,  // necessary
    'general_log_path' => config('app.dashboard_all_request_response'),  // necessary

    'request_excepted_log_data' => [
        'final-test-uri' => [['password'],['password_reset']]
    ],

    'response_excepted_log_data' => [
        'final-test-uri' => [['a','b', 'c'], ['a','d']]
    ],

    'success_code' => [
        'OK' => 200,
        'No Content' => 204
    ],

    'error_code' => [
        'Internal Server Error' => 500, // necessary

        'Bad Request' => 400, 
        'Unauthorized' => 401,
        'Not Found' => 404,
        'Request Timeout' => 408,
        'Precondition Failed' => 412,
        'Unprocessable Entity' => 422 
    ],

    'error_user_code' => [

        'all unexpected errors' => 900, // necessary

        'socket error' => 1113,
        'DB procedure...' => 1200,
    ]

];
```

####4. How to throw errors on codes
For handled errors, use this unique format.
```php
// In case of NOT Ajax, add a current exception to the last parameter of FinalException (like $e below). 
  throw new FinalException('requested email address is not valid.',
               "", config('final-logger.error_user_code')['parameter validation'], "",
        config('final-logger.error_code')['Bad Request'], $e);
```
or for unhandled errors. No need to handle any. But if you need...
```php
  Payload::createFinalException($e, function ($e){
       // example
        return new FinalException(...);
       // OR I recommend creating one function as shown Number 1.
  });
```

####<span>5. Error logging without throwing exceptions </span>
This doesn't make application stop but just log.
```php
        try {
            $binary = Storage::disk('aaa')->get($file_name);
        }catch (\Exception $e){
            Payload::processFinalErrorLog(config('final-logger.error_code')['Internal Server Error'], \Arwg\FinalLogger\Exceptions\CommonExceptionModel::getExceptionMessage('error when opening a file',
                $e->getMessage(),
                'lowlevelcode : ' . $e->getCode(),  config('final-logger.error_user_code')['all unexpected errors'], $e->getTraceAsString()));
        }

```

####6. Success payload (not necessary)
```php
class ArticleController extends Controller
{
    public function index(Request $request)
    {
  
        $data = $this->getData($$request->all());

        return Payload::renderSuccess(['list' => $data], config('final-logger.success_code')['OK']);
    }
}

```

## Samples

<span>1. </span>server-side error log sample
```
{
   "final":{
      "error_code":401,
      "error_payload":{
         "errors":{
            "userMessage":"Oauth2 token error.",
            "internalMessage":"The resource owner or authorization server denied the request.",
            "userCode":1400,
            "info":{
               "error":"access_denied",
               "error_description":"The resource owner or authorization server denied the request.",
               "hint":"Access token has been revoked",
               "message":"The resource owner or authorization server denied the request."
            },
            "stackTraceString":""
         }
      },
      "general_log":{
         "ip":"::1",
         "date":"2020-06-22 17:18:18",
         "type":"api",
         "uri":"api\/v1\/comments",
         "auth_header":"Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjgwZjdkM2RjNmNhZGJhYWYzMjg3YTY0YzIyY2U3MjZjNGY2MDIzODUyNmYyMWI0OGY5ZTdhZTQ1ZGJmYjZkNjhmZmE4MGMwOThkNDA3NzI4In0.eyJhdWQiOiIxMSIsImp0aSI6IjgwZjdkM2RjNmNhZGJhYWYzMjg3YTY0YzIyY2U3MjZjNGY2MDIzODUyNmYyMWI0OGY5ZTdhZTQ1ZGJmYjZkNjhmZmE4MGMwOThkNDA3NzI4IiwiaWF0IjoxNTkyODEzODQ1LCJuYmYiOjE1OTI4MTM4NDUsImV4cCI6MTU5MzI0NTg0NSwic3ViIjoiMTIyIiwic2NvcGVzIjpbXX0.A-7pTEoF7GyNc6zbCgDcK1IzMPoc6UWE3XNbl8Q6ZWyBe-a7Pfr0f5Ku1yCkQDimXBxH08Zy_7BQwULclTVO68XE0YgEWvP27FtlpXzMc4lzafUxhXKGR9NmLiXBUcYIWzx6r4tm6fgD337P5Gf0921jJ-tT33Pu7oZAbrLVQqiFu_gDKUBTBOcGVjHsQF5EwNAzpMb3Orn6AVF5W8rtO-flKrDUnnJcflS-XAtJiqobv5AGEa6faUrywCkElztJH9B2c5jSE_gxIozuH8ek7IC0lKPquwwqZvv-b_XukJOKEO4rgqyPSvDqVn9qJuV2uHkdNV05sdHZEU1a2BCmORj7BCtlCQpzDmVE4jdedXTwU1VZA8fxlyGZgW9_lACIx2Sc_fpmrEVULrT1SKfOvikZXFJSMBcxVh3z7ZF55Mbgqs4ifkjfk3MkeSYq9xsM-vB--Sxzzz7FsGh9KgGCTDNftNT8YmvokX5jSzruNxZUg4SGT7mqRd61Wplyd4sURkIEQvBEeTQmH0jwv-xWYCfK5Edm0HEP0DPs_TChF27NmDkp4kFBpahfph2-rkcf6fxvzyk6ZNJspUsDjbVfVN8A0MjG7pHm53IlDVtYqORkRMnjVNIaQqGLMX5NPKWqRoXhkAdW3TzN_ShxXzG_KRG3ciCOTXWUzuydmHkbkPc",
         "user_id":null,
         "request_data":[
         ],
         "response_status":null,
         "response_data":null
      }
   }
}
```
<span>2. </span>response log sample : this is the same as children of the general_log property above.
```
{
         "ip":"::1",
         "date":"2020-06-22 17:18:18",
         "type":"api",
         "uri":"api\/v1\/comments",
         "auth_header":"Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjgwZjdkM2RjNmNhZGJhYWYzMjg3YTY0YzIyY2U3MjZjNGY2MDIzODUyNmYyMWI0OGY5ZTdhZTQ1ZGJmYjZkNjhmZmE4MGMwOThkNDA3NzI4In0.eyJhdWQiOiIxMSIsImp0aSI6IjgwZjdkM2RjNmNhZGJhYWYzMjg3YTY0YzIyY2U3MjZjNGY2MDIzODUyNmYyMWI0OGY5ZTdhZTQ1ZGJmYjZkNjhmZmE4MGMwOThkNDA3NzI4IiwiaWF0IjoxNTkyODEzODQ1LCJuYmYiOjE1OTI4MTM4NDUsImV4cCI6MTU5MzI0NTg0NSwic3ViIjoiMTIyIiwic2NvcGVzIjpbXX0.A-7pTEoF7GyNc6zbCgDcK1IzMPoc6UWE3XNbl8Q6ZWyBe-a7Pfr0f5Ku1yCkQDimXBxH08Zy_7BQwULclTVO68XE0YgEWvP27FtlpXzMc4lzafUxhXKGR9NmLiXBUcYIWzx6r4tm6fgD337P5Gf0921jJ-tT33Pu7oZAbrLVQqiFu_gDKUBTBOcGVjHsQF5EwNAzpMb3Orn6AVF5W8rtO-flKrDUnnJcflS-XAtJiqobv5AGEa6faUrywCkElztJH9B2c5jSE_gxIozuH8ek7IC0lKPquwwqZvv-b_XukJOKEO4rgqyPSvDqVn9qJuV2uHkdNV05sdHZEU1a2BCmORj7BCtlCQpzDmVE4jdedXTwU1VZA8fxlyGZgW9_lACIx2Sc_fpmrEVULrT1SKfOvikZXFJSMBcxVh3z7ZF55Mbgqs4ifkjfk3MkeSYq9xsM-vB--Sxzzz7FsGh9KgGCTDNftNT8YmvokX5jSzruNxZUg4SGT7mqRd61Wplyd4sURkIEQvBEeTQmH0jwv-xWYCfK5Edm0HEP0DPs_TChF27NmDkp4kFBpahfph2-rkcf6fxvzyk6ZNJspUsDjbVfVN8A0MjG7pHm53IlDVtYqORkRMnjVNIaQqGLMX5NPKWqRoXhkAdW3TzN_ShxXzG_KRG3ciCOTXWUzuydmHkbkPc",
         "user_id":null,
         "request_data":[
         ],
         "response_status":null,
         "response_data":null
      }
```

<span>3. </span>error response payload sample : this is the same as children of the error_payload above.
```
    // userMessage : intended to be sent to clients.
    // internalMessage : not intended to be sent to clients, but logged.
    // userCode : intended to be sent to clients. (recommends to customize it )

   "errors":{
            "userMessage":"Oauth2 token error.",
            "internalMessage":"",
            "userCode":1400,
            "info":{
               "error":"access_denied",
               "error_description":"The resource owner or authorization server denied the request.",
               "hint":"Access token has been revoked",
               "message":"The resource owner or authorization server denied the request."
            },
            "stackTraceString":""
     }
```

We focus on the final endpoints, so logging is conducted at the only following two points.

<span>1. </span> Middleware : Response endpoints.
<br/>
<span>2. </span> The point when the error occurs.

No request endpoint logging is conducted, as the two points can catch all request data.



### Changelog

[Changelog](CHANGELOG.md)

## License

[License File](LICENSE.md)
