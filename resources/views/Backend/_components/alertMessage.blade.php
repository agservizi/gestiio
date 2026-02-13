@php($message=session()->get('alertMessage'))
@if($message)
    @foreach($message as $tipo=>$messaggio)
        <div class="notice d-flex bg-light-{{$tipo}} rounded border-{{$tipo}} border border-dashed p-6 mb-5">
            <i class="{{$messaggio['icona']??'fas fa-exclamation-triangle'}} fa-3x me-4 mt-2 text-{{$tipo}}"></i>
            <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                <div class="mb-3 mb-md-0 fw-bold">
                    @if($messaggio['titolo']??false)
                        <h4 class="text-gray-900 fw-bolder">{{$messaggio['titolo']}}</h4>
                    @endif
                    <div class="fs-6 text-gray-700 pe-7">
                        @foreach($messaggio['messaggi'] as $value)
                            @if($loop->index>0)
                                <br>
                            @endif
                            {!! $value !!}
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
