// Handle 'Segnala via chat' button: POST to apri-chat endpoint and redirect
(function (){
    $(document).on('click', '.segnala-chat-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var url = $btn.data('url') || $btn.attr('href');
        console.log('segnala-chat click', url, $btn.data());
        if (!url) return;
        var token = $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            credentials: 'same-origin',
            body: JSON.stringify({})
        }).then(function (resp) {
            console.log('segnala-chat response status', resp.status);
            return resp.json();
        }).then(function (data) {
            console.log('segnala-chat data', data);
            if (data.thread_id) {
                window.location.href = '/backend/chat-interna?thread=' + data.thread_id;
            } else if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                if (data.message) alert(data.message);
            }
        }).catch(function (err) {
            console.error('segnala-chat error', err);
            alert('Errore durante l\'apertura della chat');
        });

    });
})();


