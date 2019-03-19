<?php

namespace Overtrue\LaravelWeChat\Controllers\Api;

use App\Http\Controllers\Controller;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class AppSecretController extends Controller
{


    public function get()
    {
        $salt = "phfOtwKclusrHKwfgPtfIah1uT3xi";

        return [
            "app_secret" => md5(md5($salt.date('Ymd').$salt)),
        ];
    }

}
