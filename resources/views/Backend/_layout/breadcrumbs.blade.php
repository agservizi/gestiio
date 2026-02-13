<span class="h-20px border-gray-800 border-start mx-4"></span>
<ul class="breadcrumb breadcrumb-separatorless fw-bold fs-7 pt-2">
    @foreach($breadcrumbs as $url=>$testo)
        <li class="breadcrumb-item text-muted">
            <a href="{{$url}}" class="text-muted text-hover-primary">{{$testo}}</a>
        </li>
        @if($loop->remaining>1)
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-200 w-5px h-2px"></span>
            </li>
        @endif
    @endforeach
    @if(false)
        <li class="breadcrumb-item text-dark">Header</li>
    @endif
</ul>
