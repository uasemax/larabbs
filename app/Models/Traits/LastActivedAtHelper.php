<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use Redis;

trait LastActivedAtHelper
{
	protected $hash_prefix = 'larabbs_last_actived_at_';
	protected $field_prefix = 'user_';

	public function recordLastActivedAt()
	{
		$data = Carbon::now()->toDateString();
		$hash = $this->hash_prefix . $data;
		$field = $this->field_prefix . $this->id;
		$now = Carbon::now()->toDateTimeString();
		Redis::hSet($hash, $field, $now);
	}

	public function syncUserActivedAt()
	{
		$hash = $this->getHashFromDateString(Carbon::yesterday()->toDateString());
		$dates = Redis::hGetAll($hash);
		foreach($dates as $user_id => $actived_at){
			$user_id = str_replace($this->field_prefix, '', $user_id);
			if($user = $this->find($user_id)){
				$user->last_actived_at = $actived_at;
				$user->save();
			}
		}
		Redis::del($hash);
	}

	public function getLastActivedAtAttribute($value)
	{
		$hash = $this->getHashFromDateString(Carbon::now()->toDateString());
		$field = $this->field_prefix . $this->id;
		$datetime = Redis::hGet($hash, $field) ?: $value;
		if ($datetime){
			return new Carbon($datetime);
		}else{
			return $this->created_at;
		}
	}

	public function getHashFromDateString($date)
	{
		return $this->hash_prefix . $date;
	}

	public function getHashField()
	{
		return $this->field_prefix . $this->id;
	}
}