<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use Illuminate\Http\Request;

class LuggageVerifyPageController extends Controller
{
    public function __construct(private LuggageDepositService $service)
    {
    }

    public function show(string $id, Request $request)
    {
        $token = $request->query('t');
        abort_unless($token, 404);

        $deposit = $this->service->verifyByToken($token);
        abort_unless($deposit && $deposit->id === $id, 404);

        return view('Frontend.LuggageDeposit.verify', [
            'deposit' => $deposit,
        ]);
    }
}
