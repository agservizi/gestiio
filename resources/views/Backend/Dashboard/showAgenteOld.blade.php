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
    <script>
        $(function () {

            var datiCurve =@json($datiBarreOrdini);


            graficoCurve();

            function graficoCurve() {
                var element = document.getElementById('kt_project_overview_graph');

                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var totaleColor = KTUtil.getCssVariableValue('--bs-success');
                var totaleLightColor = KTUtil.getCssVariableValue('--bs-success');

                if (!element) {
                    return;
                }

                var options = {
                    series: [
                        {
                            name: 'Totale',
                            data: datiCurve.arrOk
                        }
                    ],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: height,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {},
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    fill: {
                        type: 'solid',
                        opacity: 0.5
                    },
                    stroke: {
                        curve: 'smooth',
                        show: true,
                        width: 3,
                        colors: [totaleColor]
                    },
                    xaxis: {
                        categories: datiCurve.arrMese,
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
                        },
                        crosshairs: {
                            position: 'front',
                            stroke: {
                                color: totaleColor,
                                width: 1,
                                dashArray: 3
                            }
                        },
                        tooltip: {
                            enabled: true,
                            formatter: undefined,
                            offsetY: 0,
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
                        }
                    },
                    states: {
                        normal: {
                            filter: {
                                type: 'none',
                                value: 0
                            }
                        },
                        hover: {
                            filter: {
                                type: 'none',
                                value: 0
                            }
                        },
                        active: {
                            allowMultipleDataPointsSelection: false,
                            filter: {
                                type: 'none',
                                value: 0
                            }
                        }
                    },
                    tooltip: {
                        style: {
                            fontSize: '12px'
                        },
                        y: {
                            formatter: function (val) {
                                return '€' + val;
                            }
                        }
                    },
                    colors: [totaleLightColor],
                    grid: {
                        borderColor: borderColor,
                        strokeDashArray: 4,
                        yaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    markers: {
                        strokeColor: totaleColor,
                        strokeWidth: 3
                    }
                };

                var chart = new ApexCharts(element, options);
                chart.render();

            }


        });
    </script>
@endpush
