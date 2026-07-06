<?php

namespace App\Http\Livewire;

use App\Models\Notifica;
use App\Models\NotificaLettura;
use App\Models\User;
use App\Support\UserNotificationPreferences;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MenuNotifiche extends Component
{
    public $notifiche;

    public $conteggio;

    public $nuove = false;

    public $ora;

    protected $listeners = ['aggiornaNotifiche' => 'aggiornaNotifiche'];

    public function mount()
    {
        $this->ora = now();
        $this->aggiornaNotifiche();

        if ($this->conteggio) {
            $this->nuove = true;
        } else {
            $this->nuove = false;
        }
    }

    public function render()
    {
        return view('livewire.menu-notifiche');
    }

    public function aggiornaNotifiche()
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $destinatario = $authUser->hasPermissionTo('admin') ? 'admin' : 'agente';

        $this->notifiche = Notifica::query()
            ->whereDate('created_at', '>=', $authUser->created_at)
            ->where('destinatario', $destinatario)
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            })
            ->orderByDesc('id')
            ->whereDoesntHave('letture', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->withCount(['letture' => function ($q) {
                $q->where('user_id', Auth::id());
            }])
            ->get();

        $this->conteggio = $this->notifiche->count();

        if ($this->conteggio == 0) {
            $this->nuove = false;
        } else {
            if (! $this->nuove) {
                /** @var User $user */
                $user = Auth::user();
                if (UserNotificationPreferences::wantsBrowserNotifications($user)) {
                    $this->dispatchBrowserEvent('beep');
                }
                $this->nuove = true;
            }
        }

        if ($this->conteggio) {
            $this->dispatchBrowserEvent('nuove-notifiche');
        } else {
            $this->dispatchBrowserEvent('no-notifiche');
        }

        if ($this->conteggio < 6) {
            $this->notifiche = $this->notifiche->merge(Notifica::query()
                ->where('destinatario', $destinatario)
                ->where(function ($q) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', Auth::id());
                })
                ->orderByDesc('id')
                ->whereHas('letture', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->withCount(['letture' => function ($q) {
                    $q->where('user_id', Auth::id());
                }])
                ->limit(6 - $this->conteggio)
                ->get());
        }
    }

    public function hydrate()
    {
        $this->aggiornaNotifiche();

    }

    public function visto($id)
    {
        $visto = new NotificaLettura;
        $visto->notifica_id = $id;
        $visto->user_id = Auth::id();
        $visto->save();
        $this->aggiornaNotifiche();
    }

    public function letteTutte()
    {
        foreach ($this->notifiche as $notifica) {
            $visto = new NotificaLettura;
            $visto->notifica_id = $notifica->id;
            $visto->user_id = Auth::id();
            $visto->save();
        }
        $this->aggiornaNotifiche();
    }
}
