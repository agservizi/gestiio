<div class="alert alert-danger  alert-not-autoClose" id="alert-errori" style="display: {{$errors->any()?'':'none;'}}">
    Ci sono alcuni errori nel form:
    <div id="elenco-errori">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
