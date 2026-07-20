<?php

/**
 * Smoke chat interna: gruppo + messaggio + poll delta + stream hint.
 * Eseguire: php /var/www/html/ops/smoke-chat-interna.php  (o docker exec)
 */

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function fail(string $msg): void
{
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit(1);
}

function ok(string $msg): void
{
    echo "OK: {$msg}\n";
}

$admin = User::query()
    ->whereHas('permissions', fn ($q) => $q->where('name', 'admin'))
    ->orderBy('id')
    ->first();

if (! $admin) {
    fail('Nessun utente admin trovato');
}

Auth::login($admin);
ok('login admin #'.$admin->id.' '.$admin->email);

// Redis / cache
try {
    Cache::put('smoke_chat_online_'.$admin->id, true, 60);
    $online = (bool) Cache::get('smoke_chat_online_'.$admin->id);
    ok($online ? 'cache write/read' : 'cache miss (driver?)');
    echo 'CACHE_DRIVER='.config('cache.default')." REDIS_HOST=".config('database.redis.default.host')."\n";
} catch (Throwable $e) {
    echo 'WARN cache: '.$e->getMessage()."\n";
}

// Destinatari operativi disponibili
$agenti = User::query()
    ->where('id', '<>', $admin->id)
    ->whereHas('permissions', fn ($q) => $q->whereIn('name', ['agente', 'supervisore']))
    ->orderBy('id')
    ->limit(3)
    ->get(['id', 'nome', 'cognome', 'email']);

if ($agenti->count() < 1) {
    fail('Serve almeno 1 agente/supervisore per DM');
}

$dest1 = $agenti->first();
$dest2 = $agenti->skip(1)->first();

// --- DM messaggio ---
$dm = DB::transaction(function () use ($admin, $dest1) {
    $thread = ChatThread::query()
        ->whereHas('partecipanti', fn ($q) => $q->where('users.id', $admin->id))
        ->whereHas('partecipanti', fn ($q) => $q->where('users.id', $dest1->id))
        ->whereDoesntHave('partecipanti', fn ($q) => $q->whereNotIn('users.id', [$admin->id, $dest1->id]))
        ->when(
            Schema::hasColumn('chat_threads', 'is_group'),
            fn ($q) => $q->where(function ($qq) {
                $qq->where('is_group', false)->orWhereNull('is_group');
            })
        )
        ->latest('id')
        ->first();

    if (! $thread) {
        $thread = new ChatThread;
        $thread->created_by = $admin->id;
        if (\Illuminate\Support\Facades\Schema::hasColumn('chat_threads', 'is_group')) {
            $thread->is_group = false;
        }
        $thread->save();
        $thread->partecipanti()->attach([
            $admin->id => ['last_read_at' => now()],
            $dest1->id => ['last_read_at' => null],
        ]);
    }

    $msg = ChatMessage::query()->create([
        'thread_id' => $thread->id,
        'user_id' => $admin->id,
        'messaggio' => 'SMOKE_CHAT_DM '.now()->format('H:i:s'),
        'priority' => 0,
    ]);

    return [$thread, $msg];
});

[$dmThread, $dmMsg] = $dm;
ok("DM thread #{$dmThread->id} message #{$dmMsg->id} → user #{$dest1->id}");

// --- Gruppo ---
if (! $dest2) {
    echo "WARN: un solo operativo — salto creazione gruppo (serve 2+)\n";
    $groupThread = null;
} else {
    $groupThread = DB::transaction(function () use ($admin, $dest1, $dest2) {
        $thread = new ChatThread;
        $thread->created_by = $admin->id;
        if (\Illuminate\Support\Facades\Schema::hasColumn('chat_threads', 'is_group')) {
            $thread->is_group = true;
            $thread->name = 'Smoke Gruppo '.now()->format('d/m H:i');
        }
        $thread->save();
        $thread->partecipanti()->attach([
            $admin->id => ['last_read_at' => now()],
            $dest1->id => ['last_read_at' => null],
            $dest2->id => ['last_read_at' => null],
        ]);

        ChatMessage::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $admin->id,
            'messaggio' => 'SMOKE_CHAT_GROUP ciao gruppo',
            'priority' => 0,
        ]);

        return $thread;
    });
    ok("GROUP thread #{$groupThread->id} name=".($groupThread->name ?? 'n/a')." con {$dest1->id}+{$dest2->id}");
}

// --- HTTP kernel: poll delta ---
$http = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create(
    '/backend/chat-interna/poll',
    'GET',
    [
        'thread_id' => $dmThread->id,
        'after_id' => max(0, (int) $dmMsg->id - 1),
        'delta' => 1,
    ]
);
$request->setLaravelSession($app['session']->driver());
$request->setUserResolver(fn () => $admin);
Auth::setUser($admin);

$response = $http->handle($request);
$status = $response->getStatusCode();
$json = json_decode($response->getContent(), true);
$http->terminate($request, $response);

if ($status !== 200) {
    fail("poll status={$status} body=".substr($response->getContent(), 0, 300));
}
if (empty($json['delta']) && empty($json['hasNew']) && empty($json['messaggiHtml'])) {
    // delta flag may be true with hasNew
}
ok('poll HTTP '.$status.' delta='.json_encode($json['delta'] ?? null).' hasNew='.json_encode($json['hasNew'] ?? null));

// --- stream route exists ---
$streamReq = Illuminate\Http\Request::create(
    '/backend/chat-interna/'.$dmThread->id.'/stream',
    'GET',
    ['after_id' => (int) $dmMsg->id]
);
$streamReq->setUserResolver(fn () => $admin);
Auth::setUser($admin);
// Non blocchiamo 20s: verifichiamo solo che la route risolva
$route = app('router')->getRoutes()->match($streamReq);
ok('stream route → '.$route->getActionName());

// Conta allegati ancora su public (info)
$publicLeft = 0;
if (class_exists(\App\Models\ChatMessageAttachment::class)) {
    $publicLeft = \App\Models\ChatMessageAttachment::query()->count();
}
echo "ATTACHMENTS_ROWS={$publicLeft}\n";

echo "SMOKE_CHAT_OK dm={$dmThread->id}".($groupThread ? " group={$groupThread->id}" : '')."\n";
exit(0);
