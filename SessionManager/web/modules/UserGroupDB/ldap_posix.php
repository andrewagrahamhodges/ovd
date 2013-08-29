<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
class UserGroupDB_ldap_posix {
	protected $cache_import;
	protected $cache_list;
	protected $cache_user_members;
	protected $preferences;
	
	public function __construct() {
		$this->cache_import = array();
		$this->cache_user_members = array();
		$this->cache_list = NULL;
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$a_pref = $prefs->get('UserGroupDB', 'ldap_posix');
		if (is_array($a_pref)) {
			$this->preferences = $a_pref;
		}
		else { // ugly...
			$this->preferences = array();
		}
	}
	
	public function __toString() {
		$ret = get_class($this).'()';
		return $ret;
	}
	
	public function canShowList() {
		return true;
	}
	
	public function isDynamic() {
		return false;
	}
	
	public function makeLDAPconfig($config_=NULL) {
		if (is_null($config_) === false) {
			return $config_;
		}
		else {
			$userDBAD = UserDB::getInstance();
			if (method_exists($userDBAD, 'makeLDAPconfig') === false) {
				Logger::error('main', 'UserGroupDB::ldap_posix::makeLDAPconfig makeLDAPconfig is not avalaible');
				return NULL;
			}
			
			$configLDAP = $userDBAD->makeLDAPconfig();
			
			$configLDAP['match'] = array();
			if (array_key_exists('match', $this->preferences)) {
				$configLDAP['match'] = $this->preferences['match'];
			}
			
			$configLDAP['userbranch'] = '';
			if (array_key_exists('group_dn', $this->preferences)) {
				$configLDAP['userbranch'] = $this->preferences['group_dn'];
			}
			
			if (array_key_exists('filter', $this->preferences)) {
				$configLDAP['filter'] = $this->preferences['filter'];
			}
			return $configLDAP;
		}
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		$groups = array();
		$configLDAP = $this->makeLDAPconfig();
		
		$ldap = new LDAP($configLDAP);
		$contains = '*';
		if ($contains_ != '')
			$contains .= $contains_.'*';
		
		if ($configLDAP['filter'] != '') {
			$filter = '(&'.$configLDAP['filter'].'(|';
		}
		else {
			$filter = '(|';
		}
		foreach ($attributes_ as $attribute) {
			$filter .= '('.$configLDAP['match'][$attribute].'='.$contains.')';
		}
		if ($configLDAP['filter'] != '') {
			$filter .= ')';
		}
		$filter .= ')';
		$sr = $ldap->search($filter, NULL, $limit_);
		if ($sr === false) {
			Logger::error('main', 'UserDB::ldap_posix::getUsersContaint search failed');
			return NULL;
		}
		$sizelimit_exceeded = $ldap->errno() === 4; // LDAP_SIZELIMIT_EXCEEDED => 0x04 
		
		$infos = $ldap->get_entries($sr);
		
		foreach ($infos as $dn => $info) {
			$ug = $this->generateUsersGroupFromRow($info, $dn, $configLDAP['match']);
			$groups[$dn] = $ug;
		}
		return array($groups, $sizelimit_exceeded);
	}
	
	public function isWriteable() {
		return false;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}
	
	public function import($id1_) {
		Logger::debug('main',"UserGroupDB::ldap_posix::import (id = $id1_)");
		
		if (is_base64url($id1_))
			$id = base64url_decode($id1_);
		else
			$id = $id1_;
		
		if (array_key_exists($id, $this->cache_import)) {
			return $this->cache_import[$id];
		}
		elseif (is_array($this->cache_list) && array_key_exists($id, $this->cache_list)) {
			return $this->cache_list[$id];
		}
		else {
			$ug = $this->import_nocache($id);
			$this->cache_import[$id] = $ug;
			return $ug;
		}
	}
	
	protected function import_nocache($id_) {
		Logger::debug('main',"UserGroupDB::ldap_posix::import_nocache (id = $id_)");
		$configLDAP = $this->makeLDAPconfig();
		
		if (str_endswith(strtolower($id_), strtolower($configLDAP['suffix'])) === true) {
			$id2 = substr($id_, 0, -1*strlen($configLDAP['suffix']) -1);
		}
		else
		{
			$id2 = $id_;
		}
		$expl = explode(',',$id2,2);
		if (count($expl) == 1) {
			$expl = array($id2, '');
		}
		$configLDAP['userbranch'] = $expl[1];
		
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search($expl[0], array_values($configLDAP['match']));
		if ($sr === false) {
			Logger::error('main', "UserGroupDB::ldap_posix::import_nocache search failed for ($id_)");
			return NULL;
		}
		
		$infos = $ldap->get_entries($sr);
		$keys = array_keys($infos);
		if (!is_array($infos) || $infos === array())
			return NULL;
		$dn = $keys[0];
		$info = $infos[$dn];
		return $this->generateUsersGroupFromRow($info, $dn, $configLDAP['match']);
	}

	public function imports($ids_) {
		Logger::debug('main','UserGroupDB::ldap_posix::imports (['.implode('', $ids_).'])');
		
		$result = array();
		$ids_filter = array();
		foreach($ids_ as $dn) {
			if (array_key_exists($dn, $this->cache_import)) {
				$g = $this->cache_import[$dn];
				$result[$g->getUniqueID()] = $g;
			}
			elseif (is_array($this->cache_list) && array_key_exists($dn, $this->cache_list)) {
				$g = $this->cache_list[$dn];
				$result[$g->getUniqueID()] = $g;
			}
			else {
				list($rdn, $subpath) = explode(',', $dn, 2);
				array_push($ids_filter, '('.$rdn.')');
			}
		}
		
		if (count($ids_filter) == 0) {
			return $result;
		}
		
		$configLDAP = $this->makeLDAPconfig();
		$ldap = new LDAP($configLDAP);

		$filter= '(&'.$this->join_filters(array($configLDAP['filter'], '(|'.implode('', $ids_filter).')')).')';
		$sr = $ldap->search($filter, array_values($configLDAP['match']));
		$infos = $ldap->get_entries($sr);
		
		if (! is_array($infos))
			return $result;
		
		foreach ($infos as $dn => $info) {
			if (! in_array($dn, $ids_)) {
				continue;
			}
			
			$g = $this->generateUsersGroupFromRow($info, $dn, $configLDAP['match']);
			if (! is_object($g)) {
				continue;
			}
			
			$this->cache_import[$dn] = $g;
			$result[$dn] = $g;
		}
		
		return $result;
	}
	
	public static function join_filters($filters) {
		$res = array();
		foreach($filters as $filter) {
			$filter = trim($filter);
			if (strlen($filter) == 0) {
				continue;
			}
			
			if (! (str_startswith($filter, '(') and str_endswith($filter, ')'))) {
				$filter = '('.$filter.')';
			}
			
			array_push($res, $filter);
		}
		
		if (count($res) == 0) {
			return null;
		}
		
		return implode('', $res);
	}

	public function get_by_user_members($user_login_) {
		Logger::debug('main', "UserGroupDB::ldap_posix::get_by_user_members ($user_login_)");
		if (array_key_exists($user_login_, $this->cache_user_members)) {
			return $this->cache_user_members[$user_login_];
		}
		
		$config_ldap = $this->makeLDAPconfig();
		$filter= '(&'.$this->join_filters(array($config_ldap['filter'], $config_ldap['match']['member'].'='.$user_login_)).')';
		$ldap = new LDAP($config_ldap);
		$sr = $ldap->search($filter, array_keys($config_ldap['match']));
		if ($sr === false) {
			Logger::error('main',"UserGroupDB::ldap_posix::get_by_user_members search failed for ($user_login_)");
			return NULL;
		}
		
		$infos = $ldap->get_entries($sr);
		if ($infos === array()) {
			return array();
		}
		
		$groups = array();
		foreach ($infos as $dn => $info) {
			$g = $this->generateUsersGroupFromRow($info, $dn, $config_ldap['match']);
			if (! is_object($g))
				continue;
			
			$this->cache_import[$dn] = $g;
			$groups[$g->getUniqueID()] = $g;
		}
		
		$this->cache_user_members[$user_login_] = $groups;
		return $groups;
	}
	
	public function get_users_by_group_membership($group_id_) {
		Logger::debug('main', "UserGroupDB::ldap_posix::get_users_by_group_membership ($group_id_)");
		
		$group = $this->import($group_id_);
		if (isset($group->extras) === false || ! is_array($group->extras) || !array_key_exists('member', $group->extras)) {
			// ???
			return array();
		}
		
		$userDB = UserDB::getInstance();
		return $userDB->imports($group->extras['member']);
	}

	public function getList() {
		Logger::debug('main','UserGroupDB::ldap_posix::getList');
		
		if (is_array($this->cache_list)) {
			$groups = $this->cache_list;
		}
		else {
			$groups = $this->getList_nocache();
			$this->cache_list = $groups;
		}
		
		return $groups;
	}
	public function getList_nocache() {
		Logger::debug('main','UserGroupDB::ldap_posix::getList_nocache');
		
		$configLDAP = $this->makeLDAPconfig();
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search('cn=*', NULL);
		$infos = $ldap->get_entries($sr);
		$groups = array();
		if (! is_array($infos))
			return $groups;
		
		foreach ($infos as $dn => $info) {
			$g = $this->generateUsersGroupFromRow($info, $dn, $configLDAP['match']);
			if (is_object($g))
				$groups[$dn] = $g;
		}
		
		return $groups;
	}
	
	protected function generateUsersGroupFromRow($info, $dn_, $match_) {
		$extras = array();
		$buf = array();
		foreach ($match_ as $attribut => $match_ldap) {
			if (isset($info[$match_ldap][0])) {
				$buf[$attribut] = $info[$match_ldap][0];
			}
			if (isset($info[$match_ldap]) && is_array($info[$match_ldap])) {
				if (isset($info[$match_ldap]['count']))
					unset($info[$match_ldap]['count']);
				$extras[$attribut] = $info[$match_ldap];
			}
			else {
				$extras[$attribut] = array();
			}
		}
		if (!isset($buf['description']))
			$buf['description'] = '';
		else if (is_array($buf['description'])) {
			$buf['description'] = array_pop($buf['description']);
		}
		
		if (!isset($buf['name']))
			$buf['name'] = $dn_;
		else if (is_array($buf['name'])) {
			$buf['name'] = array_pop($buf['name']);
		}
		
		$ug = new UsersGroup($dn_, $buf['name'], $buf['description'], true);
		$ug->extras = $extras;
		return $ug;
	}
	
	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_input('group_dn','');
		$ret []= $c;
		$c = new ConfigElement_dictionary('match', array('description' => 'description', 'name' => 'cn', 'member' => 'memberUid'));
		$ret []= $c;
		$c = new ConfigElement_input('filter', '(objectClass=posixGroup)');
		$ret []= $c;
		return $ret;
	}
	
	public static function isDefault() {
		return false;
	}
	
	public static function liaisonType() {
		return array(array('type' => 'UsersGroup', 'owner' => 'ldap_posix'));
	}
	
	public function add($usergroup_){
		return false;
	}
	
	public function remove($usergroup_){
		if ($usergroup_->isDefault()) {
			// unset the default usergroup
			$prefs = new Preferences_admin();
			$mods_enable = $prefs->set('general', 'user_default_group', '');
			$prefs->backup();
		}
		return true;
	}
	
	public function update($usergroup_){
		return true;
	}
	
	public static function init($prefs_) {
		return true;
	}
	
	public static function enable() {
		return true;
	}
}
 
