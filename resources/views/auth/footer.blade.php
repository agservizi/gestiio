<div class="d-flex fw-semibold text-primary fs-base" id="kt_example_js_footer">
    <a href="mailto:" class=" text-hover-primary px-2 ">Contattami</a>
    @guest()
        <a href="{{route('login')}}" class=" text-hover-primary px-2">Accedi</a>
        <a href="{{route('register')}}" class=" text-hover-primary px-2">Registrati</a>
    @endguest
    <a href="/policies" class=" text-hover-primary px-2">Termini e condizioni</a>
    <a href="javascript:void(0)" id="open_preferences_center" class=" text-hover-primary px-2">Aggiorna preferenze cookies</a>
    @if(false)
        <a href="{{route('register')}}" class=" text-hover-primary px-2">Registrati</a>
    @endif
</div>

