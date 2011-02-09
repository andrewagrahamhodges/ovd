/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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
 */

package org.ulteo.ovd.client.profile;

import java.awt.Dimension;

public class ProfileProperties {
	public static final int MODE_AUTO = 0;
	public static final int MODE_DESKTOP = 1;
	public static final int MODE_APPLICATIONS = 2;

	private String login = System.getProperty("user.name");
	private String host = null;
	private int port;
	private int sessionMode = -1;
	private boolean autoPublish = false;
	private boolean useLocalCredentials = false;
	private Dimension screensize = null;
	private String lang = null;
	private String keymap = null;
	private boolean showProgressbar = true;
	
	public ProfileProperties() {}

	public ProfileProperties(String login_, String host_, int port_, int sessionMode_, boolean autoPublish_, boolean useLocalCredentials_, Dimension screensize_, String lang, String keymap) {
		this.login = login_;
		this.host = host_;
		this.port = port_;
		this.sessionMode = sessionMode_;
		this.autoPublish = autoPublish_;
		this.useLocalCredentials = useLocalCredentials_;
		this.screensize = screensize_;
		this.lang = lang;
		this.keymap = keymap;
	}

	public String getLogin() {
		return this.login;
	}

	public void setLogin(String login_) {
		this.login = login_;
	}

	public String getHost() {
		return this.host;
	}

	public void setHost(String host_) {
		this.host = host_;
	}

	public int getPort() {
		return this.port;
	}

	public void setPort(int port_) {
		this.port = port_;
	}
	
	public int getSessionMode() {
		return this.sessionMode;
	}

	public void setSessionMode(int sessionMode_) {
		this.sessionMode = sessionMode_;
	}

	public boolean getAutoPublish() {
		return this.autoPublish;
	}

	public void setUseLocalCredentials(boolean useLocalCredentials_) {
		this.useLocalCredentials = useLocalCredentials_;
	}
	
	public boolean getUseLocalCredentials() {
		return this.useLocalCredentials;
	}

	public void setAutoPublish(boolean autoPublish_) {
		this.autoPublish = autoPublish_;
	}

	public Dimension getScreenSize() {
		return this.screensize;
	}

	public void setScreenSize(Dimension screenSize_) {
		this.screensize = screenSize_;
	}
	
	public String getLang() {
		return this.lang;
	}
	
	public void setLang(String lang) {
		this.lang = lang;
	}
	
	public String getKeymap() {
		return this.keymap;
	}
	
	public void setKeymap(String keymap) {
		this.keymap = keymap;
	}

	public boolean getShowProgressbar() {
		return this.showProgressbar;
	}

	public void setShowProgressbar(boolean showProgressbar_) {
		this.showProgressbar = showProgressbar_;
	}
}
