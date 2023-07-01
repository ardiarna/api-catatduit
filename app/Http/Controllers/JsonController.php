<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Support\Facades\File;

class JsonController extends Controller
{
    use ApiResponser;

    public function findAll() {
        $json = File::get('json/phone_code.json');
        $data = json_decode($json);
        return $this->successResponse($data);
    }


}
