@php($editabile=Auth::user()->hasPermissionTo('admin'))
@foreach($records as $record)
    <div class="col-md-3 col-lg-3 col-xl-2">
        <!--begin::Card-->
        <div class="card h-100 ribbon ribbon-top">
            @if($editabile)
            <div class="ribbon-label bg-primary"><a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'edit'],$record->id)}}"
                                                    data-target="kt_modal" data-toggle="modal-ajax"
                                                    class=""><i class="bi bi-pencil-square text-gray-100"></i></a></div>
            @endif
            <!--begin::Card body-->
            <div class="card-body d-flex justify-content-center text-center flex-column p-8 ">
                <!--begin::Name-->
                <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'show'],$record->id)}}"
                   class="text-gray-800 text-hover-primary d-flex flex-column cartella">
                    <!--begin::Image-->
                    <div class="symbol symbol-75px mb-5">
                        <img src="/assets_backend/media/svg/files/folder-document.svg" class="theme-light-show" alt=""/>
                        <img src="/assets_backend/media/svg/files/folder-document-dark.svg" class="theme-dark-show" alt=""/>
                    </div>
                    <!--end::Image-->
                    <!--begin::Title-->
                    <div class="fs-5 fw-bold mb-2 nome-cartella {{$cartellaId==$record->id?'text-green-jungle':''}}" id="nome_{{$record->id}}">{{$record->nome}}</div>
                    <!--end::Title-->
                </a>
                <!--end::Name-->
                <!--begin::Description-->
                <div class="fs-7 fw-semibold text-gray-400">{{$record->files_count}} files</div>
                <!--end::Description-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
@endforeach

@isset($reload)
    <script>
        handlerCartelle();
    </script>

@endisset

