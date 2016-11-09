<?php
namespace Addons\Core\Controllers;
use Illuminate\Support\Facades\Auth;

trait InitTrait {

	public $site;
	public $user;

	private function initCommon()
	{
		$this->site = app('config')->get('site');
		$this->site['titles'][] = ['title' => $this->site['title'], 'url' => '', 'target' => '_self'];
	}

	private function initMember()
	{
		$this->user = Auth::check() ? Auth::User() : new \App\User;
	}

	protected function subtitle($title, $url = NULL, $target = '_self')
	{
		return call_user_func_array($this->site['title_reverse'] ? 'array_unshift' : 'array_push', [&$this->site['titles'], compact('title', 'url', 'target')]);
	}
	
}