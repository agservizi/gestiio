<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Controller;
use App\Models\ContrattoTelefonia;
use App\Models\MessaggioTicket;
use App\Models\RegistroLogin;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AreaUtenteController extends Controller
{
    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function show()
    {
        $user = $this->currentUser();
        if ($user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore'])) {
            return redirect()->action([DashboardController::class, 'show']);
        }

        $ticketsQuery = Ticket::query()->where('user_id', $user->id);
        $contrattiQuery = ContrattoTelefonia::delCliente();
        $ticketsDaLeggere = MessaggioTicket::query()
            ->whereHas('ticket', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('user_id', '<>', $user->id)
            ->whereNull('letto')
            ->count();

        return view('Frontend.AreaUtente.show', [
            'profileUser' => $user,
            'ticketsRecenti' => (clone $ticketsQuery)->latest('updated_at')->limit(8)->get(),
            'contrattiRecenti' => (clone $contrattiQuery)->latest('id')->limit(8)->get(),
            'ticketsTotali' => (clone $ticketsQuery)->count(),
            'ticketsAperti' => (clone $ticketsQuery)->whereIn('stato', ['aperto', 'in_lavorazione'])->count(),
            'ticketsDaLeggere' => $ticketsDaLeggere,
            'contrattiTotali' => (clone $contrattiQuery)->count(),
            'contrattiInLavorazione' => (clone $contrattiQuery)->where('esito_finale', 'in-lavorazione')->count(),
            'recentLogin' => RegistroLogin::where('user_id', $user->id)->latest('id')->limit(10)->get(),
        ]);
    }
}
