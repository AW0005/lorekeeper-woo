@if($award)
    {!! Form::open(['url' => 'admin/data/awards/delete/'.$award->id]) !!}

    <p>You are about to delete the badge <strong>{{ $award->name }}</strong>. This is not reversible. If this badge exists in at least one user's possession, you will not be able to delete this award.</p>
    <p>Are you sure you want to delete <strong>{{ $award->name }}</strong>?</p>

    <div class="text-right">
        {!! Form::submit('Delete Badge', ['class' => 'btn btn-danger']) !!}
    </div>

    {!! Form::close() !!}
@else
    Invalid badge selected.
@endif
