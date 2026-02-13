@extends('Backend._components.modal')
@section('content')

    <h4>{{$record->titolo}}</h4>
    {!! $record->testo !!}

    <script>
        Livewire.emit('aggiornaNotifiche');
    </script>
@endsection
