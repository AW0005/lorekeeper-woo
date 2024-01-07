@extends('admin.layout')

@section('admin-title') Log of Logs @endsection

@section('scripts')
<style>
table tr td { border: none !important; }
table tr { border-bottom: 1px solid #ccc }

tr > td.nowrap {
    white-space: nowrap;
}
</style>
@endsection

@section('admin-content')
{!! breadcrumbs(['Admin Panel' => 'admin', 'Log of Logs' => 'logs']) !!}

<h1>Log of Logs</h1>
{!! $logs->render() !!}
<div class="w-100 overflow-auto">
<table class="mb-2 w-100">
    <thead>
        <tr>
            <th>Log Date</th>
            <th>Sender</th>
            <th></th>
            <th>Recipient</th>
            <th>Log Type</th>
            <th>More Info {!! add_help('Additional info about a log event - usually any items, currency, or characters involved.') !!}</th>
            <th>Link {!! add_help('The main associated linkable element to the log. This could be an item that was opened, a character that was changed, a submission approved, etc.') !!}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($logs as $log)
        @php
            $extendedLogs = $log->logs;
            // Shop purchases come from the shop
            $sender = $log->event_type == 'Shop Purchase' ?
                $extendedLogs->whereNotNull('shop_id')->first()?->shop : $extendedLogs->whereNotNull('sender_id')->first()?->sender;
            $reciever = $extendedLogs->whereNotNull('recipient_id')->first()?->recipient ?? $extendedLogs->whereNotNull('character_id')->first()?->character;
            $user = $extendedLogs->whereNotNull('user_id')->first()?->user;
            // Find a log with a log type
            $find = $extendedLogs->whereNotNull('log_type')->first();
            $type = $find->log_type ?? $find?->action ?? $log->event_type;

            $info = $extendedLogs->map(function($log) {
                $info = null;
                if($log->model && $log->log !== 'Box Opened' && !is_array($log->model)) $info = (is_object($log->model) ? $log->model->displayName : $log->model).($log->quantity ? ' (×'.$log->quantity.')' : '');
                if(strpos($log->log, 'Staff Reward')) $info = 'Staff Reward: '.$info;
                return $info;
            })->filter()->join('<br/>');
            if($info === '') $info = '-';
            
            // Find a log with a link
            $find = $extendedLogs->first(function ($item) {
                //User update logs are by themselves anyway so we'll just always pick them out:
                if(get_class($item) === 'App\Models\User\UserUpdateLog') return true;
                // item->data has a link we can use in most logs
                // action_details is the alternate for staff logs
                // change_log is the alternate for character logs
                // Box open logs don't have a link and should use the attached item
                // Shop Purchase logs link the user, but we want the item bought
                return strpos($item->log, 'Purchased from')
                    || strpos($item->data, '<a')
                    || strpos($item->action_details, '<a')
                    || $item->log == 'Box Opened'
                    || $item->log == 'User Transfer';
            });
            if(!$find) $find = $extendedLogs->first();
            $link = $find->data ?? $find->action_details;

            // Hard overrides for unusual cases
            if($find->log == 'Box Opened') $link = $find->item->displayName;
            if($find->log_type == 'Shop Purchase') $link = $find->item->displayName;
            if($find->log_type == 'User Transfer') $link = $find->currency?->displayName ?? $find->item?->displayName;
            if(get_class($find) === 'App\Models\User\UserUpdateLog') $link = $find->user->displayName;
            if(get_class($find) === 'App\Models\Character\CharacterLog') $link = $find->character->displayName;

            $linkLoc = strpos($link, '<a');
            $linkEnd = strpos($link, 'a>');
            $linkStr = isset($linkLoc) ? substr($link, $linkLoc, $linkEnd - $linkLoc + 2) : '';
        @endphp
        <tr>
            <td class='nowrap'>{!! pretty_date($log->created_at) !!}</td>
            <td class='nowrap'>
                {!! $sender?->displayName ?? $user?->displayName !!}
            </td>
            <td><i class="fas fa-long-arrow-alt-right"></i></td>
            <td class='nowrap'>{!! $reciever ? $reciever->displayName : '' !!}</td>
            <td>{!! $type !!}</td>
            <td>{!! $info !!}</td>
            <td class='nowrap'>{!! $linkStr !!}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
{!! $logs->render() !!}
@endsection
