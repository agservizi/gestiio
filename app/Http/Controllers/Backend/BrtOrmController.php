<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\BrtOrmService;
use Illuminate\Http\Request;

class BrtOrmController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'orders' => ['required', 'array', 'min:1'],
        ]);

        $service = new BrtOrmService;
        $response = $service->create($request->input('orders'));

        return response()->json($response);
    }

    public function destroy(string $reservationId)
    {
        $service = new BrtOrmService;
        $response = $service->delete($reservationId);

        return response()->json($response);
    }
}
