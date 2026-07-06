@extends('Backend._components.modal')
@section('content')
    <form id="form-segnala-chat" action="{{action([$controller,'segnalaChat'],$record->id)}}" method="POST">
        @csrf
        <div class="fv-row mb-3">
            @include('Backend._inputs.inputTextArea',['campo'=>'messaggio','testo'=>'Motivazione','required'=>true,'autocomplete'=>'off'])
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit">Invia segnalazione in chat</button>
            </div>
        </div>
    </form>
@endsection
@push('customScript')
<script>
    $(function () {
        function notify(type, message) {
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

        $('#form-segnala-chat').off().submit(function (e) {
            e.preventDefault();
            var url = $(this).attr('action');
            var data = $(this).serialize();
            var button = $(this).find('button[type="submit"]');
            button.prop('disabled', true);
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function (response) {
                    if (response.ok) {
                        $('#kt_modal').modal('hide');
                        notify('success', response.message || 'Segnalazione inviata in chat');
                        if (response.thread_id) {
                            window.open('/backend/chat-interna?thread=' + response.thread_id, '_blank');
                        }
                    } else {
                        notify('error', response.message || 'Errore invio segnalazione');
                    }
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore invio segnalazione';
                    notify('error', message);
                },
                complete: function () {
                    button.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush
