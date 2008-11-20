<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/
class UserDB_unix {
	public function __construct(){
	}
	
	public function import($login_){
		Logger::debug('main','UserDB::unix::import('.$login_.')');
		$tab = posix_getpwnam($login_);
		if (is_array($tab)){
			$u = new User();
			if (isset($tab['name']))
				$u->setAttribute('login',$tab['name']);
			if (isset($tab['gecos'])){
				$ex = explode(',',$tab['gecos']);
				$u->setAttribute('displayname',$ex[0]);
			}
			if (isset($tab['uid']))
				$u->setAttribute('uid',$tab['uid']);
			if (isset($tab['gid']))
				$u->setAttribute('gid',$tab['gid'],1);
			if (isset($tab['dir']))
				$u->setAttribute('homedir',$tab['dir']);
			return $u;
		}
		return NULL;
	}
	
	public function isWriteable(){
		return false;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function needPassword(){
		return true;
	}
	
	public function getList(){
		Logger::debug('main','UserDB::unix::getList');
		$users = array();
		$content = file_get_contents('/etc/passwd');
		$contents = explode("\n",$content);
		foreach($contents as $line){
			$infos = explode(':',$line);
			$u = $this->import($infos[0]);
			if (!is_null($u) && $this->isOk($u))
				$users []=$u;
		}
		return $users;
	}
	
	public function isOK($user_){
		$minimun_attribute = array_unique(array_merge(array('login','displayname','uid','gid','homedir'),get_needed_attributes_user_from_module_plugin()));
		if (is_object($user_)){
			foreach ($minimun_attribute as $attribute){
				if ($user_->hasAttribute($attribute) == false)
					return false;
				else {
					$a = $user_->getAttribute($attribute);
					if ( is_null($a) || $a == "")
						return false;
				}
			}
			if (($user_->getAttribute('uid') < 1000)||($user_->getAttribute('uid') == 65534))
				return false;
			
			return true;
		}
		else
			return false;
	}
	
	public function authenticate($user_,$password){
		// TODO (pam module ?
		return true;//...
	}
	
	// 	public function showListOnLog(){
	// 		// get global config['showListOnLog']
	// 	}
	
	public function configuration(){
		return array();
	}
	
	public function prefsIsValid($prefs_) {
		return true;
	}
	
	public static function prettyName() {
		return _('local');
	}
}
