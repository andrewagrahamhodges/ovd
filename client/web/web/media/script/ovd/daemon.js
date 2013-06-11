/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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

var Daemon = Class.create({
	sessionmanager: null,

	debug: false,
	explorer: false,

	my_width: 0,
	my_height: 0,

	mode: '',
	keymap: 'en-us',
	duration: -1,
	rdp_input_method: null,

	settings: null, // Hash

	servers: null, // Hash
	webapp_servers: null, // Hash
	liaison_server_applications: null, // Hash

	persistent: false,

	session_status: '',
	session_status_old: '',
	sessionmanager_request_time: 2000,

	loop_timer: null,

	ready: false,
	ready_lock: false,
	started: false,
	started_lock: false,
	stopped: false,
	stopped_lock: false,

	error_message: '',

	progressbar_value: 0,
	progress_bar_step: 20,

	application_token: 0,
	  
	session_ready_callback: null,

	initialize: function(debug_) {
		this.settings = new Hash();
		this.servers = new Hash();
		this.webapp_servers = new Hash();
		this.liaison_server_applications = new Hash();
		this.session_ready_callback = new Array();

		this.debug = debug_;

		this.refresh_body_size();

		if (this.debug) {
			Logger.init_instance();
		}

		if (jQuery('#progressBar')[0] && jQuery('#progressBarContent')[0])
			this.progressBar();

		window.onbeforeunload = function(e) {
			return i18n.get('window_onbeforeunload');
		}

		try {
			this.rdp_input_method = rdp_input_method;
		} catch(e) {}
		
		Event.observe(window, 'unload', this.client_exit.bind(this));
	},
	
	finalize: function() {},

	refresh_body_size: function() {
		if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
			this.my_width  = document.documentElement.clientWidth;
			this.my_height = document.documentElement.clientHeight;
		} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
			this.my_width  = document.body.clientWidth;
			this.my_height = document.body.clientHeight;
		}

		if (this.debug)
			this.my_height = parseInt(this.my_height)-149;
	},

	is_ready: function() {
		return this.ready;
	},

	is_started: function() {
		return this.started;
	},

	is_stopped: function() {
		return (this.stopped || this.session_status == 'unknown');
	},

	progressBar: function() {
		if (! jQuery('#progressBar')[0] || ! jQuery('#progressBarContent')[0])
			return false;

		if (this.progressbar_value > 100)
			this.progressbar_value = 100;

		this.progressbar_value += this.progress_bar_step;

		jQuery('#progressBarContent').width(this.progressbar_value+'%');

		if (this.progressbar_value < 100)
			setTimeout(this.progressBar.bind(this), 500);
	},

	warn_expire: function() {
		if (! this.is_stopped()) {
			Logger.warn('[daemon] warn_expire() - Session will expire in 3 minutes');

			alert(i18n.get('session_expire_in_3_minutes'));
		}
	},

	prepare: function() {
		Logger.debug('[daemon] prepare()');

		if (this.duration > 0) {
			if (this.duration > 180)
				setTimeout(this.warn_expire.bind(this), (this.duration-180)*1000);
			else
				this.warn_expire();
		}
	},

	loop: function() {
		Logger.debug('[daemon] loop()');

		this.check_status();

		if (! this.is_stopped()) {
			if (this.session_status == 'logged' && this.session_status_old != 'logged')
				this.sessionmanager_request_time = 60000;

			this.loop_timer = setTimeout(this.loop.bind(this), this.sessionmanager_request_time);
		}
	},

	break_loop: function() {
		Logger.debug('[daemon] break_loop()');

		clearTimeout(this.loop_timer);
	},

	suspend: function() {
		Logger.debug('[daemon] suspend()');

		this.req_logout('suspend');

		this.do_ended();
	},

	logout: function() {
		Logger.debug('[daemon] logout()');

		this.req_logout('logout');

		this.do_ended();
	},

	client_exit: function() {
		Logger.debug('[daemon] client_exit()');

		if (this.persistent == true) {
			Logger.info('[daemon] client_exit() - We are in a "persistent" mode, now suspending session');
			this.suspend();
		} else {
			Logger.info('[daemon] client_exit() - We are in a "non-persistent" mode, now ending session');
			this.logout();
		}
	},

	check_status: function() {
		Logger.debug('[daemon] check_status()');

		if( ! OPTION_USE_PROXY ) {
			jQuery.ajax({
					url: '/ovd/client/session_status.php?differentiator='+Math.floor(Math.random()*50000),
					type: 'GET',
					dataType: 'xml',
					success: this.parse_check_status.bind(this)
				}
			);
		} else {
			jQuery.ajax({
					url: 'proxy.php?differentiator='+Math.floor(Math.random()*50000),
					type: 'GET',
					dataType: 'xml',
					headers: {
						"X-Ovd-Service" : 'session_status'
					},
					success: this.parse_check_status.bind(this)
				}
			);
		}
	},

	parse_check_status: function(xml) {
		Logger.debug('[daemon] parse_check_status(transport@check_status())');

		var buffer = xml.getElementsByTagName('session');

		if (buffer.length != 1) {
			Logger.error('[daemon] parse_check_status(transport@check_status()) - Invalid XML (No "session" node)');
			return;
		}

		var sessionNode = buffer[0];

		try { // IE does not have hasAttribute in DOM API...
			this.session_status_old = this.session_status;
			this.session_status = sessionNode.getAttribute('status');

			if (this.session_status_old != this.session_status)
				Logger.info('[daemon] parse_check_status(transport@check_status()) - Session status is now "'+this.session_status+'"');
			else
				Logger.debug('[daemon] parse_check_status(transport@check_status()) - Session status is "'+this.session_status+'"');

			this.check_status_post();
		} catch(e) {
			Logger.error('[daemon] parse_check_status(transport@check_status()) - Invalid XML (Missing argument for "session" node)');
			Logger.debug('[daemon] parse_check_status(transport@check_status()) - Exception: '+e);
			return;
		}
	},

	check_status_post: function() {
		if (! this.is_ready()) {
			if (this.ready_lock) {
				Logger.debug('[daemon] check_status_post() - Already in "is_ready" state');
				return;
			}
			this.ready_lock = true;

			Logger.info('[daemon] check_status_post() - Now preparing session');

			this.ready = true;
		} else if (! this.is_started() && this.session_status == 'ready') {
			if (this.started_lock) {
				Logger.debug('[daemon] check_status_post() - Already in "is_started" state');
				return;
			}
			this.started_lock = true;

			Logger.info('[daemon] check_status_post() - Now starting session');

			for (var i=0; i<this.session_ready_callback.length; i++)
				this.session_ready_callback[i](this);
			
			this.start();

			this.started = true;
		} else if (this.is_stopped()) {
			if (this.stopped_lock) {
				Logger.debug('[daemon] check_status_post() - Already in "is_stopped" state');
				return;
			}
			this.stopped_lock = true;

			Logger.info('[daemon] check_status_post() - Now ending session');

			if (! this.is_started()) {
				Logger.warn('[daemon] check_status_post() - Session end is unexpected (session was never started)');
				this.error_message = i18n.get('session_close_unexpected');
			}

			this.do_ended();

			this.stopped = true;
		}
	},
	
	add_session_ready_callback: function(callback_) {
		this.session_ready_callback.push(callback_);
	},

	req_logout: function(mode_) {
		if (mode_ == 'suspend') {
			this.webapp_servers.each(function (param) {
				var ix = param[0], server = param[1];
				Logger.debug('[daemon] disconnecting from WebApps: '+ server.server_url);
				var tag = document.createElement('script'); tag.type = 'text/javascript'; tag.async = true;
				tag.src = server.server_url+'/disconnect?id='+server.id+'&user='+server.username+'&pass='+server.password;
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(tag, s);
			});
		}

		try {
			var doc = document.implementation.createDocument("", "", null);
		} catch(e) {
			var doc = new ActiveXObject("Microsoft.XMLDOM");
		}

		var node = doc.createElement("logout");
		node.setAttribute("mode", mode_);
		doc.appendChild(node);

		if( ! OPTION_USE_PROXY ) {
			jQuery.ajax({
					url: '/ovd/client/logout.php',
					type: 'POST',
					dataType: "xml",
					data: (new XMLSerializer()).serializeToString(doc),
				}
			);
		} else {
			jQuery.ajax({
					url: 'proxy.php',
					type: 'POST',
					dataType: "xml",
					headers: {
						"X-Ovd-Service" : 'logout'
					},
					data: (new XMLSerializer()).serializeToString(doc),
				}
			);
		}
	},

	start: function() {
		Logger.debug('[daemon] start()');

		if (! jQuery('#sessionContainer')[0].visible())
			jQuery('#sessionContainer').show();

		if (! jQuery('#desktopContainer')[0].visible())
			jQuery('#desktopContainer').show();

		this.do_started();
	},

	do_started: function() {
		Logger.debug('[daemon] do_started()');

		this.parse_do_started();
	},

	parse_do_started: function(transport) {
		Logger.debug('[daemon] parse_do_started(transport@do_started())');

		this.started = true;
	},

	do_ended: function() {
		window.onbeforeunload = function(e) {}

		Logger.debug('[daemon] do_ended()');

		if (jQuery('#splashContainer')[0].visible())
			jQuery('#splashContainer').hide();

		if (jQuery('#desktopContainer')[0].visible())
			jQuery('#desktopContainer').hide();
		jQuery('#desktopContainer').html('');

		if (jQuery('#sessionContainer')[0].visible())
			jQuery('#sessionContainer').hide();

		if (this.explorer) {
			if (jQuery('#fileManagerWrap')[0])
				jQuery('#fileManagerWrap').hide();
			if (jQuery('#fileManagerContainer')[0])
				jQuery('#fileManagerContainer').html('');
		}

		if (jQuery('#endContainer')[0]) {
			jQuery('#endContent').html('');

			var buf = document.createElement('span');
			buf.setAttribute('style', 'font-size: 1.1em; font-weight: bold; color: #686868;');

			var end_message = document.createElement('span');
			end_message.setAttribute('id', 'endMessage');
			buf.appendChild(end_message);

			if (this.error_message != '' && this.error_message != 'undefined') {
				var error_container = document.createElement('div');
				error_container.setAttribute('id', 'errorContainer');
				error_container.setAttribute('style', 'width: 100%; margin-top: 10px; margin-left: auto; margin-right: auto; display: none; visibility: hidden;');
				buf.appendChild(error_container);

				var error_toggle_div = document.createElement('div');

				var error_toggle_table = document.createElement('table');
				error_toggle_table.setAttribute('style', 'margin-top: 10px; margin-left: auto; margin-right: auto;');

				var error_toggle_tr = document.createElement('tr');

				var error_toggle_img_td = document.createElement('td');
				var error_toggle_img_link = document.createElement('a');
				error_toggle_img_link.setAttribute('href', 'javascript:;');
				error_toggle_img_link.setAttribute('onclick', 'toggleContent(\'errorContainer\'); return false;');
				var error_toggle_img = document.createElement('span');
				error_toggle_img.setAttribute('id', 'errorContainer_ajax');
				error_toggle_img.setAttribute('style', 'width: 9px; height: 9px;');
				error_toggle_img.innerHTML = '<img src="../media/image/show.png" width="9" height="9" alt="+" title="" />';
				error_toggle_img_link.appendChild(error_toggle_img);
				error_toggle_img_td.appendChild(error_toggle_img_link);
				error_toggle_tr.appendChild(error_toggle_img_td);

				var error_toggle_text_td = document.createElement('td');
				var error_toggle_text_link = document.createElement('a');
				error_toggle_text_link.setAttribute('href', 'javascript:;');
				error_toggle_text_link.setAttribute('onclick', 'toggleContent(\'errorContainer\'); return false;');
				var error_toggle_text = document.createElement('span');
				error_toggle_text.setAttribute('style', 'height: 16px;');
				error_toggle_text.innerHTML = i18n.get('error_details');
				error_toggle_text_link.appendChild(error_toggle_text);
				error_toggle_text_td.appendChild(error_toggle_text_link);
				error_toggle_tr.appendChild(error_toggle_text_td);

				error_toggle_table.appendChild(error_toggle_tr);
				error_toggle_div.appendChild(error_toggle_table);

				var error_content = document.createElement('div');
				error_content.setAttribute('id', 'errorContainer_content');
				error_content.setAttribute('style', 'display: none;');
				error_content.innerHTML = this.error_message;
				error_toggle_div.appendChild(error_content);

				buf.appendChild(error_toggle_div);
			}

			if (jQuery('#loginBox')[0]) {
				var close_container = document.createElement('div');
				close_container.setAttribute('style', 'margin-top: 10px;');
				var close_text = document.createElement('span');
				close_text.innerHTML = i18n.get('start_another_session');
				close_container.appendChild(close_text);
				buf.appendChild(close_container);
			}

			jQuery('#endContent').append(buf);

			jQuery('#endContent').html(jQuery('#endContent').html());

			if (this.error_message != '' && this.error_message != 'undefined')
				offContent('errorContainer');

			showEnd();
		}

		if (jQuery('#endMessage')[0]) {
			if (this.error_message != '')
				jQuery('#endMessage').html('<span class="msg_error">'+i18n.get('session_end_unexpected')+'</span>');
			else
				jQuery('#endMessage').html(i18n.get('session_end_ok'));
		}

		if (jQuery('#progressBar')[0] && jQuery('#progressBarContent')[0]) {
			jQuery('#progressBarContent').width('0');
			this.progressbar_value = 0;
		}

		this.break_loop();
		this.stopped = true;
		this.finalize();
	},
	
	parseSessionSettings: function(setting_nodes) {
		Logger.debug('[daemon] parseSessionSettings()');
		
		for (var i=0; i < setting_nodes.length; i++) {
			var name, value;
			try {
				name = setting_nodes[i].getAttribute('name');
				value = setting_nodes[i].getAttribute('value');
			} catch(e) {
				continue;
			}
			
			this.settings.set(name, value);
			
			if (name == 'persistent' && value == '1')
				this.persistent = true;
		}
	},
	
	parse_list_servers: function(xml) {
		Logger.debug('[daemon] parse_list_servers(transport@list_servers())');
		
		var sessionNode = xml.getElementsByTagName('session');
		
		if (sessionNode.length != 1) {
			Logger.error('[daemon] parse_list_servers(transport@list_servers()) - Invalid XML (No "session" node)');
			return false;
		}
		
		sessionNode = sessionNode[0];
		
		var mode_gateway = false;
		if (! Object.isUndefined(sessionNode.getAttribute("mode_gateway"))) {
			if (sessionNode.getAttribute("mode_gateway") == "on")
				mode_gateway = true;
		}
		
		var serverNodes = xml.getElementsByTagName('server');
		
		for (var i=0; i<serverNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var serverNode = serverNodes[i];
				
				var server_host = serverNodes[i].getAttribute("fqdn");
				var server_port = Server.DEFAULT_RDP_PORT;
				var server_username = serverNodes[i].getAttribute("login");
				var server_password = serverNodes[i].getAttribute("password");
				
				if (mode_gateway) {
					server_host = this.sessionmanager.host;
					server_port = this.sessionmanager.port;
				}
				else if (! Object.isUndefined(serverNodes[i].getAttribute("port")) && serverNodes[i].getAttribute("port") != null) {
					var port = parseInt(serverNodes[i].getAttribute("port"));
					if (isNaN(port)) {
						Logger.error("Invalid protocol: server port attribute is not a digit ("+serverNodes[i].getAttribute("port")+")");
						throw 'port isNaN';
					}
					
					server_port = port;
				}
				
				var server = new Server(i, i, server_host, server_port, server_username, server_password, serverNodes[i]);
				if (mode_gateway)
					server.setToken(serverNodes[i].getAttribute('token'));
				
				if (mode_gateway)
					Logger.info('[daemon] parse_list_servers(transport@list_servers()) - Adding server "'+server.id+'" to servers list');
				else
					Logger.info('[daemon] parse_list_servers(transport@list_servers()) - Adding server "'+server.fqdn+'" to servers list');
				this.servers.set(server.id, server);
				this.liaison_server_applications.set(server.id, new Array());
				
				this.parse_server_node(server, serverNodes[i]);
				
				server.add_status_changed_callback(this.on_server_status_change.bind(this));
				
			} catch(e) {
				Logger.error('[daemon] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "server" node '+i+')');
				Logger.debug('[daemon] parse_list_servers(transport@list_servers()) - Exception: '+e);
				return false;
			}
		}

		var serverNodes = xml.getElementsByTagName('webapp-server');
		
		for (var i=0; i<serverNodes.length; i++) {
			try { // IE does not have hasAttribute in DOM API...
				var serverNode = serverNodes[i];
				
				var server_base_url = serverNodes[i].getAttribute("base-url");
				var server_url = serverNodes[i].getAttribute("webapps-url");
				var server_username = serverNodes[i].getAttribute("login");
				var server_password = serverNodes[i].getAttribute("password");
				
				var server = new WebappServer(i, server_base_url, server_url, server_username, server_password);
				Logger.info('[daemon] parse_list_servers(transport@list_servers()) - Adding webapp server "'+server.base_url+'" to servers list');
				this.webapp_servers.set(server.id, server);

				this.parse_server_node(server, serverNodes[i]);
				
				server.add_status_changed_callback(this.on_server_status_change.bind(this));
				
			} catch(e) {
				Logger.error('[daemon] parse_list_servers(transport@list_servers()) - Invalid XML (Missing argument for "webapp-server" node '+i+')');
				Logger.debug('[daemon] parse_list_servers(transport@list_servers()) - Exception: '+e);
				return false;
			}
		}
		
		return true;
	},
	
	parse_server_node: function(server_, serverNode_) {},
	
	on_server_status_change: function(server_, status_) {
		if (status_ == 'disconnected') {
			this.break_loop();
			this.sessionmanager_request_time = 2000;
			this.loop();
			
			if (this.mode == 'desktop' && ! this.is_stopped()) {
				this.client_exit();
			}
		}
		else if (status_ == 'failed') {
			this.logout();
		}
	},
	
	buildAppletNode: function(mode_, params_) {
		return buildAppletNode('ulteoapplet', 'org.ulteo.ovd.applet.'+mode_, 'ulteo-applet.jar', params_);
	}
});
