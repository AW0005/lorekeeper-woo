@if($request->user_id == Auth::user()->id)
    <p>This will submit the design approval request. While the request is in the queue, <u>you will not be able to edit it</u>. </p>
    <p>Are you sure you want to submit this request?</p>
    {!! Form::open(['url' => 'designs/'.$request->id.'/submit', 'class' => 'text-right']) !!}
        {!! Form::submit('Submit Request', ['class' => 'btn btn-primary']) !!}
    {!! Form::close() !!}
@else
    <div>You cannot submit this request.</div>
@endif
