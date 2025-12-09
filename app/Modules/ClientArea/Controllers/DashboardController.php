<?php

namespace App\Modules\ClientArea\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('clientarea::dashboard');
    }
}

