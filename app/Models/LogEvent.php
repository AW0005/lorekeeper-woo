<?php

namespace App\Models;

use App\Models\Model;
use Carbon\Carbon;

class LogEvent extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type'
    ];

    /**
     * Whether the model contains timestamps to be saved and updated.
     *
     * @var string
     */
    public $timestamps = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'log_events';

    /**********************************************************************************************

        ATTRIBUTES

    **********************************************************************************************/

    /**
     * Get the logs for this event
     */
    public function getLogsAttribute() {
        $oneMonth = Carbon::today()->subDays(config('lorekeeper.extensions.logDaysSince') ?? 30)->toDateString();
        $logs = $this->hasMany('App\Models\Character\CharacterLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get();
        $logs = $logs->concat($this->hasMany('App\Models\User\UserCharacterLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat($this->hasMany('App\Models\Currency\CurrencyLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat($this->hasMany('App\Models\Item\ItemLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat($this->hasMany('App\Models\Shop\ShopLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get());
        $logs = $logs->concat($this->hasMany('App\Models\User\UserUpdateLog', 'event_id')->whereDate('created_at', '>', $oneMonth)->get());
        return $logs;
    }

}
