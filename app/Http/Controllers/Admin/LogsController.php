<?php

namespace App\Http\Controllers\Admin;

use DB;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Models\User\UserCharacterLog;
use App\Models\Character\CharacterLog;
use App\Models\Currency\CurrencyLog;
use App\Models\Item\ItemLog;
use App\Models\Award\AwardLog;
use App\Models\Shop\ShopLog;
use App\Models\User\UserUpdateLog;
use App\Models\AdminLog;

class LogsController extends Controller
{

    /**
     * Show admin logs.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getIndex()
    {
        return view('admin.logs', [
            'logs' => Adminlog::orderBy('created_at', 'DESC')->get()->paginate(20)
        ]);
    }

    /**
     * Shows the logs index.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getLogofLogs()
    {
        $oneMonth = Carbon::today()->subDays(30)->toDateString();
        $logs = CharacterLog::whereDate('created_at', '>', $oneMonth)->get();
        $logs = $logs->concat(UserCharacterLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(CurrencyLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(ItemLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(AwardLog::whereDate('created_at', '>', $oneMonth)->get());
        // Redundant with Item and Currency Logs
        // $logs = $logs->concat(ShopLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(UserUpdateLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(Adminlog::whereDate('created_at', '>', $oneMonth)->get());

        return view('admin.logoflogs', [
            'logs' => $logs->unique(function($item) {
                // de-dupes for logs that would show up in multiple logs
                return $item->created_at.$item->log.($item->item ? $item->item->name : '').($item->recipient ? $item->recipient->name : '');
            })->sortByDesc('created_at')->paginate(20)
        ]);
    }
}
