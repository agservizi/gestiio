<?php

use App\Models\ChatThreadUser;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{threadId}', function ($user, $threadId) {
    return ChatThreadUser::query()
        ->where('thread_id', $threadId)
        ->where('user_id', $user->id)
        ->exists();
});
