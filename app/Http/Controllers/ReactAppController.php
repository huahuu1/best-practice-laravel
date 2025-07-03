<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReactAppController extends Controller
{
    /**
     * Render the React application view
     *
     * @return View
     */
    public function index(): View
    {
        return view('react-app');
    }

    /**
     * Render the test React application view
     *
     * @return View
     */
    public function testReact(): View
    {
        return view('test-react');
    }
}
