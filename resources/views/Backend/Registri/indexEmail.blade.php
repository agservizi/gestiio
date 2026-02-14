@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('content')
    <div class="card">
        <div class="card-body">
            <form method="get" action="{{action([$controller,'index'],['cosa'=>'email'])}}" class="row g-3 mb-6">
                <div class="col-md-3">
                    <label class="form-label">Giorno</label>
                    <input type="text" class="form-control" name="giorno" value="{{request('giorno')}}" placeholder="gg/mm/aaaa">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Modulo</label>
                    <select name="modulo" class="form-select">
                        <option value="">Tutti i moduli</option>
                        @foreach(($moduli ?? []) as $key => $label)
                            <option value="{{$key}}" @selected(request('modulo')===$key)>{{$label}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end gap-3">
                    <button type="submit" class="btn btn-primary">Filtra</button>
                    <a href="{{action([$controller,'index'],['cosa'=>'email'])}}" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th> Data ora</th>
                        <th> A</th>
                        <th>Oggetto</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $record)
                        <tr class="">
                            <td> {{$record->data->format('d/m/Y H:i:s')}} </td>
                            <td>
                                {{$record->to}}

                            </td>
                            <td>
                                {{$record->subject}}
                            </td>
                            <td class="text-end">
                                <a href="{{action([$controller,'index'],['cosa'=>'email','email_id'=>$record->id])}}"
                                   data-target="kt_modal" data-toggle="modal-ajax"
                                   class="btn btn-light-success btn-xs"

                                >Vedi</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="w-100 text-center py-4">
            {{$records->links()}}
        </div>
    </div>
@endsection

