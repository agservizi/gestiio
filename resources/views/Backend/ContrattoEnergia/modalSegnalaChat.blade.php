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
        $('#form-segnala-chat').off().submit(function (e) {
            e.preventDefault();
            var url = $(this).attr('action');
            var data = $(this).serialize();
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function (response) {
                    if (response.ok) {
                        $('#kt_modal').modal('hide');
                        alert(response.message || 'Segnalazione inviata in chat');
                        if (response.thread_id) {
                            window.open('/backend/chat-interna?thread=' + response.thread_id, '_blank');
                        }
                    } else {
                        alert(response.message || 'Errore invio segnalazione');
                    }
                },
                error: function (xhr) {
                    alert('Errore invio segnalazione');
                }
            });
        });
    });
</script>
@endpush
