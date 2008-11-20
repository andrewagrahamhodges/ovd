#! /usr/bin/env python
# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com>
#
# This program is free software; you can redistribute it and/or 
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

import commands
import ConfigParser
import sys, os
from xml.dom import minidom

class Application(dict):
    def __init__(self, filename):
        self.desktop_keys = {'Name' : ['Name[en]','Name'], 
                             'Exec' : ['Exec'],
                             'Icon' : ['Icon'],
                             'Categories' : ['Categories'],
                             'Comment' : ['Comment','GenericName[en]', 'GenericName']
                             }
        self.filename = filename

    def toXmlNode(self):
        doc = minidom.Document()
        node_app = doc.createElement("application")
        node_app.setAttribute("name", self["Name"])
        node_app.setAttribute("description", self["Comment"])
        node_app.setAttribute("package", self["Package"])
        node_app.setAttribute("desktopfile", self.filename)
        
        node_exe = doc.createElement("executable")
        node_exe.setAttribute("icon", self["Icon"])
        node_exe.setAttribute("command", self["Exec"])
 
        node_app.appendChild(node_exe)
        return node_app


    def findDebianPackage(self):
        status,out = commands.getstatusoutput('dpkg -S "%s"|cut -d: -f1'%(self.filename))
        if not status == 0:
            self['Package'] = ""
            return False
        
        self['Package'] = out

    def parseDesktopFile(self):
        parser = ConfigParser.ConfigParser()
        try:
            parser.read(self.filename)
        except ConfigParser.MissingSectionHeaderError:
            return False

        if not parser.has_section('Desktop Entry'):
            return False

        if not parser.has_option('Desktop Entry', 'Type'):
            return False
        if not parser.get('Desktop Entry','Type') == "Application":
            print>> sys.stderr, "don't have Type=Application"
            return False

        for name,values in self.desktop_keys.items():
            flag = False
            for alternative in values:
                if parser.has_option('Desktop Entry', alternative):
                    obj[name] = parser.get('Desktop Entry', alternative)
                    flag = True
                    break
            if not flag:
                print>> sys.stderr, filename,"don't have keys",values
                return False

        return True


def findDesktopFiles(directory):
    status,out = commands.getstatusoutput('find %s -name "*.desktop"'%(directory))
    if not status == 0:
        return []

    return out.splitlines()

def NonApplicationFilter(obj):
    return not ('settings' in obj['Categories'].lower() or 'Peripherals' in obj['Categories'] or
            'Utility' in obj['Categories'] or 'System' in obj['Categories'] or
            'information' in obj['Categories'])

def hardcodedExceptions(obj):
    return (obj["Package"].startswith('python') or
            obj["Package"].startswith('sun-java'))

def usage():
    print>> sys.stderr, "Usage: %s directory"%(sys.argv[0])
    print>> sys.stderr, "\tdirectory: a path whare find .desktop files"

if len(sys.argv) < 2:
    search_path = "/usr/share/applications"
else:
    search_path = sys.argv[1]

if not os.path.isdir(search_path):
    print>> sys.stderr, "No such directory '%s'"%(search_path)
    print>> sys.stderr, ""
    usage()
    sys.exit(1)


desktop_files = findDesktopFiles(search_path)
applications = []
for filename in desktop_files:
    obj = Application(filename)
    if obj.parseDesktopFile() and NonApplicationFilter(obj):
        obj.findDebianPackage()
        if hardcodedExceptions(obj):
            continue

        applications.append(obj)

doc = minidom.Document()
node_apps = doc.createElement("applications")
for application in applications:
    node_apps.appendChild(application.toXmlNode())

print node_apps.toxml()
