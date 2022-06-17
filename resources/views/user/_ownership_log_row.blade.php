@php
    $wasRemoved = ($user && $log->recipient_id != $user->id) || str_contains($log->log, 'Deleted')
@endphp
<div class="d-flex row flex-wrap col-12 mt-1 pt-1 px-0 ubt-top">
  <div class="col-6 col-md">
    <i class="btn py-1 m-0 px-2 btn-{{ !$wasRemoved ? 'success' : 'danger' }} fas {{ !$wasRemoved ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-2"></i>
    {!! $log->sender ? $log->sender->displayName : $log->displaySenderAlias !!}
  </div>
  <div class="col-6 col-md">{!! $log->recipient ? $log->recipient->displayName : $log->displayRecipientAlias !!}</div>
  @if(isset($showCharacter))
  <div class="col-6 col-md">
          <td>{!! $log->character ? $log->character->displayName : '---' !!}</td>
    </div>
  @endif
  <div class="col-6 col-md-4">{!! $log->log !!}</div>
  <div class="col-6 col-md">{!! pretty_date($log->created_at) !!}</div>
</div>
