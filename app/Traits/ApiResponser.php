<?php

namespace App\Traits;

trait ApiResponser {

    public function successResponse($data, $message = ""){
        return response()->json(['status' => 'success', 'message' => $message, 'data' => $data]);
    }

    public function createdResponse($data, $message = ""){
        return response()->json(['status' => 'success', 'message' => $message, 'data' => $data], 201);
    }

    public function failResponse($message = "", $code){
        return response()->json(['status' => 'fail', 'message' => $message], $code);
    }

    public function failRespBadReq($message = ""){
        return $this->failResponse($message, 400);
    }

    public function failRespUnAuth($message = ""){
        return $this->failResponse($message, 401);
    }

    public function failRespPaymentReq($message = ""){
        return $this->failResponse($message, 402);
    }

    public function failRespForbidden($message = ""){
        return $this->failResponse($message, 403);
    }

    public function failRespNotFound($message = ""){
        return $this->failResponse($message, 404);
    }

    public function failRespUnProcess($message = ""){
        return $this->failResponse($message, 422);
    }

    public function aprUrlFile($data, $path = '') {
        if($data == null || $data == '') {
            return '';
        }
        return $path.$data;
    }

}
