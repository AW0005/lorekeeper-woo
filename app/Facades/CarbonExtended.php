<?php

namespace App\Facades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

class CarbonExtended extends Carbon {

  protected static function getFacadeAccessor() {
    return 'carbonExtended';
  }

  public function diffForHumans($other = ['join' => ' and '], $syntax = NULL, $short = false, $parts = 2, $options = NULL) {
    return parent::diffForHumans($other, $syntax, $short, $parts, $options);
  }
}
