<div class="row g-5 mb-6">
    <div class="col-md-3">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">Totale agenti</div>
                <div class="fs-2hx fw-bold">{{$totali['totale']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">Attivi</div>
                <div class="fs-2hx fw-bold text-success">{{$totali['attivi']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">Sospesi</div>
                <div class="fs-2hx fw-bold text-warning">{{$totali['sospesi']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">2FA attiva</div>
                <div class="fs-2hx fw-bold text-primary">{{$totali['con2fa']}}</div>
            </div>
        </div>
    </div>
</div>