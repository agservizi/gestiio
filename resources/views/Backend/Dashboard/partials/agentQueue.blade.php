@php
    $items = collect($items ?? []);
    $tone = $tone ?? 'primary';
    $actionHint = $actionHint ?? 'Seleziona una o più righe per usare le azioni rapide.';
@endphp

<div class="agent-queue agent-queue-{{$tone}}">
    <div class="agent-queue-top">
        <div class="agent-queue-title">
            <div class="agent-queue-mark"></div>
            <div>
                <h4>{{$title}}</h4>
                <span>{{$actionHint}}</span>
            </div>
        </div>
        <div class="agent-queue-status">
            <strong>{{$items->count()}}</strong>
            <span>elementi</span>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="agent-empty">
            <strong>Tutto pulito</strong>
            <span>{{$empty}}</span>
        </div>
    @else
        <div class="agent-bulkbar">
            <label class="agent-check-all">
                <input class="form-check-input bulk-check-all" type="checkbox" value="1" aria-label="Seleziona tutto">
                <span>Seleziona tutto</span>
            </label>
            <div class="agent-selected-counter"><strong>0</strong> selezionati</div>
            <div class="agent-queue-actions">
                <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="open" data-bulk-target="#{{$id}}">Apri</button>
                <button type="button" class="btn btn-sm btn-light-primary" data-bulk-action="assign" data-bulk-target="#{{$id}}">Assegna</button>
                <button type="button" class="btn btn-sm btn-light-success" data-bulk-action="complete" data-bulk-target="#{{$id}}">Completa</button>
            </div>
        </div>

        <div class="agent-list" id="{{$id}}">
            @foreach($items as $item)
                <div class="agent-row"
                     data-record-type="{{$item['type']}}"
                     data-record-id="{{$item['id']}}"
                     data-open-url="{{$item['open_url']}}"
                     data-assign-url="{{$item['assign_url']}}"
                     data-complete-url="{{$item['complete_url']}}">
                    <input class="form-check-input bulk-check-item" type="checkbox" value="{{$item['id']}}" aria-label="Seleziona elemento">
                    <div class="agent-row-title">
                        <strong>{{$item['title']}}</strong>
                        <span>{{$item['subtitle']}}</span>
                    </div>
                    <div class="agent-row-signals">
                        <span class="badge {{$item['badge_class']}}">{{$item['badge']}}</span>
                        <span>{{$item['meta']}}</span>
                    </div>
                    <div class="agent-row-actions">
                        <a class="btn btn-sm btn-light-primary" href="{{$item['open_url']}}">Apri</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
