<style>
    .masterlist-search .form-control,
    .toggle-group label {
        font-size: 12px;
    }

    .masterlist-search {
        gap: 4px;
    }

    .form-group {
        margin-bottom: 0px !important;
    }

    div.selectize-input {
        min-height: 35px;
        height: 35px;
        padding-left: 10px;
        padding-right: 20px;
    }
    .selectize-input .item {
        vertical-align: middle;
    }
    .selectize-input input {
        vertical-align: sub;
    }
    input.form-control {
        height: 35px;
        padding-left: 10px;
        padding-right: 10px;
    }

    div.form-control.selectize-control {
        margin-bottom: -4px;
    }

    #advancedSearch .form-control {
        font-size: 12px;
    }

    #advancedSearch > div > .d-flex {
        flex-wrap: wrap;
    }

    .masterlist-search-field {
        flex: 1;
        margin-right: 10px;
        max-width: 33%;
    }

    .toggles .masterlist-search-field label {
        margin-right: 5px;
        font-size: 10px !important;
        white-space: pre;
    }

    .toggles .masterlist-search-field > label {
        display: block;
        font-size: 12px !important;
    }

    .toggle.btn {
        min-height: 30px;
        width: 100% !important;
    }
</style>

<div>
    {!! Form::open(['method' => 'GET']) !!}
        <div class="masterlist-search form-inline justify-content-end mb-3">
            <div class="form-group">
                {!! Form::text('name', Request::get('name'), ['class' => 'form-control', 'placeholder' => 'Character Name / Code']) !!}
            </div>
            <div class="form-group">
                {!! Form::select('species_id', $specieses, Request::get('species_id'), ['class' => 'form-control selectize']) !!}
            </div>
            @if(!$isMyo)
                <div class="form-group">
                    {!! Form::select('character_category_id', $categories, Request::get('character_category_id'), ['class' => 'form-control selectize']) !!}
                </div>
                <div id="subtypes" class="form-group">
                    {!! Form::select('subtype_id', array_column($subtypes, 'name'), Request::get('subtype_id'), ['class' => 'form-control selectize']) !!}
                </div>
            @endif
            <div class="form-group">
                {!! Form::select('rarity_id', $rarities, Request::get('rarity_id'), ['class' => 'form-control selectize']) !!}
            </div>
        </div>
        <div class="text-right mb-3"><a href="#advancedSearch" class="btn btn-sm btn-outline-info" data-toggle="collapse">Show Advanced Search Options <i class="fas fa-caret-down"></i></a></div>
        <div class="card bg-light mb-3 collapse" id="advancedSearch">
            <div class="card-body masterlist-advanced-search">
                <div class="d-flex">
                    <div class="masterlist-search-field">
                        {!! Form::label('owner', 'Owner Username: ') !!}
                        {!! Form::select('owner', $userOptions, Request::get('owner'), ['class'=> 'form-control mr-2 userselectize',  'placeholder' => 'Select a User']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('artist', 'Artist: ') !!}
                        {!! Form::select('artist', $userOptions, Request::get('artist'), ['class'=> 'form-control mr-2 userselectize',  'placeholder' => 'Select a User']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('designer', 'Designer: ') !!}
                        {!! Form::select('designer', $userOptions, Request::get('designer'), ['class'=> 'form-control mr-2 userselectize',  'placeholder' => 'Select a User']) !!}
                    </div>
                </div>
                <hr />
                <div class="d-flex">
                    <div class="masterlist-search-field">
                        {!! Form::label('owner_url', 'Owner URL: ') !!} {!! add_help ('Example: https://deviantart.com/username OR username') !!}
                        {!! Form::text('owner_url', Request::get('owner_url'), ['class'=> 'form-control mr-2',  'placeholder' => 'Type a Username']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('artist_url', 'Artist URL: ') !!} {!! add_help ('Example: https://deviantart.com/username OR username') !!}
                        {!! Form::text('artist_url', Request::get('artist_url'), ['class'=> 'form-control mr-2',  'placeholder' => 'Type a Username']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('designer_url', 'Designer URL: ') !!} {!! add_help ('Example: https://deviantart.com/username OR username') !!}
                        {!! Form::text('designer_url', Request::get('designer_url'), ['class'=> 'form-control mr-2',  'placeholder' => 'Type a Username']) !!}
                    </div>
                </div>
                <hr/>
                <div class="d-flex">
                    <div class="masterlist-search-field">
                        {!! Form::label('sale_value_min', 'Resale Min ($): ') !!}
                        {!! Form::text('sale_value_min', Request::get('sale_value_min'), ['class' => 'form-control']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('sale_value_max', 'Resale Max ($): ') !!}
                        {!! Form::text('sale_value_max', Request::get('sale_value_max'), ['class' => 'form-control']) !!}
                    </div>
                    @if(!$isMyo)
                        <div class="masterlist-search-field">
                            {!! Form::label('is_gift_art_allowed', 'Gift Art Status: ') !!}
                            {!! Form::select('is_gift_art_allowed', [0 => 'Any', 2 => 'Ask First', 1 => 'Yes', 3 => 'Yes OR Ask First'], Request::get('is_gift_art_allowed'), ['class' => 'form-control selectize']) !!}
                        </div>
                        <div class="masterlist-search-field">
                            {!! Form::label('is_gift_writing_allowed', 'Gift Writing Status: ') !!}
                            {!! Form::select('is_gift_writing_allowed', [0 => 'Any', 2 => 'Ask First', 1 => 'Yes', 3 => 'Yes OR Ask First'], Request::get('is_gift_writing_allowed'), ['class' => 'form-control selectize']) !!}
                        </div>
                    @endif
                </div>
                <div class="toggles d-flex">
                    {{-- Setting the width and height on the toggles as they don't seem to calculate correctly if the div is collapsed. --}}
                    <div class="masterlist-search-field">
                        {!! Form::label('is_trading', 'Trading Status: ') !!}
                        {!! Form::checkbox('is_trading', 1, Request::get('is_trading'), ['class' => 'form-check-input',  'data-toggle' => 'toggle', 'data-on' => 'Open For Trade', 'data-off' => 'Any']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('is_sellable', 'Sellable Status: ') !!}
                        {!! Form::checkbox('is_sellable', 1, Request::get('is_sellable'), ['class' => 'form-check-input',  'data-toggle' => 'toggle', 'data-on' => 'Can Be Sold', 'data-off' => 'Any']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('is_tradable', 'Tradable Status: ') !!}
                        {!! Form::checkbox('is_tradeable', 1, Request::get('is_tradeable'), ['class' => 'form-check-input',  'data-toggle' => 'toggle', 'data-on' => 'Can Be Traded', 'data-off' => 'Any']) !!}
                    </div>
                    <div class="masterlist-search-field">
                        {!! Form::label('is_giftable', 'Giftable Status: ') !!}
                        {!! Form::checkbox('is_giftable', 1, Request::get('is_giftable'), ['class' => 'form-check-input',  'data-toggle' => 'toggle', 'data-on' => 'Can Be Gifted', 'data-off' => 'Any']) !!}
                    </div>
                </div>
                <div class="toggles d-flex">
                    {{-- Setting the width and height on the toggles as they don't seem to calculate correctly if the div is collapsed. --}}
                    <div class="masterlist-search-field">
                        {!! Form::label('multiple_forms', 'Multiple Forms: ') !!}
                        {!! Form::checkbox('multiple_forms', 1, Request::get('multiple_forms'), ['class' => 'form-check-input',  'data-toggle' => 'toggle', 'data-on' => 'More than One Form', 'data-off' => 'Any']) !!}
                    </div>
                </div>
                <hr />
                    <a href="#" class="float-right btn btn-sm btn-outline-primary add-feature-button">Add Trait</a>
                    {!! Form::label('Has Traits: ') !!} {!! add_help('This will narrow the search to characters that have ALL of the selected traits at the same time.') !!}
                    <div id="featureBody" class="row w-100">
                        @if(Request::get('feature_id'))
                            @foreach(Request::get('feature_id') as $featureId)
                                <div class="feature-block col-12 col-md-6 mt-3 bg-white d-flex p-1">
                                    {!! Form::select('feature_id[]', $features, $featureId, ['class' => 'form-control feature-select', 'placeholder' => 'Select Trait']) !!}
                                    <a href="#" class="btn feature-remove ml-2"><i class="fas fa-times"></i></a>
                                </div>
                            @endforeach
                        @endif
                    </div>
                {{-- <hr /> --}}
                {{-- <div class="masterlist-search-field">
                    {!! Form::checkbox('search_images', 1, Request::get('search_images'), ['class' => 'form-check-input mr-3',  'data-toggle' => 'toggle']) !!}
                    <span class="ml-2">Include all character images in search {!! add_help('Each character can have multiple images for each updated version of the character, which captures the traits on that character at that point in time. By default the search will only search on the most up-to-date image, but this option will retrieve characters that match the criteria on older images - you may get results that are outdated.') !!}</span>
                </div> --}}

            </div>

        </div>
        <div class="form-inline justify-content-end mb-3">
            <div class="form-group mr-3">
                {!! Form::label('sort', 'Sort: ', ['class' => 'mr-2']) !!}
                {!! Form::select('sort', ['number_desc' => 'Number Descending', 'number_asc' => 'Number Ascending', 'id_desc' => 'Newest First', 'id_asc' => 'Oldest First', 'sale_value_desc' => 'Highest Sale Value', 'sale_value_asc' => 'Lowest Sale Value'], Request::get('sort'), ['class' => 'form-control selectize']) !!}
            </div>
            {!! Form::submit('Search', ['class' => 'btn btn-primary']) !!}
        </div>
    {!! Form::close() !!}
</div>
<div class="hide" id="featureContent">
    <div class="feature-block col-12 col-md-6 mt-3 bg-white d-flex p-1">
        {!! Form::select('feature_id[]', $features, null, ['class' => 'form-control feature-select', 'placeholder' => 'Select Trait']) !!}
        <a href="#" class="btn feature-remove ml-2"><i class="fas fa-times"></i></a>
    </div>
</div>
<div class="text-right mb-3">
    <div class="btn-group">
        <button type="button" class="btn btn-secondary active grid-view-button" data-toggle="tooltip" title="Grid View" alt="Grid View"><i class="fas fa-th"></i></button>
        <button type="button" class="btn btn-secondary list-view-button" data-toggle="tooltip" title="List View" alt="List View"><i class="fas fa-bars"></i></button>
    </div>
</div>

{!! $characters->render() !!}
<div id="gridView" class="hide">
    @foreach($characters->chunk(4) as $chunk)
        <div class="row">
            @foreach($chunk as $character)
            <div class="col-md-3 col-6 text-center">
                <div>
                    <a href="{{ $character->url }}"><img src="{{ $character->image->thumbnailUrl }}" class="img-thumbnail" alt="Thumbnail for {{ $character->fullName }}"/></a>
                </div>
                <div class="mt-1">
                    <a href="{{ $character->url }}" class="h5 mb-0">@if(!$character->is_visible) <i class="fas fa-eye-slash"></i> @endif {{ $character->fullName }}</a>
                </div>
                <div class="small">
                    {!! ($character->image->subtype_id ? $character->image->subtype->displayName : $character->image->species->displayName) ?? 'No Species' !!} ・ {!! $character->image->rarity_id ? $character->image->rarity->displayName : 'No Rarity' !!} ・ {!! $character->displayOwner !!}
                </div>
            </div>
            @endforeach
        </div>
    @endforeach
</div>
<div id="listView" class="hide">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Owner</th>
                <th>Name</th>
                <th>Rarity</th>
                <th>Species</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($characters as $character)
                <tr>
                    <td>{!! $character->displayOwner !!}</td>
                    <td>@if(!$character->is_visible) <i class="fas fa-eye-slash"></i> @endif {!! $character->displayName !!}</td>
                    <td>{!! $character->image->rarity_id ? $character->image->rarity->displayName : 'None' !!}</td>
                    <td>{!! $character->image->species_id ? $character->image->species->displayName : 'None' !!}</td>
                    <td>{!! format_date($character->created_at) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
{!! $characters->render() !!}

<div class="text-center mt-4 small text-muted">{{ $characters->total() }} result{{ $characters->total() == 1 ? '' : 's' }} found.</div>

<script>
    const subTypes = <?php if(isset($subtypes)) echo json_encode($subtypes); ?>;

    $("[name=species_id]").change(() => {
        var species = $("[name=species_id]").val();
        const dd = $('#subtypes select')[0].selectize;
        dd.clearOptions();

        let newSet = subTypes.reduce((filtered, subtype, index) => {
            if (subtype.species_id === parseInt(species, 10) || !subtype.species_id || species === '0') {
                filtered.push({text: subtype.name, value: subtype.id});
            }
            return filtered;
        }, []);

        dd.addOption(newSet);
        dd.refreshOptions(false);
    });
</script>
