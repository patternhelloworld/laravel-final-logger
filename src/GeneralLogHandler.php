<?php

namespace Arwg\FinalLogger;

use Arwg\FinalLogger\Exceptions\CommonExceptionModel;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GeneralLogHandler implements GeneralLogHandlerInterface
{
    use Util;


    public function nullifyPropertyForLogging($data, $uri, $to_be_nullified_uri_property_set)
    {

        $property_set_for_matched_uri = [];

        if ($data) {

            if (isset($to_be_nullified_uri_property_set[$uri]) && is_array($to_be_nullified_uri_property_set[$uri]) && count($to_be_nullified_uri_property_set[$uri]) > 0) {
                $property_set_for_matched_uri = $to_be_nullified_uri_property_set[$uri];
            }

            if ($property_set_for_matched_uri && is_array($property_set_for_matched_uri)) {
                for ($a = 0; $a < count($property_set_for_matched_uri); $a++) {

                    // If this costs a lot, need to think about other methods.
                    $data = json_decode(json_encode($data), FALSE);

                    if(!is_array($property_set_for_matched_uri[$a])){
                        $property_set_for_matched_uri[$a] = [$property_set_for_matched_uri[$a]];
                    }
                    $this->set_property($property_set_for_matched_uri[$a], $data, "xxx");
                }
            }

        }

        return $data;
    }

    public function createGeneralLogText($request, $response_status = null, $response_data = null): string
    {

        $data_str = '';

        $type = null;
        $auth_header = null;
        $user_id = null;
        $uri = null;
        $request_log_data = null;
        $response_log_data = null;
        $real_uri = null;
        $today_time = null;

        try {

            if ($request->wantsJson() || $request->is('api/*')) {
                //write your logic for api call
                $type = 'api';
            } else {
                $type = 'web';
            }

            if ($request->header()) {
                $auth_header = $request->header('Authorization', '') ? $request->header('Authorization', '') : isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
            }

            $user = \Auth::user();
            if ($user) {
                $user_id = $user->id;
            }

            $uri = $request->route()->uri;

            /* paramters not to be written on REQUEST log files  */
            $request_log_data = $this->nullifyPropertyForLogging($request->all(), $uri,  config('final-logger.request_excepted_log_data'));

            /* paramters not to be written on RESPONSE log files  */
            $response_log_data = $this->nullifyPropertyForLogging($response_data, $uri, config('final-logger.response_excepted_log_data'));

            $real_uri = $request->path();

            $today_time = date("Y-m-d H:i:s");

            $data_str = json_encode(['ip' => $this->get_client_ip(), 'date' => $today_time, 'type' => $type, 'uri' => $real_uri, 'auth_header' => $auth_header,
                'user_id' => $user_id, 'request_data' => $request_log_data, 'response_status' => $response_status, 'response_data' => $response_log_data], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {

            $data_str_for_error_logging = json_encode(['ip' => $this->get_client_ip(), 'date' => $today_time, 'type' => $type, 'uri' => $real_uri, 'auth_header' => $auth_header,
                'user_id' => $user_id, 'request_data' => $request_log_data, 'response_status' => $response_status, 'response_data' => $response_log_data], JSON_UNESCAPED_UNICODE);

            Payload::processFinalErrorLog(config('final-logger.error_code')['Internal Server Error'],
                \Arwg\FinalLogger\Exceptions\CommonExceptionModel::getExceptionMessage('createRequestResponseLogText : Failed in creating logs.',
                    $e->getMessage(),
                    'lowlevelcode : ' . $e->getCode(), config('final-logger.error_user_code')['all unexpected errors'], $e->getTraceAsString()), $data_str_for_error_logging);

        }

        return $data_str;
    }

    public function processFinalGeneralLog(string $log_data, string $log_path): void
    {

        $today = date("Y-m-d");

        $fullPath = null;
        if (isset($_SERVER['REQUEST_METHOD'])) {
            if ($_SERVER['REQUEST_METHOD'] == "GET") {
                $fullPath = $log_path . '/Get_' . $today . '.log';
            } else {
                // CUD 를 모두 포함
                $fullPath = $log_path . '/Post_' . $today . '.log';
            }
        } else {
            $fullPath = $log_path . '/Etc_' . $today . '.log';
        }

        if ($fullPath) {
            file_put_contents($fullPath, $log_data . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

}
