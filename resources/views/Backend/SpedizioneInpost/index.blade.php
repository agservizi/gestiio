@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <div class="d-flex align-items-center position-relative me-4" data-bs-toggle="tooltip" data-bs-placement="bottom"
             title="{{$testoCerca}}">
            <span class="svg-icon svg-icon-3 position-absolute ms-3 mt-n1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14.2929 16.7071C13.9024 16.3166 13.9024 15.6834 14.2929 15.2929C14.6834 14.9024 15.3166 14.9024 15.7071 15.2929L19.7071 19.2929C20.0976 19.6834 20.0976 20.3166 19.7071 20.7071C19.3166 21.0976 18.6834 21.0976 18.2929 20.7071L14.2929 16.7071Z" fill="#000" opacity="0.3"/>
                    <path d="M11 16C13.7614 16 16 13.7614 16 11C16 8.23858 13.7614 6 11 6C8.23858 6 6 8.23858 6 11C6 13.7614 8.23858 16 11 16ZM11 18C7.13401 18 4 14.866 4 11C4 7.13401 7.13401 4 11 4C14.866 4 18 7.13401 18 11C18 14.866 14.866 18 11 18Z" fill="#000"/>
                </svg>
            </span>
            <input type="text" id="filter_search"
                   class="form-control form-control-sm form-control-solid fw-bold fs-7 w-200px ps-9 bg-body btn-color-gray-700"
                   placeholder="Ricerca"/>
        </div>
        <button class="btn btn-sm btn-primary fw-bold me-2 disabled" id="tracking-refresh-bulk"
                data-url="{{action([$controller,'trackingRefreshBulk'])}}" type="button">Aggiorna tracking selezionati
        </button>
        <a class="btn btn-sm btn-primary fw-bold" href="{{action([$controller,'create'])}}">{{$testoNuovo}}</a>
    </div>
@endsection

@section('content')
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.SpedizioneInpost.tabella')
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
                    Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true, icon: icon, title: title, text: text || ''});
                    return;
                }
                console.log(title + (text ? ': ' + text : ''));
            }

            $(document).on('change', '.sel', aggiornaSelezione);
            $(document).on('click', '#tutti', function () {
                $('.sel').prop('checked', $(this).is(':checked'));
                aggiornaSelezione();
            });

            $(document).on('click', '.tracking-refresh-row', function () {
                var button = $(this);
                var row = button.closest('tr');
                $.post(button.data('url'), {_token: csrfToken})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', res && res.message ? res.message : 'Errore tracking');
                            return;
                        }
                        row.find('.tracking-cell').html(res.trackingHtml || '-');
                        row.find('.tracking-status-cell').html(res.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                        row.find('.tracking-updated-cell').text(res.trackingUpdatedAt || '-');
                    })
                    .fail(function () {
                        notify('error', 'Errore', 'Errore tracking');
                    });
            });

            $('#tracking-refresh-bulk').click(function () {
                if (!array.length) {
                    return;
                }

                var button = $(this);
                $.post(button.data('url'), {_token: csrfToken, ids: array})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', res && res.message ? res.message : 'Errore tracking massivo');
                            return;
                        }
                        if (res.rows) {
                            Object.keys(res.rows).forEach(function (id) {
                                var row = $('tr[data-id="' + id + '"]');
                                var rowData = res.rows[id] || {};
                                row.find('.tracking-cell').html(rowData.trackingHtml || '-');
                                row.find('.tracking-status-cell').html(rowData.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                                row.find('.tracking-updated-cell').text(rowData.trackingUpdatedAt || '-');
                            });
                        }
                    });
            });

            function aggiornaSelezione() {
                array = [];
                $('.sel:checked').each(function () {
                    array.push($(this).val());
                });

                if (array.length) {
                    $('#tracking-refresh-bulk').removeClass('disabled').prop('disabled', false);
                } else {
                    $('#tracking-refresh-bulk').addClass('disabled').prop('disabled', true);
                }
            }
        });
    </script>
@endpush
