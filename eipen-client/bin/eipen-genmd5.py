#!/usr/bin/python
"""Creates the md5entry for a disk image in the md5file as specified
in the /etc/eipen-client.conf file
"""

__author__ = "Patrick Connelly <patrick@deadlypenguin.com>"

__version__ = "1.5"

import cPickle
import os
import sys
import ConfigParser
import md5

config = ConfigParser.ConfigParser()
config.read('/etc/eipen-client.conf')

md5file = config.get("main","md5file")

images_dir = config.get("main","images_dir")

if not len(sys.argv) == 2:
        print "Usage eipen-genmd5.py diskname"
        sys.exit(1)

diskname = sys.argv[1]

newmd5list = False

# open and load the pickle file
try:
        FILE = file(md5file, 'r')
        md5list = cPickle.load(FILE)
        FILE.close()
except:
        print "md5file does not exist.  Creating"
        newmd5list = True

if newmd5list == True:
        md5list = []

fullimage_path = images_dir+"/"+diskname

if not os.access(fullimage_path, os.R_OK):
        print "Unable to access diskimage \""+fullimage_path+"\""
        sys.exit(1)

try:
        FILE = file(fullimage_path, 'rb')
except:
        print "Failed to open file \""+fullimage_path+"\""
        sys.exit(1)

# create the md5sum
m = md5.new()

while True:
        d = FILE.read(8096)
        if not d:
                break
        m.update(d)

FILE.close()

digest = m.hexdigest()

md5list.append([diskname, m.hexdigest()])

# open and write the pickle file
FILE = open(md5file, 'w')
cPickle.dump(md5list, FILE)
FILE.close()
