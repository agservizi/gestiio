@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Prodotto</th>
                        <th>Data acquisto</th>
                        <th>Venditore</th>
                        <th>Licenza</th>
                        <th>Sito web</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $record)
                        <tr id="{{$record->id}}">
                            <td>
                                {{$record->nome}}
                            </td>
                            <td>
                                {{$record->data_acquisto->format('d/m/Y')}}
                            </td>
                            <td>
                                {{$record->venditore}}
                            </td>
                            <td>
                                {{$record->numero_licenza}}
                            </td>
                            <td>
                                @if($record->url)
                                    <a href="{{$record->url}}" target="_blank"
                                       class="btn btn-sm btn-success">Vedi</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endsection

