<!DOCTYPE html>
<html>
<head>
    <title>Borderò</title>
    <style>
        th {
            text-align: left;
        }

        @page {
            margin: 0cm 0cm;
        }

        body {
            margin-top: 2cm;
            margin-left: 2cm;
            margin-right: 2cm;
            margin-bottom: 2cm;
        }

        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;
            background-color: yellow;
            text-align: center;
            line-height: 1.5cm;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            text-align: center;
        }
    </style>
</head>
<body>
<footer>
    gestiio.it 2023
</footer>
<table>
    <tr>
        <td><img src="{{public_path('/loghi/Logo_BRT.png')}}" style="height: 50px;"></td>
        <td style="padding-left: 20px;">
            Borderò {{$bordero->id}} del {{$bordero->created_at->format('d/m/Y')}}
        </td>
        <td style="text-align: right;">
           MITTENTE: {{config('services.brt.user')}}
        </td>
    </tr>
</table>
<table style="width: 100%; padding-top: 50px;">

    <!-- Intestazione della tabella -->
    <thead>
    <tr>
        <th class="">Ragione sociale destinatario</th>
        <th class="">Localita destinazione</th>
        <th class="">Nazione destinazione</th>
        <th class="">Numero pacchi</th>
        <th class="">Peso totale</th>
        <th class="">Volume totale</th>
        <th class="">Segnacollo</th>
    </tr>
    </thead>
    <!-- Contenuto della tabella -->
    <tbody>
    @foreach($records as $record)
        <tr>
            <td class="">{{$record->ragione_sociale_destinatario}}</td>
            <td class="">{{$record->localita_destinazione}}</td>
            <td class="">{{$record->nazione_destinazione}}</td>
            <td class="">{{$record->numero_pacchi}}</td>
            <td class="">{{$record->peso_totale}}</td>
            <td class="">{{$record->volume_totale}}</td>
            <td>{!! $record->tracking() !!}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div style="text-align: right; padding-top: 60px;">Firma______________________________</div>

</body>
</html>
