<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'targa','testo'=>'Targa','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextDataMask',['campo'=>'data_di_nascita','testo'=>'Data di nascita','required'=>true,'autocomplete'=>'off'])
    </div>
</div>

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');

            $('#servizio_id').select2({
                placeholder: 'Seleziona un servizio',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/select2?servizio",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data
                //$("#cap").val(e.params.data.cap);

            });

        });
    </script>
@endpush
