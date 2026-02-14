@php($flashAlerts = session()->get('alertMessage'))

@if($flashAlerts || session('status') || session('error') || $errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const showToast = function (icon, title, html) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                html: html,
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
        };

        const iconMap = {
            success: 'success',
            primary: 'info',
            info: 'info',
            warning: 'warning',
            danger: 'error',
        };

        const alerts = @json($flashAlerts ?? []);
        Object.keys(alerts).forEach(function (tipo) {
            const gruppo = alerts[tipo] || {};
            const titolo = gruppo.titolo || 'Notifica';
            const messaggi = Array.isArray(gruppo.messaggi) ? gruppo.messaggi : [];
            const html = messaggi.join('<br>');
            showToast(iconMap[tipo] || 'success', titolo, html);
        });

        @if(session('status'))
            showToast('success', 'Operazione completata', @json(session('status')));
        @endif

        @if(session('error'))
            showToast('error', 'Errore', @json(session('error')));
        @endif

        @if($errors->any())
            showToast('error', 'Errore', @json($errors->first()));
        @endif
    });
</script>
@endif
