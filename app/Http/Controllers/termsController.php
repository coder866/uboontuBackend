<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class termsController extends Controller
{
    public function privacy(){
        return view('tcs.privacy');
    }


    public function termsOfUse(){
        return view('tcs.terms_of_use');
    }

    public function cancellation(){
        return view('tcs.cancellation_and_refunds');
    }
}