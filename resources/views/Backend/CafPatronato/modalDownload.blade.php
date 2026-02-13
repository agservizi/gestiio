@extends('Backend._components.modal')
@section('content')
    @if($recordsCliente->count())
        <h4 class="mt-6">Per il cliente</h4>
        <div class="table-responsive">
            <table id="" class="table align-middle table-row-dashed fs-6 gy-5">
                <!--begin::Table head-->
                <thead>
                <!--begin::Table row-->
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-250px">Nome</th>
                    <th class="min-w-10px text-end">Dimensione</th>
                    <th class="min-w-125px">Data caricamento</th>
                </tr>
                <!--end::Table row-->
                </thead>
                <!--end::Table head-->
                <!--begin::Table body-->
                <tbody class="fw-semibold text-gray-600">
                @foreach($recordsCliente as $record)
                    <tr>
                        <!--begin::Name=-->
                        <td>
                            <div class="d-flex align-items-center">
                                @switch($record->tipo_file)
                                    @case('pdf')
                                        <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                                                              fill="currentColor"/>
																		<path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
																	</svg>
																</span>
                                        <!--end::Svg Icon-->
                                        @break


                                    @case('immagine')
                                        <!--begin::Svg Icon | path: icons/duotune/general/gen006.svg-->
                                        <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3"
                                                                              d="M22 5V19C22 19.6 21.6 20 21 20H19.5L11.9 12.4C11.5 12 10.9 12 10.5 12.4L3 20C2.5 20 2 19.5 2 19V5C2 4.4 2.4 4 3 4H21C21.6 4 22 4.4 22 5ZM7.5 7C6.7 7 6 7.7 6 8.5C6 9.3 6.7 10 7.5 10C8.3 10 9 9.3 9 8.5C9 7.7 8.3 7 7.5 7Z"
                                                                              fill="currentColor"/>
																		<path d="M19.1 10C18.7 9.60001 18.1 9.60001 17.7 10L10.7 17H2V19C2 19.6 2.4 20 3 20H21C21.6 20 22 19.6 22 19V12.9L19.1 10Z"
                                                                              fill="currentColor"/>
																	</svg>
																</span>
                                        <!--end::Svg Icon-->
                                        @break

                                @endswitch
                                <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'downloadAllegato'],[$record->caf_patronato_id,$record->id])}}"
                                   class="text-gray-800 text-hover-primary">{{$record->filename_originale}}</a>
                            </div>
                        </td>
                        <!--end::Name=-->
                        <!--begin::Size-->
                        <td class="text-end">{{\App\humanFileSize($record->dimensione_file)}}</td>
                        <!--end::Size-->
                        <!--begin::Last modified-->
                        <td>{{$record->created_at->format('d/m/Y')}}</td>
                        <!--end::Last modified-->
                    </tr>
                @endforeach
                </tbody>
                <!--end::Table body-->
            </table>
        </div>
    @endif
    <h4 class="mt-6">Del cliente</h4>

    <div class="table-responsive">
        <table id="" class="table align-middle table-row-dashed fs-6 gy-5">
            <!--begin::Table head-->
            <thead>
            <!--begin::Table row-->
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th class="min-w-250px">Nome</th>
                <th class="min-w-10px text-end">Dimensione</th>
                <th class="min-w-125px">Data caricamento</th>
            </tr>
            <!--end::Table row-->
            </thead>
            <!--end::Table head-->
            <!--begin::Table body-->
            <tbody class="fw-semibold text-gray-600">
            @foreach($records as $record)
                <tr>
                    <!--begin::Name=-->
                    <td>
                        <div class="d-flex align-items-center">
                            @switch($record->tipo_file)
                                @case('pdf')
                                    <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                                    <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                                                              fill="currentColor"/>
																		<path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
																	</svg>
																</span>
                                    <!--end::Svg Icon-->
                                    @break


                                @case('immagine')
                                    <!--begin::Svg Icon | path: icons/duotune/general/gen006.svg-->
                                    <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3"
                                                                              d="M22 5V19C22 19.6 21.6 20 21 20H19.5L11.9 12.4C11.5 12 10.9 12 10.5 12.4L3 20C2.5 20 2 19.5 2 19V5C2 4.4 2.4 4 3 4H21C21.6 4 22 4.4 22 5ZM7.5 7C6.7 7 6 7.7 6 8.5C6 9.3 6.7 10 7.5 10C8.3 10 9 9.3 9 8.5C9 7.7 8.3 7 7.5 7Z"
                                                                              fill="currentColor"/>
																		<path d="M19.1 10C18.7 9.60001 18.1 9.60001 17.7 10L10.7 17H2V19C2 19.6 2.4 20 3 20H21C21.6 20 22 19.6 22 19V12.9L19.1 10Z"
                                                                              fill="currentColor"/>
																	</svg>
																</span>
                                    <!--end::Svg Icon-->
                                    @break

                            @endswitch
                            <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'downloadAllegato'],[$record->caf_patronato_id,$record->id])}}"
                               class="text-gray-800 text-hover-primary">{{$record->filename_originale}}</a>
                        </div>
                    </td>
                    <!--end::Name=-->
                    <!--begin::Size-->
                    <td class="text-end">{{\App\humanFileSize($record->dimensione_file)}}</td>
                    <!--end::Size-->
                    <!--begin::Last modified-->
                    <td>{{$record->created_at->format('d/m/Y')}}</td>
                    <!--end::Last modified-->
                </tr>
            @endforeach
            </tbody>
            <!--end::Table body-->
        </table>
    </div>
@endsection
