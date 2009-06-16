<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once('includes/core.inc.php');

function Unauthorized() {
	header('WWW-Authenticate: Basic realm="Ulteo WebDAV"');
	header('HTTP/1.0 401 Unauthorized');
	die('401 Unauthorized');
}

function ParseURL() {
	$matches = array();
	preg_match('@/webdav\.php/([^/]+)/@', $_SERVER['REQUEST_URI'], $matches);
	if (! is_array($matches) || ! array_key_exists(1, $matches))
		return Unauthorized();

	$sharedfolder_id = $matches[1];

	$sharedfolder = Abstract_SharedFolder::load($sharedfolder_id);
	if (! $sharedfolder)
		return Unauthorized();

	return $sharedfolder;
}

function AuthenticationBasicHTTP() {
	Logger::debug('main', '(webdav) Starting AuthenticationBasicHTTP');

	if (! isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER']))
		return Unauthorized();

	if (! isset($_SERVER['PHP_AUTH_PW']) || empty($_SERVER['PHP_AUTH_PW']))
		return Unauthorized();

	$login = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	$dav_user = Abstract_DAV_User::load($login);
	if (! $dav_user) {
		Logger::error('main', '(webdav) Unable to load DAV user \''.$login.'\'');
		return Unauthorized();
	}

	if ($password != $dav_user->password)
		return Unauthorized();

	$sharedfolder = ParseURL();
	if (! is_object($sharedfolder)) {
		Logger::error('main', '(webdav) ParseURL error => bad request');
		return Unauthorized();
	}

	$userDB = UserDB::getInstance();
	$user = $userDB->import($login);
	$usergroup_ids = array_keys($user->usersGroups());
	$sharedfolder_acls_ids = array_keys($sharedfolder->acls);

	foreach ($usergroup_ids as $usergroup_id) {
		if (in_array($usergroup_id, $sharedfolder_acls_ids)) {
			Logger::debug('main', '(webdav) Ending AuthenticationBasicHTTP => OK');

			return true;
		}
	}

	return Unauthorized();
}

if (! AuthenticationBasicHTTP())
	return Unauthorized();

require_once('HTTP/WebDAV/Server/Filesystem.php');
$server = new HTTP_WebDAV_Server_Filesystem();

$prefs = Preferences::getInstance();

if (! $prefs)
	Unauthorized();
$mysql_conf = $prefs->get('general', 'mysql');

$server->db_host = $mysql_conf['host'];
$server->db_user = $mysql_conf['user'];
$server->db_passwd = $mysql_conf['password'];
$server->db_name = $mysql_conf['database'];
$server->db_prefix = $mysql_conf['prefix'].'dav_';

$server->ServeRequest(SHAREDFOLDERS_DIR);
