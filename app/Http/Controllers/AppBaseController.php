<?php

namespace App\Http\Controllers;

use Response;

/**
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $code = 200, $message = null)
    {
        $res = $result;

        if (!empty($message)) {
            $res = [
                'success' => true,
                'data' => $result,
                'message' => $message,
            ];
        }

        return Response::json($res, $code);
    }

    public function sendError($error, $code = 500)
    {
        $res = [
            'message' => 'Error: ' . $error,
        ];

        return Response::json($res, $code);
    }
}
