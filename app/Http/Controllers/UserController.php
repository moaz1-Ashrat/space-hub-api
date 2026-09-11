<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    function index()
    {
        $users =
        [
            ['id'=>1,'name'=>"Moaz"],
            ['id'=>2,'name'=>"tas"],
            ['id'=>3,'name'=>"ahmad"],

        ];
        return response()->json(["name"=>"Moaz"]);
    }


};
