<!--begin::Image input-->
<div class="image-input image-input-empty" data-kt-image-input="true"
     style="background-color:#F5F8FA; border:1px solid #F5F8FA; background-image: url({{$immagine??'/assets_backend/media/logo.png'}}?v={{now()->format('His')}}); background-size: @isset($dimensioni) {{str_replace(['w-','h-'],'',$dimensioni)}} @else 200px 200px @endisset; ">
    <!--begin::Image preview wrapper-->
    <div class="image-input-wrapper @isset($dimensioni)  @else w-200px h-200px @endisset"  style="@isset($dimensioni) {{$dimensioni}} @endisset"  id="open"></div>
    <!--end::Image preview wrapper-->

    <!--begin::Edit button-->
    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
           data-kt-image-input-action="change"
           data-bs-toggle="tooltip"
           data-bs-dismiss="click"
           title="Cambia immagine" id="change">
        <i class="bi bi-pencil-fill fs-7"></i>

        <!--begin::Inputs-->
        <input type="file" id="kt_image_input_control" name="{{$campo}}" accept=".png, .jpg, .jpeg"/>
        <input type="hidden" name="avatar_remove"/>
        <!--end::Inputs-->
    </label>
    <!--end::Edit button-->

    <!--begin::Cancel button-->
    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
          data-kt-image-input-action="cancel"
          data-bs-toggle="tooltip"
          data-bs-dismiss="click"
          title="Cancel avatar">
         <i class="bi bi-x fs-2"></i>
     </span>
    <!--end::Cancel button-->

    <!--begin::Remove button-->
    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
          data-kt-image-input-action="remove"
          data-bs-toggle="tooltip"
          data-bs-dismiss="click"
          title="Remove avatar">
         <i class="bi bi-x fs-2"></i>
     </span>
    <!--end::Remove button-->
</div>
<!--end::Image input-->
