<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * @param Request $request
     * @return Factory|View|Application
     */
    public function index(Request $request): Factory|View|Application
    {
        $filterData = [];

        $title = request()->input('title');
        if (strlen($title)) {
            $filterData['title'] = $title;
        }

        $barcode = request()->input('barcode');
        if (strlen($barcode)) {
            $filterData['barcode'] = $barcode;
        }

        $isVariant = request()->input('is_variant');
        if (strlen($isVariant)) {
            $filterData['is_variant'] = $isVariant;
        }

        return view('pages.index', [
            'filterData' => $filterData
        ]);
    }
}
