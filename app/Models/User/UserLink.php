<?php

namespace App\Models\User;

use App\Models\Model;

class UserLink extends Model {
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id', 'site_url'
  ];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'user_links';

  /**********************************************************************************************

        RELATIONS

   **********************************************************************************************/

  /**
   * Get the user this set of settings belongs to.
   */
  public function user() {
    return $this->belongsTo('App\Models\User\User', 'user_id');
  }

  /**********************************************************************************************

        ACCESSORS

   **********************************************************************************************/

  /**
   * Displays the user's alias, linked to the appropriate site.
   *
   * @return string
   */
  public function getDisplayNameAttribute() {
    return prettyProfileLink($this->site_url);
  }
}
