<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accesso Documenti…</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, Segoe UI, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f5f5f5;
            color: #333;
        }
    </style>
</head>
<body>
    <p>Accesso automatico a Documenti…</p>
    <script>
        // Cookie sessione già impostati via Set-Cookie (Domain=.agenziaplinio.it).
        // Piccolo delay poi apri la library su documenti.*
        setTimeout(function () {
            window.location.replace(@json($target));
        }, 80);
    </script>
</body>
</html>
