/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

var Desktop = Class.create(Daemon, {
	mode: 'desktop',

	initialize: function(applet_version_, applet_main_class_, in_popup_, debug_) {
		Daemon.prototype.initialize.apply(this, [applet_version_, applet_main_class_, in_popup_, debug_]);
	},

	parse_do_started: function(transport) {
		this.push_log('debug', '[desktop] parse_do_started(transport@do_started())');

		var server = false;

		var servers = this.servers.values();
		for (var i=0; i < servers.length; i++)
			server = servers[i];

		if (! server)
			setTimeout(this.parse_do_started.bind(this, transport), 1000);
		else {
			this.refresh_body_size();

			var applet_width = (this.my_width-(this.my_width % 4));
			var applet_height = (this.my_height*applet_width/this.my_width);

			var applet_html_string = '<applet id="ulteoapplet" name="ulteoapplet" code="'+this.applet_main_class+'" codebase="applet/" archive="log4j-1.2.jar,'+this.applet_version+'" cache_archive="log4j-1.2.jar,'+this.applet_version+'" cache_archive_ex="log4j-1.2.jar,'+this.applet_version+';preload" mayscript="true" width="'+applet_width+'" height="'+applet_height+'"> \
				<param name="name" value="ulteoapplet" /> \
				<param name="code" value="'+this.applet_main_class+'" /> \
				<param name="codebase" value="applet/" /> \
				<param name="archive" value="log4j-1.2.jar,'+this.applet_version+'" /> \
				<param name="cache_archive" value="log4j-1.2.jar,'+this.applet_version+'" /> \
				<param name="cache_archive_ex" value="log4j-1.2.jar,'+this.applet_version+';preload" /> \
				<param name="mayscript" value="true" /> \
				\
				<param name="server" value="'+server.fqdn+'" /> \
				<param name="port" value="3389" /> \
				<param name="username" value="'+server.username+'" /> \
				<param name="password" value="'+server.password+'" /> \
				\
				<param name="keymap" value="'+this.keymap+'" /> \
				<param name="multimedia" value="'+this.multimedia+'" /> \
				<param name="redirect_client_printers" value="'+this.redirect_client_printers+'" /> \
			</applet><div id="ulteoprintingappletcontainer"></div>';

			$('desktopAppletContainer').show();
			$('desktopAppletContainer').innerHTML = applet_html_string;

			this.load_printing_applet();

			return true;
		}
	}
});
