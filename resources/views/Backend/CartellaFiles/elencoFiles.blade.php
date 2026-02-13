@php($editabile=Auth::user()->hasPermissionTo('admin'))
@if($cartellaId)
    <div class="d-flex justify-content-between">
        <h3>{{\App\Models\CartellaFiles::find($cartellaId)->nome}}</h3>
        @if($editabile)
            <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['upload-documento',$cartellaId])}}"><span
                        class="d-md-none">+</span><span
                        class="d-none d-md-block">Upload</span></a>
        @endif
    </div>
@endif
<div class="row g-6 g-xl-9 mb-6 mb-xl-9">
    @foreach($files as $record)
        <!--begin::Col-->
        <div class="col-md-3 col-lg-3 col-xl-2" id="file_{{$record->id}}">
            <!--begin::Card-->
            <div class="card h-100 ribbon ribbon-top">
                @if($editabile)
                    <div class="ribbon-label bg-danger"><a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'cancellaFile'],['id'=>$record->id])}}"
                                                            class="elimina-file"><i class="bi bi-trash text-gray-100"></i></a></div>
                @endif
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'download'],$record->id)}}" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/{{$record->tipo_file}}.svg" alt=""/>

                        @if(false)
                            <img src="assets_backend/media/svg/files/{{$record->tipo_file}}.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/{{$record->tipo_file}}-dark.svg" class="theme-dark-show" alt=""/>
                                @endif
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">{{$record->filename_originale}}</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">{{\App\humanFileSize($record->dimensione_file)}}</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    @endforeach

    @isset($reload)
        <script>
            eliminaFileHandler();
        </script>

        @endisset

    @if(false)
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/doc.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/doc-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">CRM App Docs..</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">3 days ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/css.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/css-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">User CRUD Styles</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">4 days ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/ai.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/ai-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">Product Logo</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">5 days ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/sql.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/sql-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">Orders backup</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">1 week ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/xml.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/xml-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">UTAIR CRM API Co..</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">2 weeks ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-4 col-xl-3">
            <!--begin::Card-->
            <div class="card h-100">
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                    <!--begin::Name-->
                    <a href="../../demo1/dist/apps/file-manager/files.html" class="text-gray-800 text-hover-primary d-flex flex-column">
                        <!--begin::Image-->
                        <div class="symbol symbol-60px mb-5">
                            <img src="assets_backend/media/svg/files/tif.svg" class="theme-light-show" alt=""/>
                            <img src="assets_backend/media/svg/files/tif-dark.svg" class="theme-dark-show" alt=""/>
                        </div>
                        <!--end::Image-->
                        <!--begin::Title-->
                        <div class="fs-5 fw-bold mb-2">Tower Hill App..</div>
                        <!--end::Title-->
                    </a>
                    <!--end::Name-->
                    <!--begin::Description-->
                    <div class="fs-7 fw-semibold text-gray-400">3 weeks ago</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    @endif
    <!--end::Col-->
</div>
