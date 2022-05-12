@extends('admin.layout')

@section('admin-title') Create {{ $isMyo ? 'MYO Slot' : 'Character' }} @endsection

@section('admin-content')
{!! breadcrumbs(['Admin Panel' => 'admin', 'Create '.($isMyo ? 'MYO Slot' : 'Character') => 'admin/masterlist/create-'.($isMyo ? 'myo' : 'character')]) !!}

<h1>Create {{ $isMyo ? 'MYO Slot' : 'Character' }}</h1>

@if(!$isMyo && !count($categories))

    <div class="alert alert-danger">Creating characters requires at least one <a href="{{ url('admin/data/character-categories') }}">character category</a> to be created first, as character categories are used to generate the character code.</div>

@else

    {!! Form::open(['url' => 'admin/masterlist/create-'.($isMyo ? 'myo' : 'character'), 'files' => true]) !!}

    <h3 class="mt-3">Basic Information</h3>

    @if($isMyo)
        <div class="form-group">
            {!! Form::label('Name') !!} {!! add_help('Enter a descriptive name for the type of character this slot can create, e.g. Rare MYO Slot. This will be listed on the MYO slot masterlist.') !!}
            {!! Form::text('name', old('name'), ['class' => 'form-control']) !!}
        </div>
    @endif

    <div class="alert alert-info">
        Fill in either of the owner fields - you can select a user from the list if they have registered for the site, or enter the URL of their off-site profile, such as their deviantArt profile, if they don't have an account. If the owner registers an account later and links their account, {{ $isMyo ? 'MYO slot' : 'character' }}s linked to that account's profile will automatically be credited to their site account. If both fields are filled, the URL field will be ignored.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('Owner') !!}
                {!! Form::select('user_id', $userOptions, old('user_id'), ['class' => 'form-control', 'placeholder' => 'Select User', 'id' => 'userSelect']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('Owner URL (Optional)') !!}
                {!! Form::text('owner_url', old('owner_url'), ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>

    @if(!$isMyo)
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('Character Category') !!}
                    <select name="character_category_id" id="category" class="form-control" placeholder="Select Category">
                        <option value="" data-code="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-code="{{ $category->code }}" {{ old('character_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('Year') !!}
                    {!! Form::text('year', date("Y"), ['class' => 'form-control mr-2', 'id' => 'year']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('Number') !!} {!! add_help('This number helps to identify the character and should preferably be unique either within the category, or among all characters.') !!}
                    <div class="d-flex">
                        {!! Form::text('number', old('number'), ['class' => 'form-control mr-2', 'id' => 'number']) !!}
                        <a href="#" id="pull-number" class="btn btn-primary" data-toggle="tooltip" title="This will find the highest number assigned to a character currently and add 1 to it. It can be adjusted to pull the highest number in the category or the highest overall number - this setting is in the code.">Pull #</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('Character Code') !!} {!! add_help('This code identifies the character itself. You don\'t have to use the automatically generated code, but this must be unique among all characters (as it\'s used to generate the character\'s page URL).') !!}
            {!! Form::text('slug', old('slug'), ['class' => 'form-control', 'id' => 'code']) !!}
        </div>
    @endif

    @if(!$isMyo)
    <div class="row" style="align-items: center;">
        <div class="form-group col-5">
            {!! Form::label('is_visible', 'Is Visible', ['class' => 'form-check-label ml-3']) !!} {!! add_help('Turn this off to hide the '.($isMyo ? 'MYO slot' : 'character').'. Only mods with the Manage Masterlist power (that\'s you!) can view it - the owner will also not be able to see the '.($isMyo ? 'MYO slot' : 'character').'\'s page.') !!}
            {!! Form::checkbox('is_visible', 1, old('is_visible') || 1, ['class' => 'form-check-input', 'data-toggle' => 'toggle']) !!}
        </div>
        <div class="form-group col-7">
            {!! Form::label('profile_link', 'Profile Link', ['class' => 'form-check-label ml-3']) !!} {!! add_help('For Toyhouse, or similar profiles.') !!}
            {!! Form::text('profile_link', old('profile_link'), ['class' => 'form-control']) !!}
        </div>
    </div>
    @endif


    @if(!$isMyo)
    <div class="form-group">
        {!! Form::label('Description (Optional)') !!}
        {!! add_help('This section is for making additional notes about the character and is separate from the character\'s profile (this is not editable by the user).') !!}
        {!! Form::textarea('description', old('description'), ['class' => 'form-control wysiwyg']) !!}
    </div>
    @endif


    <h3 class="mt-4">Transfer Information</h3>
    <div class="row" style="justify-content: space-between;">
        <div class="form-group">
            {!! Form::label('is_giftable', 'Is Giftable', ['class' => 'form-check-label ml-3']) !!}
            {!! Form::checkbox('is_giftable', 1, old('is_giftable'), ['class' => 'form-check-input', 'data-toggle' => 'toggle' ]) !!}
        </div>
        <div class="form-group">
            {!! Form::label('is_tradeable', 'Is Tradeable', ['class' => 'form-check-label ml-3']) !!}
            {!! Form::checkbox('is_tradeable', 1, old('is_tradeable'), ['class' => 'form-check-input', 'data-toggle' => 'toggle']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('is_sellable', 'Is Sellable', ['class' => 'form-check-label ml-3']) !!}
            {!! Form::checkbox('is_sellable', 1, old('is_sellable'), ['class' => 'form-check-input', 'data-toggle' => 'toggle', 'id' => 'resellable']) !!}
        </div>
    </div>
    <div class="card mb-3" id="resellOptions">
        <div class="card-body">
            {!! Form::label('Resale Value') !!} {!! add_help('This value is publicly displayed on the '.($isMyo ? 'MYO slot' : 'character').'\'s page.') !!}
            {!! Form::text('sale_value', old('sale_value'), ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group">
        {!! Form::label('On Transfer Cooldown Until (Optional)') !!}
        {!! Form::text('transferrable_at', old('transferrable_at'), ['class' => 'form-control', 'id' => 'datepicker']) !!}
    </div>

    @if(!$isMyo)
    <h3 class="mt-4">Image Upload</h3>
    <div class="form-group">
        {!! Form::label('Image') !!}
        {!! add_help('This is the full masterlist image. Note that the image is not protected in any way, so take precautions to avoid art/design theft.') !!}
        <div>{!! Form::file('image', ['id' => 'mainImage']) !!}</div>
    </div>
    @if (Config::get('lorekeeper.settings.masterlist_image_automation') === 1)
        <div class="form-group">
            {!! Form::checkbox('use_cropper', 1, 1, ['class' => 'form-check-input', 'data-toggle' => 'toggle', 'id' => 'useCropper']) !!}
            {!! Form::label('use_cropper', 'Use Thumbnail Automation', ['class' => 'form-check-label ml-3']) !!} {!! add_help('A thumbnail is required for the upload (used for the masterlist). You can use the Thumbnail Automation, or upload a custom thumbnail.') !!}
        </div>
        <div class="card mb-3" id="thumbnailCrop">
            <div class="card-body">
                <div id="cropSelect">By using this function, the thumbnail will be automatically generated from the full image.</div>
                {!! Form::hidden('x0', 1) !!}
                {!! Form::hidden('x1', 1) !!}
                {!! Form::hidden('y0', 1) !!}
                {!! Form::hidden('y1', 1) !!}
            </div>
        </div>
    @else
        <div class="form-group">
            {!! Form::checkbox('use_cropper', 1, 1, ['class' => 'form-check-input', 'data-toggle' => 'toggle', 'id' => 'useCropper']) !!}
            {!! Form::label('use_cropper', 'Use Image Cropper', ['class' => 'form-check-label ml-3']) !!} {!! add_help('A thumbnail is required for the upload (used for the masterlist). You can use the image cropper (crop dimensions can be adjusted in the site code), or upload a custom thumbnail.') !!}
        </div>
        <div class="card mb-3" id="thumbnailCrop">
            <div class="card-body">
                <div id="cropSelect">Select an image to use the thumbnail cropper.</div>
                <img src="#" id="cropper" class="hide" alt="" />
                {!! Form::hidden('x0', null, ['id' => 'cropX0']) !!}
                {!! Form::hidden('x1', null, ['id' => 'cropX1']) !!}
                {!! Form::hidden('y0', null, ['id' => 'cropY0']) !!}
                {!! Form::hidden('y1', null, ['id' => 'cropY1']) !!}
            </div>
        </div>
    @endif
    <div class="card mb-3" id="thumbnailUpload">
        <div class="card-body">
            {!! Form::label('Thumbnail Image') !!} {!! add_help('This image is shown on the masterlist page.') !!}
            <div>{!! Form::file('thumbnail') !!}</div>
            <div class="text-muted">Recommended size: {{ Config::get('lorekeeper.settings.masterlist_thumbnails.width') }}px x {{ Config::get('lorekeeper.settings.masterlist_thumbnails.height') }}px</div>
        </div>
    </div>
    <p class="alert alert-info">
        This section is for crediting the image creators. The first box is for the designer or artist's on-site username (if any). The second is for a link to the designer or artist if they don't have an account on the site.
    </p>
    <div class="form-group">
        {!! Form::label('Designer(s)') !!}
        <div id="designerList">
            <div class="mb-2 d-flex">
                {!! Form::select('designer_id[]', $userOptions, null, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select a Designer']) !!}
                {!! Form::text('designer_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Designer URL']) !!}
                @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                    {!! Form::text('designer_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                @endif
                <a href="#" class="add-designer btn btn-link" data-toggle="tooltip" title="Add another designer">+</a>
            </div>
        </div>
        <div class="designer-row hide mb-2">
            {!! Form::select('designer_id[]', $userOptions, null, ['class'=> 'form-control mr-2 designer-select', 'placeholder' => 'Select a Designer']) !!}
            {!! Form::text('designer_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Designer URL']) !!}
            @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                {!! Form::text('designer_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
            @endif
            <a href="#" class="add-designer btn btn-link" data-toggle="tooltip" title="Add another designer">+</a>
        </div>
    </div>
    <div class="form-group">
        {!! Form::label('Artist(s)') !!}
        <div id="artistList">
            <div class="mb-2 d-flex">
                {!! Form::select('artist_id[]', $userOptions, null, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select an Artist']) !!}
                {!! Form::text('artist_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
                @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                    {!! Form::text('artist_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                @endif
                <a href="#" class="add-artist btn btn-link" data-toggle="tooltip" title="Add another artist">+</a>
            </div>
        </div>
        <div class="artist-row hide mb-2">
            {!! Form::select('artist_id[]', $userOptions, null, ['class'=> 'form-control mr-2 artist-select', 'placeholder' => 'Select an Artist']) !!}
            {!! Form::text('artist_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
            @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                {!! Form::text('artist_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
            @endif
            <a href="#" class="add-artist btn btn-link mb-2" data-toggle="tooltip" title="Add another artist">+</a>
        </div>
    </div>
    <div class="form-group">
        {!! Form::label('Image Notes (Optional)') !!} {!! add_help('This section is for making additional notes about the image.') !!}
        {!! Form::textarea('image_description', old('image_description'), ['class' => 'form-control wysiwyg']) !!}
    </div>
    @endif

    @if(!$isMyo)<h3 class="mt-4">Traits</h3>@else<h3 class="mt-4">MYO Limitations</h3>@endif

    <div class="form-group">
        {!! Form::label('Species') !!} @if($isMyo) {!! add_help('This will lock the slot into a particular species. Leave it blank if you would like to give the user a choice.') !!} @endif
        {!! Form::select('species_id', $specieses, old('species_id'), ['class' => 'form-control selectize', 'id' => 'species']) !!}
    </div>

    @if(!$isMyo)
    <div class="form-group" id="subtypes">
        {!! Form::label('Subtype (Optional)') !!} @if($isMyo) {!! add_help('This will lock the slot into a particular subtype. Leave it blank if you would like to give the user a choice, or not select a subtype. The subtype must match the species selected above, and if no species is specified, the subtype will not be applied.') !!} @endif
        {!! Form::select('subtype_id', [0 => 'Pick a Species First'], old('subtype_id'), ['class' => 'form-control disabled selectize', 'id' => 'subtype']) !!}
    </div>
    @endif

    <div class="form-group">
        {!! Form::label('Character Rarity') !!} @if($isMyo) {!! add_help('This will lock the slot into a particular rarity. Leave it blank if you would like to give the user more choices.') !!} @endif
        {!! Form::select('rarity_id', $rarities, old('rarity_id'), ['class' => 'form-control selectize']) !!}
    </div>

    @if(!$isMyo)
    <div class="form-group">
        {!! Form::label('Traits') !!}
        <div id="featureList">
        </div>
        <div><a href="#" class="btn btn-primary" id="add-feature">Add Trait</a></div>
        <div class="feature-row hide mb-2">
            {!! Form::select('feature_id[]', $features, null, ['class' => 'form-control mr-2 feature-select', 'placeholder' => 'Select Trait']) !!}
            {!! Form::text('feature_data[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Extra Info (Optional)']) !!}
            <a href="#" class="remove-feature btn btn-danger mb-2"><i class="fas fa-times"></i></a>
        </div>
    </div>
    @endif

    <div class="text-right">
        {!! Form::submit('Create Character', ['class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endif

@endsection

@section('scripts')
@parent
@include('widgets._character_create_options_js')
@include('widgets._image_upload_js')
@if(!$isMyo)
    @include('widgets._character_code_js')
@endif

<script>
    const subTypes = <?php echo json_encode($subtypes); ?>;
    $( "#species" ).change(function() {
        var species = $('#species').val();
        const dd = $('#subtypes select')[0].selectize;

        dd.clear();
        dd.clearOptions();

        let newSet = subTypes.reduce((filtered, subtype, index) => {
            if (subtype.species_id === parseInt(species, 10) || !subtype.species_id) {
                filtered.push({text: subtype.name, value: subtype.id || 0});
            }
            return filtered;
        }, []);

        dd.addOption(newSet);
        dd.setValue(0);
        dd.refreshOptions(false);
    });
</script>

@endsection
