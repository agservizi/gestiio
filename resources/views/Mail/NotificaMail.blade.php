<!DOCTYPE html>
<html lang="it">
<body id="kt_body" class="app-blank">
<div class="d-flex flex-column flex-root" id="kt_app_root">
    <div class="d-flex flex-column flex-column-fluid">
        <div class="scroll-y flex-column-fluid px-10 py-10" data-kt-scroll="true" data-kt-scroll-activate="true"
             data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_header_nav" data-kt-scroll-offset="5px"
             data-kt-scroll-save-state="true"
             style="background-color:#D5D9E2; --kt-scrollbar-color: #d9d0cc; --kt-scrollbar-hover-color: #d9d0cc">

            <style>html, body {
                    padding: 0;
                    margin: 0;
                    font-family: Inter, Helvetica, "sans-serif";
                }
                a:hover {
                    color: #009ef7;
                }</style>
            <div id="#kt_app_body_content"
                 style="background-color:#D5D9E2; font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:0; width:100%;">
                <div style="background-color:#ffffff; padding: 45px 0 34px 0; border-radius: 24px; margin:40px auto; max-width: 600px;">
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto"
                           style="border-collapse:collapse">
                        <tbody>
                        <tr>
                            <td align="center" valign="center" style="text-align:center; padding-bottom: 10px">
                                <div style="text-align:center; margin:0 15px 34px 15px">
                                    <div style="margin-bottom: 10px">
                                        <a href="{{url()->to('/')}}" rel="noopener" target="_blank">
                                            <img alt="Logo" src="{{url()->to('/loghi/logo-aziendale.png')}}"
                                                 style="height: 35px"/>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr style="display: flex; justify-content: center; margin:0 60px 35px 60px">
                            <td align="start" valign="start" style="padding-bottom: 10px;">
                                <p style="color:#181C32; font-size: 18px; font-weight: 600; margin-bottom:13px">{{$notifica->titolo}}</p>
                                <div style="background: #F9F9F9; border-radius: 12px; padding:35px 30px">
                                    {!! $notifica->testo !!}
                                </div>
                            </td>
                        </tr>
                        @if(false)
                            <tr>
                                <td align="center" valign="center"
                                    style="font-size: 13px; padding:0 15px; text-align:center; font-weight: 500; color: #A1A5B7;font-family:Arial,Helvetica,sans-serif">
                                    <p>&copy; Copyright KeenThemes.
                                        <a href="https://keenthemes.com" rel="noopener" target="_blank"
                                           style="font-weight: 600;font-family:Arial,Helvetica,sans-serif">Unsubscribe</a>&nbsp;
                                        from newsletter.</p>
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
