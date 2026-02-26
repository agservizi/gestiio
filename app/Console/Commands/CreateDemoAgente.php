<?php

namespace App\Console\Commands;

use App\Models\Agente;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class CreateDemoAgente extends Command
{
    protected $signature = 'agente:create-demo
        {--email=demo.agente@gestiio.local : Email account demo}
        {--password=Demo123456! : Password account demo}
        {--nome=Demo : Nome agente}
        {--cognome=Agente : Cognome agente}
        {--telefono=3330000000 : Telefono agente}
        {--force-password : Forza reset password anche se utente gia esistente}';

    protected $description = 'Crea o aggiorna un account demo con permesso agente';

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->error('Connessione DB non disponibile. Verifica .env/host DB.');
            $this->line('Dettaglio: ' . $e->getMessage());
            return self::FAILURE;
        }

        $email = strtolower((string) $this->option('email'));
        $password = (string) $this->option('password');
        $nome = trim((string) $this->option('nome'));
        $cognome = trim((string) $this->option('cognome'));
        $telefono = trim((string) $this->option('telefono'));

        if ($email === '' || $nome === '' || $cognome === '' || $telefono === '') {
            $this->error('Email, nome, cognome e telefono sono obbligatori.');
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $user = User::query()->where('email', $email)->first();
            $isNewUser = false;

            if (!$user) {
                $user = new User();
                $isNewUser = true;
                $user->email = $email;
            }

            $user->nome = $nome;
            $user->cognome = $cognome;
            $user->telefono = $telefono;
            $user->alias = $cognome . ' ' . $nome;

            if ($isNewUser || (bool) $this->option('force-password')) {
                $user->password = Hash::make($password);
            }

            if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            $user->save();

            $agente = Agente::query()->firstOrNew(['user_id' => $user->id]);
            $agente->ragione_sociale = $agente->ragione_sociale ?: ($cognome . ' ' . $nome);
            $agente->save();

            Permission::findOrCreate('agente');
            $user->syncPermissions(['agente']);

            DB::commit();

            $this->info($isNewUser ? 'Account demo agente creato.' : 'Account demo agente aggiornato.');
            $this->line('ID utente: ' . $user->id);
            $this->line('Email: ' . $email);
            $this->line('Password: ' . ($isNewUser || (bool) $this->option('force-password') ? $password : '[invariata]'));
            $this->line('Permesso: agente');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Errore creazione account demo agente: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
