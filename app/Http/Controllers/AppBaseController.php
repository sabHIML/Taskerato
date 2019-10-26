<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Response;

/**
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $code = JsonResponse::HTTP_OK, $message = null)
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

    public function sendError($error, $code = JsonResponse::HTTP_INTERNAL_SERVER_ERROR)
    {
        $res = [
            'message' => 'Error: ' . $error,
        ];

        return Response::json($res, $code);
    }
}
