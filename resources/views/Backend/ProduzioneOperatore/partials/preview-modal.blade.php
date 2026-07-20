{{-- Modal preview proforma (riusabile) --}}
<div class="modal fade" id="modalPreviewProforma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Anteprima proforma</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Chiudi">×</button>
            </div>
            <div class="modal-body">
                <div id="preview-proforma-loading" class="text-center py-8 d-none">
                    <span class="spinner-border"></span>
                </div>
                <div id="preview-proforma-error" class="alert alert-danger d-none"></div>
                <div id="preview-proforma-body" class="d-none">
                    <p class="fs-5 mb-1"><strong id="preview-agente"></strong></p>
                    <p class="text-muted mb-4">Periodo: <span id="preview-periodo"></span></p>
                    <div id="preview-warning-intestazione" class="alert alert-warning d-none">
                        Intestazione incompleta: completa indirizzo/CF prima di PDF o email.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                            <tr class="fw-bold text-gray-700">
                                <th>Descrizione</th>
                                <th class="text-end">Importo</th>
                            </tr>
                            </thead>
                            <tbody id="preview-linee"></tbody>
                            <tfoot>
                            <tr>
                                <td class="fw-bold text-end">Totale</td>
                                <td class="fw-bolder text-end" id="preview-totale"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
                <form id="form-crea-proforma" method="post" action="#">
                    @csrf
                    <button type="submit" class="btn btn-primary" id="btn-conferma-crea-proforma">Conferma crea</button>
                </form>
            </div>
        </div>
    </div>
</div>
