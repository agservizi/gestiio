@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a class="btn btn-sm btn-primary" data-targetZ="kt_modal" data-toggleZ="modal-ajax"
           href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],['backup-db','esegui'])}}">Esegui backup</a>
    </div>
@endsection
@section('content')
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Riepilogo</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        @foreach($headers as $th)
                            <th class="text-center"> {{$th}}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="">
                        @foreach($rows[0] as $td)
                            <td class="text-center"> {{$td}} </td>
                        @endforeach
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Elenco files</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th class="">File</th>
                        <th class="">Eseguito</th>
                        <th class="text-end">Dimensione</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($files->reverse() as $file)
                        @php($pathinfo=pathinfo($file['path']))
                        @if(isset($pathinfo['extension']) && $pathinfo['extension']=='zip')
                            <tr class="">
                                <td class=""> {{$pathinfo['basename']}} </td>
                                <td class=""> {{\Carbon\Carbon::createFromFormat('Y-m-d-H-i-s',$pathinfo['filename'])->diffForHumans()}} </td>
                                <td class="text-end"> {{\App\humanFileSize($file['fileSize'])}}</td>
                                <td class="text-end">
                                    <a href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],['cosa'=>'backup-db','scarica'=>$pathinfo['basename']])}}">Scarica</a>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

