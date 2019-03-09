<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;

class ApiController extends Controller
{
    use Helpers;
    public $guard_name = 'api';

    public function successMessage($message){
        return $this->response->array([
            'success_message' => $message,
        ]);
    }

    public function errorMessage($message){
        return $this->response->array([
            'error_message' => $message,
        ]);
    }
}
