#!/usr/bin/python

"""Creates the eipen-server interface

This creates an XMLRPC gateway with the following functions.  These are only
available to one of the eipen-clients as set in the database set in the 
/etc/eipen/eipen-server.conf file.

getCourseData(courseName)
    courseName

getStudentName(ipAddr)
    studentName

"""

__author__ = "Patrick Connelly <patrick@deadlypenguin.com>"

__version__ = "2.1"

from daemon import Daemon
from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler
import xmlrpclib
import SocketServer
import shutil
import os
import sys
import ConfigParser
import re
import signal
import time
import MySQLdb
import logging


# Get the configuration information the config file
config = ConfigParser.ConfigParser()
config.read('/etc/eipen/eipen-server.conf')

server_port = (int)(config.get("main","server_port"))
client_port = (int)(config.get("main", "client_port"))

log_level = config.get("logging", "log_level")

database_db = config.get("database", "db")
database_host = config.get("database", "host")
database_user_id = config.get("database", "user_id")
database_db_password = config.get("database", "db_password")

try:
    # Set up the logging, and set the log level
    logging.basicConfig(level=logging.INFO,
                    format='%(asctime)s %(levelname)s %(message)s',
                    filename='/var/log/eipen/xmlrpc.log',
                    filemode='a')
    log = logging.getLogger()

    if log_level == "DEBUG":
        log.setLevel(logging.DEBUG)
    elif log_level == "INFO":
        log.setLevel(logging.INFO)
    elif log_level == "WARNING":
        log.setLevel(logging.WARNING)
    elif log_level == "ERROR":
        log.setLevel(logging.ERROR)
    elif log_level == "CRITICAL":
        log.setLevel(logging.CRITICAL)

except:
    print "An error occurred setting up logging '"+str(e)+"'"
    sys.exit(1)

def isValidIpAddr(ipAddr):
    regex = re.compile("^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$")
    match = regex.match(ipAddr)

    if not match:
        return False

    return True

def isValidCourseName(courseName):
    regex = re.compile("^\w*$")
    match = regex.match(courseName)

    if not match:
        return False

    return True

def runQuery(query):
    """Runs the supplied MySQL query
        query - The query to run
    """

    try:
        db = MySQLdb.connect(host=database_host, user=database_user_id, 
                                passwd=database_db_password, db=database_db)
        cursor = db.cursor()
        cursor.execute(query)
        result = cursor.fetchall()
        cursor.close()
        db.close()
    except MySQLdb.Error, e:
        errMsg =  "Error occured with the database. query of '"+query
        errMsg += "' with error '"+str(e)+"'"
        logging.warning(errMsg)
        return None

    return result

def getCourseXML(courseid):
    """Gets the XML file that discribes a course.  Including it's associated
        images.

        courseid - The courseid in the database
    """

    logging.debug("Getting XML file for courseid '"+str(courseid)+"'")

    query = """SELECT name, enabled, coursedesc, xen, length, samemachine, 
                memory, fullvirt, profileid FROM courses WHERE 
                courseid = """ + str(courseid)

    result = runQuery(query)

    if result == None:
        logging.debug("No results returned for courseid '"+str(courseid)+"'")
        return None

    row = result[0]
    name = row[0]
    enabled = row[1]
    coursedesc = row[2]
    xen = row[3]
    length = row[4]
    samemachine = row[5]
    memory = row[6]
    fullvirt = row[7]
    profileid = row[8]

    xml = """<course name=\""""+str(name)+"""\">
    <info>
        <enabled>"""+str(enabled)+"""</enabled>
        <coursedesc>"""+str(coursedesc)+"""</coursedesc>
        <xen>"""+str(xen)+"""</xen>
        <length>"""+str(length)+"""</length>
        <samemachine>"""+str(samemachine)+"""</samemachine>
        <memory>"""+str(memory)+"""</memory>
        <fullvirt>"""+str(fullvirt)+"""</fullvirt>
        <profileid>"""+str(profileid)+"""</profileid>
    </info>\n"""

    query = """SELECT ci.name AS pair_name, i.name AS name, 
                t.typename AS type, i.fullvirt AS fullvirt, 
                i.memory AS memory, i.image AS image FROM course_images ci,
                images i, image_type t WHERE ci.imageid = i.imageid AND
                i.typeid = t.typeid AND ci.courseid = """ + str(courseid)

    result = runQuery(query)

    if result == None:
        errMsg = "No images found for courseid '"+str(courseid)+"'"
        logging.debug(errMsg)
        return None

    for row in result:
        pair_name = row[0]
        name = row[1]
        type = row[2]
        fullvirt = row[3]
        memory = row[4]
        image = row[5]

        xml += """    <image_pair name=\""""+pair_name+"""\">
        <type>"""+str(type)+"""</type>
        <name>"""+str(name)+"""</name>
        <fullvirt>"""+str(fullvirt)+"""</fullvirt>
        <memory>"""+str(memory)+"""</memory>
        <image>"""+str(image)+"""</image>
    </image_pair>\n"""


    xml += """</course>"""

    return xml

class ForkingXMLRPCServer (SocketServer.ForkingMixIn, SimpleXMLRPCServer):
    pass

class EipenHandler(SimpleXMLRPCRequestHandler):
    def _dispatch(self, method, params):
        """Checks to see if the requested method is known to the server
        """

        try:
            func = getattr(self, 'export_' + method)
        except AttributeError:
            raise Exception('Method "%s" is not supported' % method)
        return apply(func, params)

    def log_message(self, format, *args):
        pass

    def export_getCourseXML(self, courseName):
        """Exports the course data
            courseName - The name of the course in the database eg (FOO101)
        """

        logging.debug("Getting courseXML for '"+courseName+"'")

        if not isValidCourseName(courseName):
            logging.debug("Invalid courseName")
            return "An error has occurred"

        query =  "SELECT courseid FROM courses WHERE name = \""
        query += str(courseName)+"\""

        result = runQuery(query)

        if result == None:
            return "An error has occurred"

        if len(result) != 1:
            errMsg = "Got "+str(len(result))+" rows.  Expected 1"
            logging.debug(errMsg)
            return "An error has occurred"

        courseid = result[0][0]

        xml = getCourseXML(courseid)

        if xml == None:
            return "An error has occurred"
        
        return xml

    def export_getStudentName(self, ipAddr):
        """Gets the student name for a given ipAddr
            ipAddr - The ip address of the guest
        """

        logging.debug("Getting student name for '"+ipAddr+"'")

        if not isValidIpAddr(ipaddr):
            logging.debug("Invalid ipAddr")
            return "An error has occurred"
            

        query = """SELECT rv.userid AS userid FROM running_vms rv, macaddr m 
                    WHERE m.ipaddr = '"""+ipAddr+"""' AND 
                    m.macid = rv.macid LIMIT 1"""

        result = runQuery(query)

        if result == None:
            logging.debug("No rows returned for '"+ipAddr+"'")
            return "An error has occurred"

        return result[0][0]

class EipenXMLRPC (Daemon):
    def run(self):
        logging.debug("Starting XMLRPC Server")
        try:
            server = ForkingXMLRPCServer(('', server_port), EipenHandler)
            server.socket.settimeout(1.0)
            server.serve_forever()
        except Exception, e:
            logging.error("An error has occurred '"+str(e)+"'")
            sys.exit(1)

if __name__ == "__main__":
    xmlrpc = EipenXMLRPC('/var/run/eipen-server-xmlrpc.pid',
                            stdout='/var/log/eipen/xmlrpc.log',
                            stderr='/var/log/eipen/xmlrpc.log')
    if len(sys.argv) == 2:
        if 'start' == sys.argv[1]:
            xmlrpc.start()
        elif 'stop' == sys.argv[1]:
            xmlrpc.stop()
        elif 'restart' == sys.argv[1]:
            xmlrpc.restart()
    else:
        print "Unknown command"
        sys.exit(2)
    sys.exit(0)
else:
    print "usage: %s start|stop|restart" % sys.argv[0]
    sys.exit(2)
