@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a class="btn btn-sm btn-primary fw-bold me-2"
           href="{{action([\App\Http\Controllers\Backend\ContattoBrtController::class,'index'])}}">Preferiti</a>
        @isset($testoCerca)
            <div class="d-flex align-items-center position-relative me-4" data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="{{$testoCerca}}">
            <span class="svg-icon svg-icon-3 position-absolute ms-3 mt-n1">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                     height="24px" viewBox="0 0 24 24"
                     version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path
                                d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z"
                                fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                        <path
                                d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z"
                                fill="#000000" fill-rule="nonzero"/>
                    </g>
                </svg>
            </span>
                <input type="text" id="filter_search"
                       class="form-control form-control-sm form-control-solid  fw-bold fs-7 w-200px ps-9 bg-body btn-color-gray-700"
                       placeholder="Ricerca"/>
            </div>
        @endisset
        @isset($ordinamenti)
            <div class="me-4 d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary"
                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                        data-kt-menu-flip="top-end">
                    <i class="bi bi-sort-down fs-3"></i>
                </button>
                <!--begin::Menu 3-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3"
                     data-kt-menu="true">
                    <!--begin::Heading-->
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                    </div>
                    @foreach($ordinamenti as $key=>$ordinamento)
                        <div class="menu-item px-3">
                            <a href="{{Request::url()}}?orderBy={{$key}}"
                               class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
                                @if($key==$orderBy)
                                    <i class="fas fa-check ms-2 fs-7"></i>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
        <a class="btn btn-sm btn-primary fw-bold me-2 disabled" data-targetZ="kt_modal" data-toggleZ="modal-ajax"
           href="{{action([$controller,'bordero'])}}" id="bordero">Crea borderò</a>
        <button class="btn btn-sm btn-primary fw-bold me-2 disabled" id="tracking-refresh-bulk"
            data-url="{{action([$controller,'trackingRefreshBulk'])}}" type="button">Aggiorna tracking selezionati
        </button>
        @can('agente')
            <a class="btn btn-sm btn-primary fw-bold me-2"
               href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_type'=>'spedizione-brt'])}}">Apri
                ticket</a>
        @endif
        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax"
           href="{{action([$controller,'create'])}}"><span class="d-md-none">+</span><span
                    class="d-none d-md-block">{{$testoNuovo}}</span></a>
    </div>
@endsection
@section('content')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.SpedizioneBrt.tabella')
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';
        var array = [];
        var csrfToken = '{{csrf_token()}}';

        $(function () {
            searchHandler();

            function notify(icon, title, text) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        icon: icon,
                        title: title,
                        text: text || ''
                    });
                    return;
                }

                if (text) {
                    console.log(title + ': ' + text);
                } else {
                    console.log(title);
                }
            }

            $(document).on('change', '.sel', function () {
                aggiornaSelezione();
            });

            $('#bordero').click(function (e) {
                e.preventDefault();
                location.href = $(this).attr('href') + '?id=' + array.join(',');
            });

            $(document).on('click', '#tutti', function () {
                const checked = $(this).is(':checked');
                $('.sel').prop('checked', checked);
                aggiornaSelezione();

            });

            $(document).on('click', '.tracking-refresh-row', function () {
                var button = $(this);
                var row = button.closest('tr');
                var url = button.data('url');
                if (!url) {
                    return;
                }

                button.prop('disabled', true).addClass('disabled');

                $.post(url, {_token: csrfToken})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', (res && res.message) ? res.message : 'Errore durante aggiornamento tracking');
                            return;
                        }

                        row.find('.tracking-cell').html(res.trackingHtml || '-');
                        row.find('.tracking-status-cell').html(res.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                        row.find('.tracking-updated-cell').text(res.trackingUpdatedAt || '-');
                        notify('success', 'Tracking aggiornato', res.message || 'Aggiornamento completato');
                    })
                    .fail(function () {
                        notify('error', 'Errore', 'Errore durante aggiornamento tracking');
                    })
                    .always(function () {
                        button.prop('disabled', false).removeClass('disabled');
                    });
            });

            $('#tracking-refresh-bulk').click(function () {
                if (!array.length) {
                    return;
                }

                var button = $(this);
                button.prop('disabled', true).addClass('disabled');

                $.post(button.data('url'), {
                    _token: csrfToken,
                    ids: array
                }).done(function (res) {
                    if (!res || !res.success) {
                        notify('error', 'Errore', (res && res.message) ? res.message : 'Errore durante aggiornamento tracking massivo');
                        return;
                    }

                    if (res.rows) {
                        Object.keys(res.rows).forEach(function (id) {
                            var rowData = res.rows[id] || {};
                            var row = $('tr[data-id="' + id + '"]');
                            if (!row.length) {
                                return;
                            }
                            row.find('.tracking-cell').html(rowData.trackingHtml || '-');
                            row.find('.tracking-status-cell').html(rowData.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                            row.find('.tracking-updated-cell').text(rowData.trackingUpdatedAt || '-');
                        });
                    }

                    notify('success', 'Tracking aggiornato', res.message || 'Aggiornamento tracking completato');
                }).fail(function () {
                    notify('error', 'Errore', 'Errore durante aggiornamento tracking massivo');
                }).always(function () {
                    button.prop('disabled', false).removeClass('disabled');
                });
            });

            aggiornaSelezione();


            function aggiornaSelezione() {
                array = []
                $('.sel:checked').each(function () {
                    array.push($(this).val());
                });

                var quantio = array.length;

                if (quantio) {
                    $('#bordero').removeClass('disabled');
                    $('#tracking-refresh-bulk').removeClass('disabled').prop('disabled', false);
                } else {
                    $('#bordero').addClass('disabled');
                    $('#tracking-refresh-bulk').addClass('disabled').prop('disabled', true);
                }
            }
        });
    </script>
@endpush
