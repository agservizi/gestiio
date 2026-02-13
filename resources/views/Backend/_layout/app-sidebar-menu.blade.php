@if(Auth::user()->hasAnyPermission(['admin','agente']))
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
             data-kt-scroll="true" data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
             data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu"
                 data-kt-menu="true" data-kt-menu-expand="false">
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <rect x="2" y="2" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="13" y="2" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="13" y="13" width="9" height="9" rx="2" fill="currentColor"/>
                                    <rect opacity="0.3" x="2" y="13" width="9" height="9" rx="2" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Dashboards</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Contratti Telefonia</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Contratti Energia</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Caf / Patronato</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/general/gen019.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3"
                                  d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22ZM12.5 18C12.5 17.4 12.6 17.5 12 17.5H8.5C7.9 17.5 8 17.4 8 18C8 18.6 7.9 18.5 8.5 18.5L12 18C12.6 18 12.5 18.6 12.5 18ZM16.5 13C16.5 12.4 16.6 12.5 16 12.5H8.5C7.9 12.5 8 12.4 8 13C8 13.6 7.9 13.5 8.5 13.5H15.5C16.1 13.5 16.5 13.6 16.5 13ZM12.5 8C12.5 7.4 12.6 7.5 12 7.5H8C7.4 7.5 7.5 7.4 7.5 8C7.5 8.6 7.4 8.5 8 8.5H12C12.6 8.5 12.5 8.6 12.5 8Z"
                                  fill="currentColor"/>
                            <rect x="7" y="17" width="6" height="2" rx="1" fill="currentColor"/>
                            <rect x="7" y="12" width="10" height="2" rx="1" fill="currentColor"/>
                            <rect x="7" y="7" width="6" height="2" rx="1" fill="currentColor"/>
                            <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                            </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                        <span class="menu-title">Visure</span>
                    </a>
                </div>


                @if(Auth::user()->hasAnyPermission(['admin']))
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                            <span class="menu-title">Spedizione BRT</span>
                        </a>
                    </div>
                @endif
                @can('admin')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\ClienteAssistenzaController::class,'index'])}}">
											<span class="menu-icon">
												<span class="svg-icon svg-icon-2">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16.0173 9H15.3945C14.2833 9 13.263 9.61425 12.7431 10.5963L12.154 11.7091C12.0645 11.8781 12.1072 12.0868 12.2559 12.2071L12.6402 12.5183C13.2631 13.0225 13.7556 13.6691 14.0764 14.4035L14.2321 14.7601C14.2957 14.9058 14.4396 15 14.5987 15H18.6747C19.7297 15 20.4057 13.8774 19.912 12.945L18.6686 10.5963C18.1487 9.61425 17.1285 9 16.0173 9Z"
                                                      fill="currentColor"/>
                                                <rect opacity="0.3" x="14" y="4" width="4" height="4" rx="2"
                                                      fill="currentColor"/>
                                                <path d="M4.65486 14.8559C5.40389 13.1224 7.11161 12 9 12C10.8884 12 12.5961 13.1224 13.3451 14.8559L14.793 18.2067C15.3636 19.5271 14.3955 21 12.9571 21H5.04292C3.60453 21 2.63644 19.5271 3.20698 18.2067L4.65486 14.8559Z"
                                                      fill="currentColor"/>
                                                <rect opacity="0.3" x="6" y="5" width="6" height="6" rx="3"
                                                      fill="currentColor"/>
                                                </svg>
                                                </span>
                                            </span>
                            <span class="menu-title">Clienti assistenza</span>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class,'index'])}}">
											<span class="menu-icon">
												<span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                                                </span>
                                            </span>
                            <span class="menu-title">Richieste assistenza</span>
                        </a>
                    </div>

                    <div class="menu-item">

                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\ClienteController::class,'index'])}}">
											<span class="menu-icon">
												<span class="svg-icon svg-icon-2">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16.0173 9H15.3945C14.2833 9 13.263 9.61425 12.7431 10.5963L12.154 11.7091C12.0645 11.8781 12.1072 12.0868 12.2559 12.2071L12.6402 12.5183C13.2631 13.0225 13.7556 13.6691 14.0764 14.4035L14.2321 14.7601C14.2957 14.9058 14.4396 15 14.5987 15H18.6747C19.7297 15 20.4057 13.8774 19.912 12.945L18.6686 10.5963C18.1487 9.61425 17.1285 9 16.0173 9Z"
                                                      fill="currentColor"/>
                                                <rect opacity="0.3" x="14" y="4" width="4" height="4" rx="2"
                                                      fill="currentColor"/>
                                                <path d="M4.65486 14.8559C5.40389 13.1224 7.11161 12 9 12C10.8884 12 12.5961 13.1224 13.3451 14.8559L14.793 18.2067C15.3636 19.5271 14.3955 21 12.9571 21H5.04292C3.60453 21 2.63644 19.5271 3.20698 18.2067L4.65486 14.8559Z"
                                                      fill="currentColor"/>
                                                <rect opacity="0.3" x="6" y="5" width="6" height="6" rx="3"
                                                      fill="currentColor"/>
                                                </svg>
                                                </span>

                                            </span>
                            <span class="menu-title">Clienti</span>
                        </a>

                    </div>
                    <div class="menu-item">

                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\AgenteController::class,'index'])}}">
											<span class="menu-icon">
												<!--begin::Svg Icon | path: icons/duotune/communication/com005.svg-->
												<span class="svg-icon svg-icon-2">
													<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
														<path d="M20 14H18V10H20C20.6 10 21 10.4 21 11V13C21 13.6 20.6 14 20 14ZM21 19V17C21 16.4 20.6 16 20 16H18V20H20C20.6 20 21 19.6 21 19ZM21 7V5C21 4.4 20.6 4 20 4H18V8H20C20.6 8 21 7.6 21 7Z"
                                                              fill="currentColor"/>
														<path opacity="0.3"
                                                              d="M17 22H3C2.4 22 2 21.6 2 21V3C2 2.4 2.4 2 3 2H17C17.6 2 18 2.4 18 3V21C18 21.6 17.6 22 17 22ZM10 7C8.9 7 8 7.9 8 9C8 10.1 8.9 11 10 11C11.1 11 12 10.1 12 9C12 7.9 11.1 7 10 7ZM13.3 16C14 16 14.5 15.3 14.3 14.7C13.7 13.2 12 12 10.1 12C8.10001 12 6.49999 13.1 5.89999 14.7C5.59999 15.3 6.19999 16 7.39999 16H13.3Z"
                                                              fill="currentColor"/>
													</svg>
												</span>
                                                <!--end::Svg Icon-->
											</span>
                            <span class="menu-title">Agenti</span>
                        </a>

                    </div>
                @endcan
                <div class="menu-item">

                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'])}}">
                        <span class="menu-icon">
                        <span class="svg-icon svg-icon-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3"
                                      d="M21.25 18.525L13.05 21.825C12.35 22.125 11.65 22.125 10.95 21.825L2.75 18.525C1.75 18.125 1.75 16.725 2.75 16.325L4.04999 15.825L10.25 18.325C10.85 18.525 11.45 18.625 12.05 18.625C12.65 18.625 13.25 18.525 13.85 18.325L20.05 15.825L21.35 16.325C22.35 16.725 22.35 18.125 21.25 18.525ZM13.05 16.425L21.25 13.125C22.25 12.725 22.25 11.325 21.25 10.925L13.05 7.62502C12.35 7.32502 11.65 7.32502 10.95 7.62502L2.75 10.925C1.75 11.325 1.75 12.725 2.75 13.125L10.95 16.425C11.65 16.725 12.45 16.725 13.05 16.425Z"
                                      fill="currentColor"></path>
                                <path d="M11.05 11.025L2.84998 7.725C1.84998 7.325 1.84998 5.925 2.84998 5.525L11.05 2.225C11.75 1.925 12.45 1.925 13.15 2.225L21.35 5.525C22.35 5.925 22.35 7.325 21.35 7.725L13.05 11.025C12.45 11.325 11.65 11.325 11.05 11.025Z"
                                      fill="currentColor"></path>
                            </svg>
                        </span>
                        </span>
                        <span class="menu-title">Documenti</span>
                    </a>

                </div>

                @if(false)
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/general/gen019.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.3"
                                  d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22ZM12.5 18C12.5 17.4 12.6 17.5 12 17.5H8.5C7.9 17.5 8 17.4 8 18C8 18.6 7.9 18.5 8.5 18.5L12 18C12.6 18 12.5 18.6 12.5 18ZM16.5 13C16.5 12.4 16.6 12.5 16 12.5H8.5C7.9 12.5 8 12.4 8 13C8 13.6 7.9 13.5 8.5 13.5H15.5C16.1 13.5 16.5 13.6 16.5 13ZM12.5 8C12.5 7.4 12.6 7.5 12 7.5H8C7.4 7.5 7.5 7.4 7.5 8C7.5 8.6 7.4 8.5 8 8.5H12C12.6 8.5 12.5 8.6 12.5 8Z"
                                  fill="currentColor"/>
                            <rect x="7" y="17" width="6" height="2" rx="1" fill="currentColor"/>
                            <rect x="7" y="12" width="10" height="2" rx="1" fill="currentColor"/>
                            <rect x="7" y="7" width="6" height="2" rx="1" fill="currentColor"/>
                            <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                            </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                        <span class="menu-title">Visure camerali</span>
                        <span class="menu-arrow"></span>
                    </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\VisuraCameraleController::class,'showCercaAzienda'])}}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                    <span class="menu-title">Cerca azienda</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\VisuraCameraleController::class,'index'])}}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                    <span class="menu-title">Richieste</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'index'])}}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                    <span class="menu-title">Portafoglio</span>
                                </a>
                            </div>
                        </div>
                        <!--end:Menu sub-->
                    </div>
                @endif
                @can('agente')
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                            <span class="menu-icon">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen025.svg-->
                                <span class="svg-icon svg-icon-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M3.20001 5.91897L16.9 3.01895C17.4 2.91895 18 3.219 18.1 3.819L19.2 9.01895L3.20001 5.91897Z"
                                          fill="currentColor"/>
                                    <path opacity="0.3"
                                          d="M13 13.9189C13 12.2189 14.3 10.9189 16 10.9189H21C21.6 10.9189 22 11.3189 22 11.9189V15.9189C22 16.5189 21.6 16.9189 21 16.9189H16C14.3 16.9189 13 15.6189 13 13.9189ZM16 12.4189C15.2 12.4189 14.5 13.1189 14.5 13.9189C14.5 14.7189 15.2 15.4189 16 15.4189C16.8 15.4189 17.5 14.7189 17.5 13.9189C17.5 13.1189 16.8 12.4189 16 12.4189Z"
                                          fill="currentColor"/>
                                    <path d="M13 13.9189C13 12.2189 14.3 10.9189 16 10.9189H21V7.91895C21 6.81895 20.1 5.91895 19 5.91895H3C2.4 5.91895 2 6.31895 2 6.91895V20.9189C2 21.5189 2.4 21.9189 3 21.9189H19C20.1 21.9189 21 21.0189 21 19.9189V16.9189H16C14.3 16.9189 13 15.6189 13 13.9189Z"
                                          fill="currentColor"/>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                            </span>
                        <span class="menu-title">Portafoglio</span>
                        <span class="menu-arrow"></span>
                    </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">

                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'index'])}}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                    <span class="menu-title">Movimenti</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                    <span class="menu-title">Carica portafoglio</span>
                                </a>
                            </div>
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <div class="menu-item">

                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\FatturaProformaController::class,'index'])}}">
                        <span class="menu-icon">
                        <span class="svg-icon svg-icon-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3"
                                      d="M21.25 18.525L13.05 21.825C12.35 22.125 11.65 22.125 10.95 21.825L2.75 18.525C1.75 18.125 1.75 16.725 2.75 16.325L4.04999 15.825L10.25 18.325C10.85 18.525 11.45 18.625 12.05 18.625C12.65 18.625 13.25 18.525 13.85 18.325L20.05 15.825L21.35 16.325C22.35 16.725 22.35 18.125 21.25 18.525ZM13.05 16.425L21.25 13.125C22.25 12.725 22.25 11.325 21.25 10.925L13.05 7.62502C12.35 7.32502 11.65 7.32502 10.95 7.62502L2.75 10.925C1.75 11.325 1.75 12.725 2.75 13.125L10.95 16.425C11.65 16.725 12.45 16.725 13.05 16.425Z"
                                      fill="currentColor"></path>
                                <path d="M11.05 11.025L2.84998 7.725C1.84998 7.325 1.84998 5.925 2.84998 5.525L11.05 2.225C11.75 1.925 12.45 1.925 13.15 2.225L21.35 5.525C22.35 5.925 22.35 7.325 21.35 7.725L13.05 11.025C12.45 11.325 11.65 11.325 11.05 11.025Z"
                                      fill="currentColor"></path>
                            </svg>
                        </span>
                        </span>
                            <span class="menu-title">Fatture proforma</span>
                        </a>

                    </div>

                @endcan
                @can('admin')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'])}}">
                            <span class="menu-icon">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen025.svg-->
                                <span class="svg-icon svg-icon-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                         xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M3.20001 5.91897L16.9 3.01895C17.4 2.91895 18 3.219 18.1 3.819L19.2 9.01895L3.20001 5.91897Z"
                                          fill="currentColor"/>
                                    <path opacity="0.3"
                                          d="M13 13.9189C13 12.2189 14.3 10.9189 16 10.9189H21C21.6 10.9189 22 11.3189 22 11.9189V15.9189C22 16.5189 21.6 16.9189 21 16.9189H16C14.3 16.9189 13 15.6189 13 13.9189ZM16 12.4189C15.2 12.4189 14.5 13.1189 14.5 13.9189C14.5 14.7189 15.2 15.4189 16 15.4189C16.8 15.4189 17.5 14.7189 17.5 13.9189C17.5 13.1189 16.8 12.4189 16 12.4189Z"
                                          fill="currentColor"/>
                                    <path d="M13 13.9189C13 12.2189 14.3 10.9189 16 10.9189H21V7.91895C21 6.81895 20.1 5.91895 19 5.91895H3C2.4 5.91895 2 6.31895 2 6.91895V20.9189C2 21.5189 2.4 21.9189 3 21.9189H19C20.1 21.9189 21 21.0189 21 19.9189V16.9189H16C14.3 16.9189 13 15.6189 13 13.9189Z"
                                          fill="currentColor"/>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                            </span>
                            <span class="menu-title">Ricarica plafond agenti</span>
                        </a>
                    </div>
                @endcan

                <div class="menu-item">

                    <a class="menu-link"
                       href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}">
											<span class="menu-icon">
												<!--begin::Svg Icon | path: icons/duotune/general/gen025.svg-->
												<span class="svg-icon svg-icon-2">
<!--begin::Svg Icon | path: /var/www/preview.keenthemes.com/kt-products/docs/metronic/html/releases/2022-10-09-043348/core/html/src/media/icons/duotune/coding/cod002.svg-->
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                             xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.3"
                                                          d="M18 22C19.7 22 21 20.7 21 19C21 18.5 20.9 18.1 20.7 17.7L15.3 6.30005C15.1 5.90005 15 5.5 15 5C15 3.3 16.3 2 18 2H6C4.3 2 3 3.3 3 5C3 5.5 3.1 5.90005 3.3 6.30005L8.7 17.7C8.9 18.1 9 18.5 9 19C9 20.7 7.7 22 6 22H18Z"
                                                          fill="currentColor"/>
                                                    <path d="M18 2C19.7 2 21 3.3 21 5H9C9 3.3 7.7 2 6 2H18Z"
                                                          fill="currentColor"/>
                                                    <path d="M9 19C9 20.7 7.7 22 6 22C4.3 22 3 20.7 3 19H9Z"
                                                          fill="currentColor"/>
                                                    </svg>
                                                    <!--end::Svg Icon-->												</span>
                                                <!--end::Svg Icon-->
											</span>
                        <span class="menu-title">Tickets
                            @php($daLeggere=\App\Http\MieClassiCache\CacheConteggioTicketsDaLeggere::get(Auth::id()))
                            @if($daLeggere)
                                <span class="badge badge-danger fw-bolder my-2 ms-2 animation-blink">
                                    {{\App\singolareOplurale($daLeggere,'nuovo','nuovi')}}
                                </span>
                            @endif
                        </span>
                    </a>
                </div>
                @can('admin')

                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\SmsController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                            <span class="menu-title">Sms</span>
                        </a>
                    </div>

                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                    <span class="menu-icon">
                        <!--begin::Svg Icon | path: icons/duotune/general/gen019.svg-->
                        <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 11H7C6.4 11 6 10.6 6 10V9C6 8.4 6.4 8 7 8H17C17.6 8 18 8.4 18 9V10C18 10.6 17.6 11 17 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z"
                                  fill="currentColor"/>
                            <path opacity="0.3"
                                  d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM18 20V19C18 18.4 17.6 18 17 18H7C6.4 18 6 18.4 6 19V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20Z"
                                  fill="currentColor"/>
                            </svg>
                        </span>
                        <!--end::Svg Icon-->
                    </span>
                    <span class="menu-title">Proforma</span>
                    <span class="menu-arrow"></span>
                </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Produzioni</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\FatturaProformaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Fatture proforma</span>
                                </a>
                            </div>

                        </div>
                        <!--end:Menu sub-->
                    </div>





                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">

                    <span class="menu-link">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.5 11H6.5C4 11 2 9 2 6.5C2 4 4 2 6.5 2H17.5C20 2 22 4 22 6.5C22 9 20 11 17.5 11ZM15 6.5C15 7.9 16.1 9 17.5 9C18.9 9 20 7.9 20 6.5C20 5.1 18.9 4 17.5 4C16.1 4 15 5.1 15 6.5Z"
                                          fill="currentColor"/>
                                    <path opacity="0.3"
                                          d="M17.5 22H6.5C4 22 2 20 2 17.5C2 15 4 13 6.5 13H17.5C20 13 22 15 22 17.5C22 20 20 22 17.5 22ZM4 17.5C4 18.9 5.1 20 6.5 20C7.9 20 9 18.9 9 17.5C9 16.1 7.9 15 6.5 15C5.1 15 4 16.1 4 17.5Z"
                                          fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                        <span class="menu-title">Impostazioni</span>
                        <span class="menu-arrow"></span>
                    </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Contratti Telefonia</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\GestoreController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Gestori</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoContrattoController::class,'index'])}}">
													<span class="menu-bullet">
														<span class="bullet bullet-dot"></span>
													</span>
                                            <span class="menu-title">Tipi contratto</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoTelefoniaController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Listini</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Contratti Energia</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoContrattoEnergiaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\GestoreContrattoEnergiaController::class,'index'])}}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Gestori</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Caf Patronato</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoCafPatronatoController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoCafPatronatoController::class,'index'])}}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                            <span class="menu-title">Tipo caf patronato</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Visure</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\EsitoVisuraController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Esiti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\TipoVisuraController::class,'index'])}}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                            <span class="menu-title">Tipi visure</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Assistenza</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ProdottoAssistenzaController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Prodotti</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>

                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Spedizioni BRT</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'showPrezziAgenti'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Ricarico agenti</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoBrtController::class,'index'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Listino Italia</span>
                                        </a>
                                    </div>
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\ListinoBrtEuropaController::class,'index'])}}">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                            <span class="menu-title">Listino Europa</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link">
                                    <span class="menu-title">Ticket</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link"
                                           href="{{action([\App\Http\Controllers\Backend\CausaleTicketController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                            <span class="menu-title">Causali ticket</span>
                                        </a>
                                    </div>
                                </div>
                                <!--end:Menu sub-->
                            </div>


                        </div>
                        <!--end:Menu sub-->
                    </div>


                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <span class="menu-link">
                    <span class="menu-icon">
                        <!--begin::Svg Icon | path: icons/duotune/general/gen019.svg-->
                        <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 11H7C6.4 11 6 10.6 6 10V9C6 8.4 6.4 8 7 8H17C17.6 8 18 8.4 18 9V10C18 10.6 17.6 11 17 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z"
                                  fill="currentColor"/>
                            <path opacity="0.3"
                                  d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM18 20V19C18 18.4 17.6 18 17 18H7C6.4 18 6 18.4 6 19V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20Z"
                                  fill="currentColor"/>
                            </svg>
                        </span>
                        <!--end::Svg Icon-->
                    </span>
                    <span class="menu-title">Registri</span>
                    <span class="menu-arrow"></span>
                </span>

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'login')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Login</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'email')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Email inviate</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'backup-db')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Backup DB</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\ChiamataApiController::class,'index'])}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Chiamate API</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'info-sito')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Info varie</span>
                                </a>
                            </div>
                            <div class="menu-item">
                                <a class="menu-link"
                                   href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],'elenco_licenze')}}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Licenze</span>
                                </a>
                            </div>
                            @if(Auth::id()==1 || env('APP_ENV')=='local')
                                <div class="menu-item">
                                    <a class="menu-link" href="/backend/log-viewer/logs" target="_blank">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Log viewer</span>
                                    </a>
                                </div>
                            @endif

                        </div>
                        <!--end:Menu sub-->
                    </div>

                @endcan
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
@else
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
             data-kt-scroll="true" data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
             data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
             data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu"
                 data-kt-menu="true" data-kt-menu-expand="false">
                @can('servizio_contratti_telefonia')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                            <span class="menu-title">Contratti</span>
                        </a>
                    </div>
                @endcan
                @can('servizio_spedizioni')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}">
                        <span class="menu-icon">
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                        </span>
                            <span class="menu-title">Spedizione BRT</span>
                        </a>
                    </div>
                @endcan
                @can('servizio_caf_patronato')
                    <div class="menu-item">
                        <a class="menu-link"
                           href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                        <span class="menu-icon">
                            <!--begin::Svg Icon | path: icons/duotune/files/fil003.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3"
                                          d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22Z"
                                          fill="currentColor"/>
                                    <path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <!--end::Svg Icon-->
                        </span>
                            <span class="menu-title">Pratiche Caf/Patronato</span>
                        </a>
                    </div>
                @endcan
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
@endif
