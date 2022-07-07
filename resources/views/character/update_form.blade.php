@extends('character.layout', ['isMyo' => $character->is_myo_slot])

@section('profile-title') {{ $character->is_myo_slot ? 'MYO Approval' : 'Design Update' }} for {{ $character->fullName }} @endsection

@section('meta-img') {{ $character->image->thumbnailUrl }} @endsection

@section('profile-content')

@if($character->is_myo_slot)
{!! breadcrumbs([$character->fullName => $character->url, ($character->is_myo_slot ? 'MYO Approval' : 'Design Update') => $character->url.'/approval']) !!}
@else
{!! breadcrumbs([($character->category->masterlist_sub_id ? $character->category->sublist->name.' Masterlist' : 'Character masterlist') => ($character->category->masterlist_sub_id ? 'sublist/'.$character->category->sublist->key : 'masterlist' ), $character->fullName => $character->url, ($character->is_myo_slot ? 'MYO Approval' : 'Design Update') => $character->url.'/approval']) !!}
@endif

@include('character._header', ['character' => $character])

<h3>
    {{ $character->is_myo_slot ? 'MYO Approval' : 'Add Form' }} Request
</h3>
@if(!$queueOpen)
    <div class="alert alert-danger">
        The {{ $character->is_myo_slot ? 'MYO approval' : 'design update' }} queue is currently closed. You cannot submit a new approval request at this time.
    </div>
@elseif(!$request)
    <p>No {{ $character->is_myo_slot ? 'MYO approval' : 'Add Form' }} request found. Would you like to create one?</p>
    <p>This will prepare a request to approve {{ $character->is_myo_slot ? 'your MYO slot\'s design' : 'a new form for your character' }}, which will allow you to upload a new masterlist image, list their new traits and spend items/currency on the design. You will be able to edit the contents of your request as much as you like before submission. Staff will be able to view the draft and provide feedback. </p>
    {!! Form::open(['url' => $character->is_myo_slot ? 'myo/'.$character->id.'/approval' : 'character/'.$character->slug.'/approval']) !!}
    <div class="text-right">
        {!! Form::submit('Create Request', ['class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@else
    <p>You have a {{ $character->is_myo_slot ? 'MYO approval' : 'design update' }} request {{ $request->status == 'Draft' ? 'that has not been submitted' : 'awaiting approval' }}. <a class="text-primary" href="{{ $request->url }}">Click here to view {{ $request->status == 'Draft' ? 'and edit ' : '' }}it.</a></p>
@endif

@if($queueOpen && !$request && !$character->is_myo_slot)
<p class="mt-4">
    You may also request an update to an existing form. Know that doing so will overwrite the existing image and traits.<br/>
    Click on the form you wish to update and hit the submit button to open a request.
</p>
{!! Form::open(['url' => 'character/'.$character->slug.'/update']) !!}
<ul class="row nav image-nav mb-1 no-gutters w-100">
    @foreach($character->images as $displayImage)
        <li class="col-2 text-center nav-item" data-id="{{ $displayImage->id }}">
            <a id="{{ $displayImage->id }}" href="#image-{{ $displayImage->id }}" class="tab-trigger">
                <img src="{{ $displayImage->thumbnailUrl }}" class="img-thumbnail" alt="Thumbnail for {{ $displayImage->character->fullName }}"/>
            </a>
            <div class="form-type">{!! $displayImage->formType !!}</div>
        </li>
    @endforeach
</ul>
<input class="hide" name="form_id" id="form_id" type="text" />
{{--  {{ $displayImage->id == $image->id ? 'active' : '' }} --}}
    <div class="text-right">
        {!! Form::submit('Create Request for Update', ['class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endif
@endsection

@section('scripts')
    @parent
    <script>
        $(document).ready(function(){
            $('.tab-trigger').click((e) => {
                $('.tab-trigger').removeClass('active');
                $(e.currentTarget).addClass('active');
                $('#form_id').val(e.currentTarget.getAttribute('id'));
            })
        });
    </script>
@endsection
