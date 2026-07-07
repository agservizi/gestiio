@extends('Backend._components.modal')
@section('content')

    <h4>{{$record->titolo}}</h4>
    @if($record->immagine)
        <img src="{{ $record->urlImmagine() }}" alt="" class="w-100 rounded mb-4">
    @endif
    {!! $record->testo !!}

    <script>
        Livewire.emit('aggiornaNotifiche');
    </script>
@endsection
