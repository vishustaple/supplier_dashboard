<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CronResourcePage extends Controller
{
    public function index() {
        return view('admin.cron_resource_page', ['pageTitle' => "Cron Resource Page"]);
    }
}
