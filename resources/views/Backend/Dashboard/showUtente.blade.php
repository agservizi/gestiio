@extends('Backend._layout._main')

@section('toolbar')
@endsection
@section('content')
    @include('Backend.Agente.show.topBar')
    <div class="row">
        <div class="col-lg-4">
            @include('Backend.Dashboard.linksGestori',['altezza'=>'h-lg-100'])

        </div>
        <div class="col-lg-8">
            <div class="card card-flush h-lg-100">
                <div class="card-header mt-6">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Guadagno mese</h3>
                        <div class="fs-6 d-flex text-gray-400 fs-6 fw-bold">
                        </div>
                    </div>
                    <div class="card-toolbar">
                    </div>
                </div>
                <div class="card-body pt-10 pb-0 px-5">
                    <div id="kt_project_overview_graph" class="card-rounded-bottom" style="height: 300px"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customCss')
@endpush
@push('customScript')
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script>
        $(function () {
            const datiCurve = @json($datiBarreOrdini);
            const element = document.getElementById('kt_project_overview_graph');
            if (!element || typeof am5 === 'undefined') return;

            const root = am5.Root.new(element);
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(am5percent.PieChart.new(root, {
                layout: root.verticalLayout
            }));

            const series = chart.series.push(am5percent.PieSeries.new(root, {
                valueField: 'totale',
                categoryField: 'mese',
                tooltip: am5.Tooltip.new(root, {labelText: '{category}: €{value}'})
            }));
            series.labels.template.setAll({forceHidden: true});
            series.ticks.template.setAll({forceHidden: true});
            series.slices.template.setAll({strokeOpacity: 0});

            const data = (datiCurve.arrMese || []).map(function (mese, idx) {
                return {mese: mese, totale: Number((datiCurve.arrOk || [])[idx] || 0)};
            });
            series.data.setAll(data);
        });
    </script>
@endpush
