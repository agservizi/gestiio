<div class="modal-dialog @isset($minW) {{$minW}} @else mw-650px @endisset @isset($centered) modal-dialog-centered @endisset @isset($scrollable) modal-dialog-scrollable @endisset">
    <div class="modal-content" id="kt_modal_content">

        <div class="modal-header @isset($noBordo) pb-0 border-0 @endisset ">
            <h5 class="modal-title">{{$titoloPagina??''}}</h5>
            <div class="btn btn-sm btn-icon btn-active-color-primary btn-clos" data-bs-dismiss="modal">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="modal-body scroll-y  pt-0 pb-15" id="kt_modal_body">
            @yield('content')
        </div>
        @isset($footer)
            <div class="modal-footer">
                {!! $footer !!}
            </div>
        @endisset
    </div>
</div>
@stack('customScript')
