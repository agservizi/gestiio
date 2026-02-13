@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('content')
    <div class="card">
        <div class="card-body">
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

