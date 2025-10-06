<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use Illuminate\Http\Request;

class StockOutController extends Controller
{
    public function index(Request $request)
    {
        $stockOuts = StockOut::with(['item', 'user'])
            ->orderByDesc('stockout_date')
            ->orderByDesc('stockout_id')
            ->get();

        return view('stock_out.index', compact('stockOuts'));
    }

    public function receipt($stockout_id)
    {
        $stockOut = StockOut::with(['item', 'user'])->findOrFail($stockout_id);

        return view('stock_out.receipt', compact('stockOut'));
    }
}