Name: eipen-client
Summary: epien course management client
Version: 2.1
Release: 0
License: GPL
Group: Applications/Internet
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildArch: noarch
Provides: eipen-client
Requires: libvirt-python
Requires: python-iniparse
Packager: Patrick Connelly <patrick@deadlypenguin.com>


%description
Eipen is designed to be used as a course management with courses being
their own xen instance.


%prep
%setup -q

%build


%install
%{__rm} -rf %{buildroot}
%{__mkdir_p} %{buildroot}/usr/bin/eipen
%{__mkdir_p} %{buildroot}/etc/
%{__mkdir_p} %{buildroot}/etc/init.d/
%{__mkdir_p} %{buildroot}/etc/logrotate.d/

%{__install} -p -m 0755 init/eipen-client %{buildroot}/etc/init.d/
%{__install} -p -m 0644 rotate/eipen-client %{buildroot}/etc/logrotate.d/

%{__cp} -a bin/* %{buildroot}/usr/bin/eipen/
%{__cp} -a etc/* %{buildroot}/etc/


%post
if [ $1 = 1 ]; then
   /sbin/chkconfig --add eipen-client
fi

%clean
rm -rf %{buildroot}


%files
%doc doc/*
%config(noreplace) /etc/eipen-client.conf
/etc/init.d/eipen-client
/etc/logrotate.d/eipen-client
/usr/bin/eipen/*
/var/log/eipen*


%changelog
* Thu Feb 19 2009 Patrick Connelly <patrick@deadlypenguin.com> 2.1-0
- Changed logging

* Mon Nov 03 2008 Patrick Connelly <patrick@deadlypenguin.com> 2.0-0
- Roll out of redesigned eipen framework

* Thu Apr 10 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-3
- Fixed the formatting on the xml file inside eipen-client.py

* Mon Mar 17 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-2
- Added error handling for tracebacks

* Mon Mar 17 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-1
- Added the a function to the XMLRPC to check to see if a domain is running

* Thu Mar 06 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.5-0
- Complete rewrite of user functions.  Changed from bash scripts with the proof of concept
  to an XMLRPC gateway written in python.

* Mon Jan 21 2008 Patrick Connelly <patrick@deadlypenguin.com> 1.0-1
- Initial RPM release
