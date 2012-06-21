Name: eipen-server
Summary: epien course management server
Version: 2.1
Release: 0
License: GPL
Group: Applications/Internet
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildArch: noarch
Provides: eipen-server
Requires: mysql-server
Requires: mysql
Requires: httpd
Requires: php
Requires: php-mysql
Requires: php-ldap
Requires: php-pear-Image-Graph
Requires: php-gd
Requires: perl
Requires: perl-Config-IniFiles
Requires: perl-SOAP-Lite
Requires: perl-Net-Telnet
Requires: python
Requires: python-pexpect
Requires: python-iniparse
Requires: MySQL-python
Packager: Patrick Connelly <patrick@deadlypenguin.com>


%description
Eipen is designed to be used as a course management with courses being
their own xen instance or baremetal machine.


%prep
%setup -q


%build


%install
%{__rm} -rf %{buildroot}
%{__mkdir_p} %{buildroot}/usr/bin/eipen
%{__mkdir_p} %{buildroot}/etc/init.d/
%{__mkdir_p} %{buildroot}/etc/eipen/
%{__mkdir_p} %{buildroot}/etc/logrotate.d/
%{__mkdir_p} %{buildroot}/var/log/eipen/
%{__mkdir_p} %{buildroot}/var/www/html/eipen/

%{__install} -p -m 0755 init/eipen-server %{buildroot}/etc/init.d/
%{__install} -p -m 0644 rotate/eipen-server %{buildroot}/etc/logrotate.d/

%{__cp} -a src/* %{buildroot}/var/www/html/eipen/
%{__cp} -a bin/* %{buildroot}/usr/bin/eipen/
%{__cp} -a etc/eipen/* %{buildroot}/etc/eipen/


%post
if [ $1 = 1 ]; then
   /sbin/chkconfig --add eipen-server
fi


%clean
rm -rf %{buildroot}


%files
%doc doc/*
%config(noreplace) /etc/eipen/eipen-server.conf
%config(noreplace) /etc/eipen/baremetal
%config(noreplace) /etc/eipen/error
%config(noreplace) /etc/eipen/newmachine
/usr/bin/eipen/*
/var/www/html/eipen/*
/var/log/eipen/*
/etc/eipen/*
/etc/init.d/eipen-server
/etc/logrotate.d/eipen-server


%changelog
* Thu Feb 19 2009 Patrick Connelly <patrick@deadlypenguin.com> 2.1-0
- Rewrote eipend.pl into eipend.py and eipend-xmlrpc.py
- Added XMLRPC interface
- Added some stats output
- Added "Eipen Deployment Guide"

* Mon Nov 03 2008 Patrick Connelly <patrick@deadlypenguin.com> 2.0-0
- Roll out of redesigned eipen framework.

* Mon Mar 17 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-3
- Fixed formatting issue with the XML file, and with the admin interface.
- Added checking to see if eipen-client is up before trying to start machine

* Mon Mar 17 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-2
- Fixed an error with emailing and sleeping while waiting for a guest

* Mon Mar 17 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-1
- Added support for eipen-client addition to see if a client is currently running

* Thu Mar 06 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-0 
- Rewrite of backend functionallity to use XMLRPC in conjuction with client side change

* Mon Jan 21 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.0-1
- Initial RPM release
