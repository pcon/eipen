#!/usr/bin/python
"""Removes the md5sum from the md5file that is specified in the
/etc/eipen-client.conf file
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

if not len(sys.argv) == 2:
        print "Usage eipen-removemd5.py diskname"
        sys.exit(1)

diskname = sys.argv[1]

newmd5list = False

# open and load the pickle file
try:
        FILE = file(md5file, 'r')
        md5list = cPickle.load(FILE)
        FILE.close()
except:
        print "md5file does not exist.  Aborting"
        sys.exit(1)

# find the disk name in the pickle file
i = 0
isInList = False
for imageset in md5list:
        if diskname in imageset:
                isInList = True
                break
        i = i + 1

if isInList == True:
        md5list.pop(i)
else:
        print diskname+" not found in "+md5file+" - Aborting"
        sys.exit(1)

# open and write to the pickle file
FILE = open(md5file, 'w')
cPickle.dump(md5list, FILE)
FILE.close()
