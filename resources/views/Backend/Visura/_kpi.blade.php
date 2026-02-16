<div class="row g-5 mb-6">
    <div class="col-md-3 col-lg-2">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">Totali</div>
                <div class="fs-2hx fw-bold">{{$totali['totale']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">In lavorazione</div>
                <div class="fs-2hx fw-bold text-primary">{{$totali['in_lavorazione']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">Completate</div>
                <div class="fs-2hx fw-bold text-success">{{$totali['ok']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-lg-2">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">KO</div>
                <div class="fs-2hx fw-bold text-danger">{{$totali['ko']}}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="text-gray-400 fw-semibold fs-7">SLA attenzione (&gt; 3 giorni)</div>
                <div class="fs-2hx fw-bold text-warning">{{$totali['sla_attention']}}</div>
            </div>
        </div>
    </div>
</div>