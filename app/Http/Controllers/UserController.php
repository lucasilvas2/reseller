<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Admin/Users/Index', []);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }
}
