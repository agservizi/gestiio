@if(request()->ajax())
    @include('Backend._components.modal')
@elseif(Auth::user()->hasAnyPermission(['agente','operatore']))
    @include('Backend._layout._main-Header')
@else
    @include('Backend._layout._main-Sidebar')
@endif
