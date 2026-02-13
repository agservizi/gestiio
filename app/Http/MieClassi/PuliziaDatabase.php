<?php

namespace App\Http\MieClassi;

use App\Models\AllegatoContratto;
use App\Models\RegistroEmail;
use App\Models\RegistroLogin;
use Carbon\Carbon;

class PuliziaDatabase
{
    /**
     * @param $mesi
     * @return int Record eliminati
     */
    static public function pulisciRegistroLogin($mesi): int
    {
        $limit = Carbon::now()->subMonths($mesi)->toDateTimeString();
        return RegistroLogin::where('created_at', '<', $limit)->delete();
    }

    /**
     * @param $mesi
     * @return int Record eliminati
     */
    static public function pulisciRegistroLoginFalliti($mesi): int
    {
        $limit = Carbon::now()->subMonths($mesi)->toDateTimeString();
        return RegistroLogin::where('created_at', '<', $limit)->where('riuscito', '=', 0)->delete();
    }

    /**
     * @param $mesi
     * @return int Record eliminati
     */
    static public function pulisciAllegatiOrfani($mesi): int
    {
        $limit = Carbon::now()->subMonths($mesi)->toDateTimeString();
        $records = AllegatoContratto::where('created_at', '<', $limit)->whereNull('contratto_id')->get();
        foreach ($records as $record) {
            $record->delete();
        }

        return count($records);
    }

    /**
     * @param $mesi
     * @return int Record eliminati
     */
    static public function pulisciRegistroEmail($mesi): int
    {
        $limit = Carbon::now()->subMonths($mesi)->toDateTimeString();
        return RegistroEmail::where('data', '<', $limit)->delete();
    }



}
