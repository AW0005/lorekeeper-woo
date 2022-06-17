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

class LogsController extends Controller
{
    /**
     * Shows the logs index.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getIndex()
    {
        $oneMonth = Carbon::today()->subDays(30)->toDateString();
        $logs = CharacterLog::whereDate('created_at', '>', $oneMonth)->get();
        $logs = $logs->concat(UserCharacterLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(CurrencyLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(ItemLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(AwardLog::whereDate('created_at', '>', $oneMonth)->get());
        // Redundant with the ItemLog
        $logs = $logs->concat(ShopLog::whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat(UserUpdateLog::whereDate('created_at', '>', $oneMonth)->get());

        dd($logs->sortByDesc('created_at'));
        return view('admin.logs', [
            'logs' => $logs->sortByDesc('created_at')->paginate(20)
        ]);
    }
}
