<?php


namespace App\Http;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;


class HelperForMetronic
{

    protected const CONTAINER = false;

    public const SIDEBAR = true;
    public const SIDEBAR_LIGHT_DARK = 'dark';

    public static function ktHeaderHeader()
    {
        return self::CONTAINER ? 'container-xxl' : 'container-fluid';
    }

    public static function latout()
    {
        return self::SIDEBAR ? 'data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true"
      data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true"
      data-kt-app-toolbar-enabled="true" class="app-default"' : 'data-kt-app-layout="light-header" data-kt-app-header-fixed="true" data-kt-app-toolbar-enabled="true" class="app-default"';
    }


    public static function breadCrumbs($arr)
    {

    }

    public static function labelSegnalazione($risolto)
    {
        switch ($risolto) {
            case 50:
                return '<span class="badge badge-light-success">Completato</span>';
            case 25:
                return '<span class="badge badge-light-warning">Parzialmente</span>';
            case 0:
                return '<span class="badge badge-light-info">Da fare</span>';
        }

    }

    public static function iconaRegistro($evento)
    {
        switch ($evento) {
            case 'created':
                return '<i class="fa fa-plus-square"></i>';
            case 'updated':
                return '<i class="fa fa-pencil-square"></i>';
            case 'deleted':
                return '<i class="fa fa-trash-square"></i>';
        }

    }


    public static function userLevel($small, $user = null)
    {
        if ($small) {
            $small = 'fs-8 px-4 py-3';
        } else {
            $small = '';
        }
        if (!$user) {
            $user = Auth::user();
        }
        if ($user->hasPermissionTo('admin')) {
            return '<span class="badge badge-light-info fw-bolder ' . $small . '">Admin</span>';
        }
        if ($user->hasPermissionTo('agente')) {
            return '<span class="badge badge-light-info fw-bolder ' . $small . '">Agente</span>';
        }


    }


}
