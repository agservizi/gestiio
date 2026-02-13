<div class="d-flex flex-stack">
    <!--begin::Folder path-->
    <div class="badge badge-lg badge-light-primary">
        <div class="d-flex align-items-center flex-wrap">

            <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'])}}">Root</a>
            @foreach($cartellePrev as $prev)
                <!--begin::Svg Icon | path: icons/duotune/arrows/arr071.svg-->
                <span class="svg-icon svg-icon-2x svg-icon-primary mx-1">
														<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<path d="M12.6343 12.5657L8.45001 16.75C8.0358 17.1642 8.0358 17.8358 8.45001 18.25C8.86423 18.6642 9.5358 18.6642 9.95001 18.25L15.4929 12.7071C15.8834 12.3166 15.8834 11.6834 15.4929 11.2929L9.95001 5.75C9.5358 5.33579 8.86423 5.33579 8.45001 5.75C8.0358 6.16421 8.0358 6.83579 8.45001 7.25L12.6343 11.4343C12.9467 11.7467 12.9467 12.2533 12.6343 12.5657Z"
                                                                  fill="currentColor"/>
														</svg>
													</span>
                <!--end::Svg Icon-->
                <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'],$prev->id)}}">{{$prev->nome}}</a>
            @endforeach
        </div>
    </div>
    <!--end::Folder path-->
    <!--begin::Folder Stats-->

    <div class="badge badge-lg badge-primary">
        <span id="kt_file_manager_items_counter">{{$files->count()}} files</span>

    </div>
    <!--end::Folder Stats-->
</div>
<!--end::Table header-->
<!--begin::Table-->
<table id="kt_file_manager_list" data-kt-filemanager-table="files" class="table align-middle table-row-dashed fs-6">
    <!--begin::Table head-->
    <thead>
    <!--begin::Table row-->
    <tr class="text-start text-gray-900 fw-bold fs-7 text-uppercase gs-0">

        <th class="min-w-250px">Nome</th>
        <th class="min-w-10px">Dimensione</th>
        <th class="min-w-125px">Caricato il</th>
        <th class="w-125px"></th>
    </tr>
    <!--end::Table row-->
    </thead>
    <!--end::Table head-->
    <!--begin::Table body-->
    <tbody class="fw-semibold text-gray-600">
    @foreach($cartelle as $cartella)
        <tr class="odd">
            <!--begin::Name=-->
            <td data-order="account">
                <div class="d-flex align-items-center">
                    <!--begin::Svg Icon | path: icons/duotune/files/fil012.svg-->
                    <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3" d="M10 4H21C21.6 4 22 4.4 22 5V7H10V4Z" fill="currentColor"></path>
																		<path d="M9.2 3H3C2.4 3 2 3.4 2 4V19C2 19.6 2.4 20 3 20H21C21.6 20 22 19.6 22 19V7C22 6.4 21.6 6 21 6H12L10.4 3.60001C10.2 3.20001 9.7 3 9.2 3Z"
                                                                              fill="currentColor"></path>
																	</svg>
																</span>
                    <!--end::Svg Icon-->
                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'],$cartella->id)}}"
                       class="text-gray-800 text-hover-primary">{{$cartella->nome}}</a>
                </div>
            </td>
            <!--end::Name=-->
            <!--begin::Size-->
            <td>{{$cartella->files_count}} files</td>
            <!--end::Size-->
            <!--begin::Last modified-->
            <td data-order="Invalid date">-</td>
            <!--end::Last modified-->
            <!--begin::Actions-->
            <td class="text-end" data-kt-filemanager-table="action_dropdown">
                <div class="d-flex justify-content-end">
                    @can('admin')
                        <div class="ms-2">
                            <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-primary me-2" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen052.svg-->
                                <span class="svg-icon svg-icon-5 m-0">
																			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																				<rect x="10" y="10" width="4" height="4" rx="2" fill="currentColor"></rect>
																				<rect x="17" y="10" width="4" height="4" rx="2" fill="currentColor"></rect>
																				<rect x="3" y="10" width="4" height="4" rx="2" fill="currentColor"></rect>
																			</svg>
																		</span>
                                <!--end::Svg Icon-->
                            </button>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4"
                                 data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'edit'],['cartellaId'=>$cartellaId,'cartella'=>$cartella->id])}}"
                                       class="menu-link px-3" data-target="kt_modal" data-toggle="modal-ajax">Modifica</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu-->
                            <!--end::More-->
                        </div>
                </div>
                @endcan

            </td>
            <!--end::Actions-->
        </tr>
    @endforeach
    @foreach($files as $record)
        <tr id="file_{{$record->id}}">
            <!--begin::Name=-->
            <td>
                <div class="d-flex align-items-center">
                    <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                    <span class="svg-icon svg-icon-2x svg-icon-primary me-4">
																	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                                                              fill="currentColor"/>
																		<path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
																	</svg>
																</span>
                    <!--end::Svg Icon-->
                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'download'],$record->id)}}"
                       class="text-gray-800 text-hover-primary">{{$record->filename_originale}}</a>
                </div>
            </td>
            <!--end::Name=-->
            <!--begin::Size-->
            <td>{{\App\humanFileSize($record->dimensione_file)}}</td>
            <!--end::Size-->
            <!--begin::Last modified-->
            <td>{{$record->created_at->format('d/m/Y')}}</td>
            <!--end::Last modified-->
            <!--begin::Actions-->
            <td class="text-end" data-kt-filemanager-table="action_dropdown">
                @can('admin')
                    <div class="d-flex justify-content-end">
                        <!--begin::More-->
                        <div class="ms-2">
                            <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen052.svg-->
                                <span class="svg-icon svg-icon-5 m-0">
																			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
																				<rect x="10" y="10" width="4" height="4" rx="2" fill="currentColor"/>
																				<rect x="17" y="10" width="4" height="4" rx="2" fill="currentColor"/>
																				<rect x="3" y="10" width="4" height="4" rx="2" fill="currentColor"/>
																			</svg>
																		</span>
                                <!--end::Svg Icon-->
                            </button>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4"
                                 data-kt-menu="true">

                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'cancellaFile'],['id'=>$record->id])}}"
                                       class="elimina-file menu-link text-danger px-3" data-kt-filemanager-table-filter="delete_row">Elimina</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu-->
                        </div>
                        <!--end::More-->
                    </div>
                @endcan
            </td>
            <!--end::Actions-->
        </tr>
    @endforeach
    </tbody>
    <!--end::Table body-->
</table>
