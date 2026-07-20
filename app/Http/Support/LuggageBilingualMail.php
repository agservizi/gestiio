<?php

namespace App\Http\Support;

use Illuminate\Support\HtmlString;

class LuggageBilingualMail
{
    public static function subject(string $english, string $italian, string $suffix = ''): string
    {
        $en = trim($english.' '.$suffix);
        $it = trim($italian.' '.$suffix);

        return $en.' | '.$it;
    }

    public static function greeting(string $name, string $english, string $italian): HtmlString
    {
        return new HtmlString(
            '<p style="margin:0 0 4px;font-size:18px;font-weight:600;color:#111827;">'
            .e($english).' '.e($name).',</p>'
            .'<p style="margin:0 0 18px;font-size:13px;line-height:1.4;color:#6b7280;">'
            .e($italian).' '.e($name).',</p>'
        );
    }

    public static function line(string $english, string $italian): HtmlString
    {
        return new HtmlString(
            '<p style="margin:0 0 4px;color:#111827;">'.e($english).'</p>'
            .'<p style="margin:0 0 18px;font-size:13px;line-height:1.4;color:#6b7280;">'.e($italian).'</p>'
        );
    }

    public static function actionLabel(string $english, string $italian): string
    {
        return $english.' · '.$italian;
    }
}
