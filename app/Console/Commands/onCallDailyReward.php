<?php

namespace App\Console\Commands;

use DB;
use Config;
use Illuminate\Console\Command;

use App\Models\User\User;
use App\Models\Currency\Currency;
use App\Services\CurrencyManager;

class onCallDailyReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'on-call-daily-reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command should only be run by the cron job as it rewards anyone with the on call power a set amount.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("\n".'*******************************');
        $this->info('* ON CALL DAILY REWARD RUNNING *');
        $this->info('*******************************');

        if(Config::get('lorekeeper.extensions.staff_rewards.enabled')){
            $reward = DB::table('staff_actions')->where('key', 'on_call')->first()->value ?? 1;
            $currency = Currency::find(Config::get('lorekeeper.extensions.staff_rewards.currency_id'));
            if ($currency) {
                // need to foreach through every user with the on_call power and not admin
                $usersOnCall = User::get()->filter(function ($value, $key) {
                    return $value->rank->name !== 'Admin' && $value->rank->hasPower('on_call');
                });

                foreach ($usersOnCall as $user) {
                    if (!(new CurrencyManager)->creditCurrency(null, $user, 'Staff Reward', 'On Call Daily Reward', $currency, $reward)) {
                        return false;
                    }
                };
            }
        }

        $this->info('Rewards have been distributed');
    }
}
