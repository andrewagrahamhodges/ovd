<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Logger {
	public static function append($module_='main', $data_='', $level_='info') {
		if ($data_ === '')
			return;

		$level_flags = array($level_);

		if (in_array($level_, $level_flags)) {
			$logfile = @fopen(CONNECTME_LOGS.'/'.strtolower($module_).'.log', 'a');
			@fputs($logfile, @date('M j H:i:s').' - '.$_SERVER['REMOTE_ADDR'].' - '.strtoupper($level_).' - '.$data_."\r\n");
			@fclose($logfile);
		}
	}

	public static function debug($module_='main', $data_='') {
		Logger::append($module_, $data_, 'debug');
	}

	public static function info($module_='main', $data_='') {
		Logger::append($module_, $data_, 'info');
	}

	public static function warning($module_='main', $data_='') {
		Logger::append($module_, $data_, 'warning');
	}

	public static function error($module_='main', $data_='') {
		Logger::append($module_, $data_, 'error');
	}

	public static function critical($module_='main', $data_='') {
		Logger::append($module_, $data_, 'critical');
	}
}
