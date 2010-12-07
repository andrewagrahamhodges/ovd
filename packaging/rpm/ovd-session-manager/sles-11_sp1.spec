Name: ovd-session-manager
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Session Manager
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: SLES 11 SP1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool
Buildroot: %{buildroot}

%description
This source package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-session-manager
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Group: Applications/System
Requires: ulteo-ovd-l10n, apache2, apache2-mod_php5, php5, php5-curl, php5-dom, php5-mysql, php5-mbstring, php5-gettext, php5-pear, php5-ldap, php5-libchart, php5-imagick, curl, openssl

%description -n ulteo-ovd-session-manager
This package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

%prep -n ulteo-ovd-session-manager
%setup -q

%build -n ulteo-ovd-session-manager
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var --without-libchart
make

%install -n ulteo-ovd-session-manager
make DESTDIR=%{buildroot} install
# install the logrotate example
mkdir -p %{buildroot}/etc/logrotate.d
install -m 0644 examples/ulteo-sm.logrotate %{buildroot}/etc/logrotate.d/sessionmanager
# hack to not provide /usr/bin/php (zypper)
sed -i -e 's,^#!/usr/bin/php$,#!/usr/bin/php5,' $(find %{buildroot} -name *.php*)

%post -n ulteo-ovd-session-manager
A2CONFDIR=/etc/apache2/conf.d
CONFDIR=/etc/ulteo/sessionmanager

A2USER=wwwrun

a2enmod php5 > /dev/null

# VHost server config
if [ ! -e $A2CONFDIR/sessionmanager-vhost-server.conf ]; then
    ln -sfT $CONFDIR/apache2-vhost-server.conf \
        $A2CONFDIR/sessionmanager-vhost-server.conf
    a2enmod rewrite >/dev/null
fi

# Alias admin
if [ ! -e $A2CONFDIR/ovd-admin.conf ]; then
    ln -sfT $CONFDIR/apache2-admin.conf $A2CONFDIR/ovd-admin.conf
fi

# VHost SSL config
if [ ! -e $A2CONFDIR/sessionmanager-vhost-ssl.conf ]; then
    serverName=$(hostname -f 2>/dev/null || true)
    [ -z "$serverName" ] && serverName=$(hostname) # Bad /etc/hosts configuration
    sed -i -r "s/^( *ServerName).*$/\1 ${serverName}/" \
        $CONFDIR/apache2-vhost-ssl.conf
    ln -sfT $CONFDIR/apache2-vhost-ssl.conf \
        $A2CONFDIR/sessionmanager-vhost-ssl.conf
    a2enflag SSL > /dev/null
    a2enmod ssl > /dev/null
fi

# SSL self-signed key generation
if [ ! -f $CONFDIR/ovd.key -o ! -f $CONFDIR/ovd.csr -o ! -f $CONFDIR/ovd.crt ]
then
    echo "Auto-generate SSL configuration for Apache2 with self-signed certificate."
    openssl genrsa -out $CONFDIR/ovd.key 1024 2> /dev/null
    openssl req -new -subj /CN=$(hostname)/ -batch \
        -key $CONFDIR/ovd.key -out $CONFDIR/ovd.csr
    openssl x509 -req -days 3650 -in $CONFDIR/ovd.csr \
        -signkey $CONFDIR/ovd.key -out $CONFDIR/ovd.crt 2> /dev/null
    chown root:root $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
    chmod 600       $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
fi

# restart apache server
if apache2ctl configtest 2>/dev/null; then
    service apache2 restart || true
else
    echo << EOF
"Apache configuration error after enable OVD virtual hosts. Please remove your
old SSL configuration or be sure that the following URL are valid:
https://hostname/ovd/admin, https://hostname/ovd/client.
If you don't change anything, you won't start OVD sessions."
EOF
fi

# link crons
chmod a+x $CONFDIR/sessionmanager.cron
sed -i "s/@APACHE_USER@/${A2USER}/" $CONFDIR/sessionmanager.cron
ln -sfT $CONFDIR/sessionmanager.cron /etc/cron.d/sessionmanager

%postun -n ulteo-ovd-session-manager
if [ "$1" = "0" ]; then
    A2CONFDIR=/etc/apache2/conf.d
    CONFDIR=/etc/ulteo/sessionmanager
    rm -f $A2CONFDIR/sessionmanager-vhost-server.conf \
          $A2CONFDIR/sessionmanager-vhost-ssl.conf \
          $A2CONFDIR/ovd-admin.conf
    rm -f $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
    rm -f /etc/cron.hourly/sessionmanager
    rm -rf /var/spool/ulteo/sessionmanager/* \
           /var/cache/ulteo/sessionmanager/* \
           /var/log/ulteo/sessionmanager/*

    if apache2ctl configtest 2>/dev/null; then
        service apache2 restart || true
    else
        echo "Apache configuration broken: correct the issue and restart the apache2 server"
    fi
fi

%clean -n ulteo-ovd-session-manager
rm -rf %{buildroot}

%files -n ulteo-ovd-session-manager
%defattr(-,root,root)
/usr/*
%config /etc/ulteo/sessionmanager/*.conf
%config /etc/ulteo/sessionmanager/sessionmanager.cron
%config /etc/logrotate.d/sessionmanager
%defattr(0660,wwwrun,www)
%config /etc/ulteo/sessionmanager/config.inc.php
%defattr(2770,wwwrun,www)
/var/*

%changelog -n ulteo-ovd-session-manager
* Thu Sep 02 2010 Samuel Bovée <samuel@ulteo.com> 3.0+svn04389-1
- Initial release
