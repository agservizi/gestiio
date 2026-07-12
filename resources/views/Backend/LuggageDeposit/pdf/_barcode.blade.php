@php
    use App\Http\Support\Code128Barcode;

    $height = $height ?? 54;
    $module = $module ?? 2;
    $caption = $caption ?? ($value ?? '');
    $src = Code128Barcode::dataUri((string) ($value ?? ''), $height, $module);
@endphp
<table class="bc-block" cellpadding="0" cellspacing="0" role="presentation" width="100%">
    <tr>
        <td class="bc-frame">
            <img src="{{ $src }}" alt="{{ $caption }}" class="bc-img">
            <div class="bc-hri">{{ $caption }}</div>
            <div class="bc-type">CODE 128</div>
        </td>
    </tr>
</table>
