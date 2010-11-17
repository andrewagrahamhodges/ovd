<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

function locale2unix($locale_) {
	if (preg_match('/[a-z]+_[A-Z]+\.[a-zA-Z-0-9]+/', $locale_))
		return $locale_;

	$locale = strtolower($locale_);
	$locales = array(
		'ar'	=>	'ar_AE',
		'en'	=>	'en_US',
		'ja'	=>	'ja_JP',
	);

	if (! preg_match('/[a-zA-Z-_]/', $locale))
		$locale = $locales['en'];

	if (strlen($locale) == 2) {
		if (array_key_exists($locale, $locales))
			$locale = $locales[$locale];
		else
			$locale = $locale.'_'.strtoupper($locale);
	}
	elseif (strlen($locale) == 5)
		$locale = substr($locale, 0, 2).'_'.strtoupper(substr($locale, -2));

	$locale .= '.UTF-8';

	return $locale;
}

setlocale(LC_ALL, locale2unix($_REQUEST['lang']));
$domain = 'uovdclient';
bindtextdomain($domain, LOCALE_DIR);
textdomain($domain);

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$root = $dom->createElement('translations');
$dom->appendChild($root);

$translations = array(
	'close'							=>	_('Close'),

	'session_manager'				=>	_('Session Manager'),
	'login'							=>	_('Login'),
	'password'						=>	_('Password'),
	'use_local_credentials'			=>	_('Use local credentials'),
	'use_local_credentials_yes'		=>	_('Yes'),
	'use_local_credentials_no'		=>	_('No'),
	'mode'							=>	_('Mode'),
	'mode_desktop'					=>	_('Desktop'),
	'mode_portal'					=>	_('Portal'),
	'fullscreen'					=>	_('Fullscreen'),
	'fullscreen_yes'				=>	_('Yes'),
	'fullscreen_no'					=>	_('No'),
	'language'						=>	_('Language'),
	'keyboard_layout'				=>	_('Keyboard layout'),
	'use_popup'						=>	_('Use pop-up'),
	'use_popup_yes'					=>	_('Yes'),
	'use_popup_no'					=>	_('No'),
	'debug'							=>	_('Debug'),
	'debug_yes'						=>	_('Yes'),
	'debug_no'						=>	_('No'),
	

	'advanced_settings'				=>	_('Advanced settings'),
	'connect'						=>	_('Connect'),

	'system_compatibility_check_1'	=>	_('Checking for system compatibility'),
	'system_compatibility_check_2'	=>	_('If this is your first time here, a Java security window will show up and you have to accept it to use the service.'),
	'system_compatibility_check_3'	=>	_('You are advised to check the "<em>Always trust content from this publisher</em>" checkbox.'),

	'system_compatibility_error_1'	=>	_('System compatibility error'),
	'system_compatibility_error_2'	=>	_('Java is not available either on your system or in your web browser.'),
	'system_compatibility_error_3'	=>	_('Please install Java extension for your web browser or contact your administrator.'),
	'system_compatibility_error_4'	=>	_('You have not accepted the Java security window.'),
	'system_compatibility_error_5'	=>	_('You <strong>cannot</strong> have access to this service.'),

	'loading_ovd'					=>	_('Loading Open Virtual Desktop'),
	'welcome'						=>	_('Welcome!'),
	'suspend'						=>	_('Suspend'),
	'logout'						=>	_('Logout'),

	'my_apps'						=>	_('My applications'),
	'running_apps'					=>	_('Running applications'),
	'my_files'						=>	_('My files')
);

foreach ($translations as $id => $string) {
	$node = $dom->createElement('translation');
	$node->setAttribute('id', $id);
	$node->setAttribute('string', $string);
	$root->appendChild($node);
}

echo $dom->saveXML();
die();
