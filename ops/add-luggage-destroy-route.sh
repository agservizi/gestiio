#!/bin/sh
set -e
APP=/home/Carmine/apps/gestiio-20260624-2128
ROUTES=$APP/routes/web-backend.php
if ! grep -q "LuggageDepositController::class, 'destroy'" "$ROUTES"; then
  sed -i "/Route::post('{id}\/action', \[LuggageDepositController::class, 'action'\]);/a\\
        Route::delete('{id}', [LuggageDepositController::class, 'destroy']);" "$ROUTES"
fi
