<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use App\Product;

class PageController extends Controller
{

    public function __construct()
        {
         $this->middleware('guest');
        }

    public function getIndex()
    {
    	// return view('welcome');
        $products = Product::orderby('id', 'desc')->paginate(6);
        // $products = Product::all();
        return view('welcome')->withProducts($products);
    }

    public function getAbout()
    {
    	return view('pages.about');
    }

    public function getContact()
    {
    	return view('pages.contact');
    }

    public function getT1()
    {
    	return view('test');
    }

    public function getT2()
    {
    	return view('test2');
    }

    public function login()
    {
        return view('pages.login');
    }

    public function register()
    {
        return view('pages.register');
    }
}
