@extends('Backend._components.modal')
@section('content')
    @include('Backend._components.notice',['level'=>$tipo,'titolo'=>'Richiedi visura','testo'=>$testo])
    <div class="w-100 text-center mt-4">

        @if($tipo=='primary')
            <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'azioni'],['id'=>$record->id,'azione'=>'richiedi_visura'])}}"
               data-targetZ="kt_modal" data-toggleZ="modal-ajax"
               class="btn btn-primary azione">



            <span class="indicator-label">
                         Richiedi visura
                    </span>
                <span class="indicator-progress">
                        Attendi... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
            </a>
        @else
            <a href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}"
               data-targetZ="kt_modal" data-toggleZ="modal-ajax"
               class="btn btn-primary">
                Ricarica portafoglio
            </a>
        @endif
    </div>

    <script>
        $('.azione').click(function (e) {
            e.preventDefault();
            const $obj = $(this);
            var mainDialog = $($obj).closest('.modal');
            $obj.attr("data-kt-indicator", "on");
            var url = $(this).attr('href');
            $.ajax(url,
                {
                    method: 'POST',
                    success: function (resp) {
                        $obj.attr("data-kt-indicator", "non");

                        if (resp.success) {
                            if (typeof resp.redirect !== 'undefined') {
                                location.href = resp.redirect;
                            } else if (typeof resp.message !== 'undefined') {
                                var titolo = 'Bene!';
                                if (typeof resp.title !== 'undefined') {
                                    titolo = resp.title;
                                }

                                Swal.fire(titolo, resp.message, "success");

                            }

                        } else {
                            Swal.fire(
                                "Errore!",
                                resp.message,
                                "error"
                            );
                        }
                        mainDialog.modal('toggle');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        var err = eval("(" + xhr.responseText + ")");
                        Swal.fire(
                            "Errore " + xhr.status,
                            err.message,
                            "error"
                        )
                    }
                });

        });
    </script>
@endsection
