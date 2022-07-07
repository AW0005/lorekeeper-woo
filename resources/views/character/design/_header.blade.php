<h1>
    Request (#{{ $request->id }}): {!! $request->character ? $request->character->displayName : 'Deleted Character [#'.$request->character_id.']' !!}
    <span class="float-right badge badge-{{ ($request->status == 'Draft' || $request->status == 'Pending') ? 'secondary' : ($request->status == 'Approved' ? 'success' : 'danger') }}">{{ $request->status }}
</h1>

@if(isset($request->staff_id))
    @if($request->staff_comments && ($request->user_id == Auth::user()->id || Auth::user()->hasPower('manage_submissions')))
        <h5 class="text-danger">Staff Comments ({!! $request->staff->displayName !!})</h5>
        <div class="card border-danger mb-3"><div class="card-body">{!! nl2br(htmlentities($request->staff_comments)) !!}</div></div>
    @else
        <p>No staff comment was provided.</p>
    @endif
@endif

@if($request->status != 'Draft' && Auth::user()->hasPower('manage_characters') && Config::get('lorekeeper.extensions.design_update_voting'))
    <?php
        $rejectSum = 0;
        $approveSum = 0;
        foreach($request->voteData as $voter=>$vote) {
            if($vote == 1) $rejectSum += 1;
            if($vote == 2) $approveSum += 1;
        }
    ?>
    <div class="card mb-3"><div class="card-body">
        <h5 class="text-left">{{$request->status == 'Pending' ? 'Vote' : 'Past Votes'}} on this {{ $request->update_type == 'MYO' ? 'MYO Submission' : 'Design Update' }}
        @if($request->status == 'Pending')
            <span class="text-right float-right">
                <div class="row">
                    <div class="col-sm-6 text-center text-danger">
                        {{ $rejectSum }}/{{ Settings::get('design_votes_needed') }}
                        {!! Form::open(['url' => 'admin/designs/vote/'.$request->id.'/reject', 'id' => 'voteRejectForm']) !!}
                            <button class="btn {{ $request->voteData->get(Auth::user()->id) == 1 ? 'btn-danger' : 'btn-outline-danger' }}" style="min-width:40px;" data-action="reject"><i class="fas fa-times"></i></button>
                        {!! Form::close() !!}
                    </div>
                    <div class="col-sm-6 text-center text-success">
                        {{ $approveSum }}/{{ Settings::get('design_votes_needed') }}
                        {!! Form::open(['url' => 'admin/designs/vote/'.$request->id.'/approve', 'id' => 'voteApproveForm']) !!}
                            <button class="btn {{ $request->voteData->get(Auth::user()->id) == 2 ? 'btn-success' : 'btn-outline-success' }}" style="min-width:40px;" data-action="approve"><i class="fas fa-check"></i></button>
                        {!! Form::close() !!}
                    </div>
                </div>
            </span>
        @endif
        </h5>
            <p>
                {{ $request->update_type == 'MYO' ? 'MYO Submissions' : 'Design updates' }} need {{ Settings::get('design_votes_needed') }} votes before they are considered approved. Note that this does not automatically process the submission in any case, only indicate a consensus.
            </p>
        <hr/>
        @if(isset($request->vote_data) && $request->vote_data)
            <h4>Votes:</h4>
                <div class="row">
                    <div class="col-md">
                        <h5>Reject:</h5>
                        <ul>
                        @foreach($request->voteData as $voter=>$vote)
                            @if($vote == 1)
                            <li>
                                {!! App\Models\User\User::find($voter)->displayName !!} {{ $voter == Auth::user()->id ? '(you)' : '' }}
                            </li>
                            @endif
                        @endforeach
                        </ul>
                    </div>
                    <div class="col-md">
                        <h5>Approve:</h5>
                        <ul>
                        @foreach($request->voteData as $voter=>$vote)
                            @if($vote == 2)
                            <li>
                                {!! App\Models\User\User::find($voter)->displayName !!} {{ $voter == Auth::user()->id ? '(you)' : '' }}
                            </li>
                            @endif
                        @endforeach
                        </ul>
                    </div>
                </div>
        @else
            <p>No votes have been cast yet!</p>
        @endif
    </div></div>
@endif

@php
$isNewForm = $request->update_type === 'New Form';
$isHolo = $request->character->image->species_id === 2;
$isMYORequest = $request->update_type === 'MYO';
$hasAndroidItem = $request->hasAndroidItem;
$hasBuddyUpgrade = $request->hasBuddyItem;

// Don't show digital form if it's a holo
// Show digital form if it's a MYO Request
// Show digital form if it's a form add request and they haven't attached the android item
// TODO: Will need to be allowed for Holo once I'm ready for the pet updates
$showDigitalForm = (!$isHolo && (($isMYORequest) || ($isNewForm && !$hasAndroidItem))) || (!$isNewForm && !$isMYORequest && $request->hasDigitalData);
// Show Android if it's not a holo and they have attached the android item
$showAndroidForm = (!$isHolo && $hasAndroidItem) || (!$isNewForm && !$isMYORequest && $request->hasAndroidData);
// Show holo if this is a holo myo or if they've attached an android item or if it's a new form and they didn't attach the buddy upgrade item
$showHolobotTab = ($isHolo && $isMYORequest) || ($isHolo && $isNewForm && !$hasBuddyUpgrade) || $hasAndroidItem  || (!$isNewForm && !$isMYORequest && $request->hasHolobotData);
// Show holo-buddy tab if this is an epic holo myo or has the buddy upgrade item
$showHolobuddyTab = ($isHolo && ($request->character->image->rarity->name === 'Epic' || $hasBuddyUpgrade))  || (!$isNewForm && !$isMYORequest && $request->hasHolobuddyData);

global $isComplete;

// Figuring out whether we allow submission or not
$isComplete =
    $request->has_comments && $request->has_addons && (
        // if it'a regular myo request
        (!$isHolo && $isMYORequest && $request->hasDigitalData && (!$hasAndroidItem || $request->hasAndroidData && $request->hasHolobotData))
        // if it's a holo myo
        || ($isHolo && $isMYORequest && $request->hasHolobotData && (!$showHolobuddyTab || $request->hasHolobuddyData))
        // regular new form
        || (!$isHolo && $isNewForm && (!$hasAndroidItem && $request->hasDigitalData || ($hasAndroidItem && $request->hasAndroidData && $request->hasHolobotData))
        // holo new form
        || ($isHolo && $isNewForm && (!$hasBuddyUpgrade && $request->hasHolobotData || ($hasBuddyUpgrade && $request->hasHolobuddyData)))
        // regular form update
        || (!$isHolo && !$isNewForm && !$isMYORequest && $request->hasDigitalData || $request->hasAndroidData))
        // holo form update
        || ($isHolo && !$isNewForm && !$isMYORequest && ($request->hasHolobotData || $request->hasHolobuddyData))
    );
@endphp

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ set_active('designs/' . $request->id) }}" href="{{ url('designs/' . $request->id) }}">@if($request->is_complete && isset($request->image))<i class="text-success fas fa-check-circle fa-fw mr-2"></i> @endif Status</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ set_active('designs/' . $request->id . '/comments') }}" href="{{ url('designs/' . $request->id . '/comments') }}"><i class="text-{{ $request->has_comments ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i> Comments</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ set_active('designs/' . $request->id . '/addons') }}" href="{{ url('designs/' . $request->id . '/addons') }}"><i class="text-{{ $request->has_addons ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i> Add-ons</a>
    </li>
    @if($showDigitalForm)
        <li class="nav-item">
            <a class="nav-link {{ set_active('designs/' . $request->id . '/digital-form') }}" href="{{ url('designs/' . $request->id . '/digital-form') }}">
                <i class="text-{{ $request->hasDigitalData || $request->status === 'Approved' ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i>
                Digital Form
            </a>
        </li>
    @endif
    @if($showAndroidForm)
        <li class="nav-item">
            <a class="nav-link {{ set_active('designs/' . $request->id . '/android-form') }}" href="{{ url('designs/' . $request->id . '/android-form') }}"><i class="text-{{ $request->hasAndroidData || $request->status == 'Approved' ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i> Android Form</a>
        </li>
    @endif
    @if($showHolobotTab)
        <li class="nav-item">
            <a class="nav-link {{ set_active('designs/' . $request->id . '/holobot') }}" href="{{ url('designs/' . $request->id . '/holobot') }}"><i class="text-{{ $request->hasHolobotData || $request->status == 'Approved' ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i> HoloBOT</a>
        </li>
    @endif
    @if($showHolobuddyTab)
        <li class="nav-item">
            <a class="nav-link {{ set_active('designs/' . $request->id . '/holobuddy') }}" href="{{ url('designs/' . $request->id . '/holobuddy') }}"><i class="text-{{ $request->hasHolobuddyData || $request->status == 'Approved' ? 'success far fa-circle' : 'danger fas fa-times'  }} fa-fw mr-2"></i> HoloBUDDY</a>
        </li>
    @endif
</ul>
