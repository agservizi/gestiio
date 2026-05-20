@php($includeSegnalaChat = $includeSegnalaChat ?? false)
@php($includeSidebarEvents = $includeSidebarEvents ?? false)
@php($includeSidebarToggle = $includeSidebarToggle ?? false)

<script>var hostUrl = "assets/";</script>
<script src="/assets_backend/plugins/global/plugins.bundle.js"></script>
<script src="/assets_backend/js/scripts.bundle.js"></script>
<script src="/assets_backend/js-miei/mieiScript.js?v=10"></script>
<script src="/assets_backend/js-miei/html2canvas.min.js"></script>
@if($includeSegnalaChat)
    <script src="/assets_backend/js-miei/segnala-chat.js?v=1"></script>
@endif

@include('Backend._components.flashToast')
@include('Backend.Chat._globalNotifications')
@stack('customScript')

@if($includeSidebarEvents)
    <script>
        window.addEventListener('nuove-notifiche', function () {
            $('#nuove').show();
        });
        window.addEventListener('no-notifiche', function () {
            $('#nuove').hide();
        });
        window.addEventListener('beep', function () {
            Swal.fire('Nuove notifiche', 'Hai nuove notifiche da leggere', "success");
        });
    </script>
@endif

<script>
    $(function () {
        modalAjax();

        $('.menu-click').click(function () {
            location.href = $(this).attr('href');
        });

        $('#kt_user_menu_dark_mode_toggle').change(function () {
            window.location.href = $(this).data('kt-url');
        });

        @if($includeSidebarToggle)
        $('#kt_app_sidebar_toggle').click(function () {
            $.ajax({
                type: 'GET',
                url: '/metronic/aside',
                success: function () {
                },
                error: function (e) {
                }
            });
        });
        @endif
    });
</script>

@livewireScripts
