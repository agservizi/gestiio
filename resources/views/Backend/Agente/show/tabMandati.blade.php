<div class="table-responsive">
    <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th> Gestore</th>
            <th> Attivo</th>

        </tr>
        </thead>
        <tbody>
        @foreach($gestori as $gestore)
            <tr class="">

                <td>

                    <div class="symbol symbol-50px symbol-2by3 me-3">
                        @if($gestore->logo)
                            <img src="{{$gestore->immagineLogo()}}" class="" alt="">
                        @endif
                    </div>
                    {{$gestore->nome}}
                </td>
                <td>
                    @php($attivo=$mandati->where('gestore_id',$gestore->id)->first()?->attivo)
                    <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                        <input class="form-check-input" type="checkbox" value="{{$gestore->id}}" id="mandato_{{$gestore->id}}"
                               name="mandato_{{$gestore->id}}" {{$attivo?'checked':''}}/>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    function notificaMandato(type, message) {
        if (window.gestiioToast) {
            gestiioToast(type, message);
            return;
        }
        if (window.toastr && typeof toastr[type] === 'function') {
            toastr[type](message);
            return;
        }
        Swal.fire({
            text: message,
            icon: type === 'error' ? 'error' : 'success',
            buttonsStyling: false,
            confirmButtonText: 'Ok',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    }

    $('.form-check-input').change(function () {

        var stato = $(this).is(':checked') ? 1 : 0;
        var mandato = $(this).val();
        var url = '{{action([$controller,'azioni'],['id'=>$id,'azione'=>'imposta_mandato'])}}';
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                stato,
                mandato
            },
            success: function (response) {
                if (response.success) {

                    $('#tr_' + response.id).replaceWith(base64_decode(response.html));
                    $('#kt_modal').modal('hide');
                    modalAjax();
                } else {
                    notificaMandato('error', response.message || 'Impossibile aggiornare il mandato.');
                }

            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Impossibile aggiornare il mandato.';
                notificaMandato('error', message);
            }
        });

    })
</script>
