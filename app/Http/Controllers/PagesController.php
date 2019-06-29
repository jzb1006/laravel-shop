<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PagesController extends Controller
{
    //
    public function root(){
        return view('pages.root');
    }

    public function emailVerifiedNotice(Request $request){
        return view('pages.email_verified_notice');
    }
}
