#!/usr/bin/python

"""Creates the eipen-server daemon

This creates the backend of the eipen-server that cleans up when the courses
are completed

"""

__author__ = "Patrick Connelly <patrick@deadlypenguin.com>"

__version__ = "2.1"

from daemon import Daemon
import xmlrpclib
import shutil
import os
import sys
import ConfigParser
import re
import signal
import time
import pxssh
import MySQLdb
import logging


# Get the configuration information the config file
config = ConfigParser.ConfigParser()
config.read('/etc/eipen/eipen-server.conf')

server_port = (int)(config.get("main","server_port"))
client_port = (int)(config.get("main", "client_port"))

root_password = config.get("guest", "root_password")

log_file = config.get("logging", "log_file")
log_level = config.get("logging", "log_level")

database_db = config.get("database", "db")
database_host = config.get("database", "host")
database_user_id = config.get("database", "user_id")
database_db_password = config.get("database", "db_password")

cobbler_server = config.get("cobbler", "server_name")
cobbler_username = config.get("cobbler", "user_name")
cobbler_password = config.get("cobbler", "password")
cobbler_default_profile = config.get("cobbler", "default_profile")


try:
    # Set up the logging, and set the log level
    logging.basicConfig(level=logging.INFO,
                    format='%(asctime)s %(levelname)s %(message)s',
                    filename=log_file,
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

except Exception, e:
    print "An error occurred setting up logging '"+str(e)+"'"
    sys.exit(1)

def wallssh(ipaddr, message):
    """Sends a wall message over ssh
        ipaddr - The destination of the wall message
        message - The message to send
    """

    try:
        shell = pxssh.pxssh()
        shell.login (ipaddr, "root", root_password)
        shell.prompt()
        shell.sendline('/usr/bin/wall "'+message+'"')
        shell.prompt()
        shell.logout()
    except pxssh.ExceptionPxssh, e:
        errMsg = "Failed to login to '"+ipaddr+"' with error '"+str(e)+"'"
        logging.warning(errMsg)

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

def warnVirtMachine(minutes):
    """Sends a wall message to the virt guest to alert them when the session expires
        minutes - The number of minutes left until the session expires
    """

    startOffset = 60 * minutes
    warningNum = int(minutes / 5)

    query ="""SELECT m.ipaddr AS guestip, rc.sessionid AS sessionid FROM 
                running_vms s, macaddr m, running_courses rc WHERE 
                m.macid = s.macid AND rc.sessionid = s.sessionid AND 
                unix_timestamp(s.end) < (unix_timestamp(now()) + """
    query +=    str(startOffset)+""")
                AND rc.warning > """+str(warningNum)

    result = runQuery(query)

    if result == None:
        logging.error("An error has occurred with "+minute+" virt warning")
        return None

    for row in result:
        guestip = row[0]
        sessionid = row[1]

        logging.info("Sending "+str(minutes)+" warning for "+guestip)
        msg = "You have "+str(minutes)+" until this session is destroyed"
        wallssh(guestip, msg)

        query =  "UPDATE running_courses SET warning = "+str(warningNum)+" "
        query += "WHERE sessionid = "+str(sessionid)

        result2 = runQuery(query)
        if result2 == None:
            errMsg =  "Update failed for session '"+str(sessionid)+"' at host "
            errMsg += "'"+guestip+"'"
            logging.warning(errMsg)
        else:
            errMsg =  "Updated warning for '"+str(sessionid)+"' at host "
            errMsg += "'"+guestip+"'"
            logging.debug(errMsg)

def killVirtMachine():
    """Kill all the virtual machines that have expired
    """

    query = """SELECT sessionid, course_imageid, xenname, s.macid AS macid, 
                h.ipaddr AS hostip FROM running_vms s, host h, macaddr m WHERE 
                h.hostid = s.hostid AND s.macid = m.macid AND 
                unix_timestamp(end) < unix_timestamp(now())"""

    result = runQuery(query)

    if result == None:
        logging.error("killVirtMachine: An error has occurred the MySql call")
        return None

    for row in result:
        sessionid = row[0]
        course_imageid = row[1]
        xenName = row[2]
        macid = row[3]
        hostip = row[4]

        query = "UPDATE macaddr SET status = 0 WHERE macid = "+str(macid)
        runQuery(query)

        query = """DELETE FROM running_vms WHERE sessionid = """
        query +=    str(sessionid)+""" AND course_imageid = """
        query +=    str(course_imageid)
        runQuery(query)

        logging.info("Killing "+xenName+" on "+hostip)

        try:
            serverURI = "http://"+hostip+":"+str(client_port)
            xmlrpcServer = xmlrpclib.ServerProxy(serverURI)
            result = xmlrpcServer.destroyMachine(xenName)

            if result == True:
                logging.debug("Kill successful for "+xenName)
            else:
                logging.error("Kill unsuccessful for "+xenName)
        except Exception, e:
            logging.error("killVirtMachine: An error has occurred '"+str(e)+"'")

def warnBareMetalMachine(minutes):
    """Sends a wall message to the baremetal guest to alert them when the 
        session expires

        minutes - The number of minutes left until the session expires
    """

    startOffset = 60 * minutes
    warningNum = int(minutes / 5)

    query = """SELECT h.ipaddr AS hostip, rc.sessionid AS sessionid FROM
                running_baremetal s, host h, running_courses rc WHERE 
                h.hostid = s.hostid AND rc.sessionid = s.sessionid AND 
                unix_timestamp(s.end) < (unix_timestamp(now()) + """
    query +=    str(startOffset)+""")
                AND rc.warning > """+str(warningNum)

    result = runQuery(query)

    if result == None:
        errMsg = "An error has occurred with "+str(minute)+" baremetal warning"
        logging.error(errMsg)
        return None

    for row in result:
        hostip = row[0]
        sessionid = row[1]

        logging.info("Sending "+str(minutes)+" warning for "+hostip)
        msg = "You have "+str(minutes)+" until this session is destroyed"
        wallssh(guestip, msg)

        query =  "UPDATE running_courses SET warning = "+str(warningNum)+" "
        query += "WHERE sessionid = "+str(sessionid)

        result2 = runQuery(query)
        if result2 == None:
            errMsg =  "Update failed for session '"+str(sessionid)+"' at host "
            errMsg += "'"+hostip+"'"
            logging.warning(errMsg)
        else:
            errMsg =  "Updated warning for '"+str(sessionid)+"' at host "
            errMsg += "'"+guestip+"'"
            logging.debug(errMsg)

def killBareMetalMachine ():
    """Kills all baremetal machines that have expired
    """

    query = """SELECT h.ipaddr AS hostip, h.hostid AS hostid FROM 
                running_baremetal s, host h WHERE h.hostid = s.hostid AND 
                unix_timestamp(end) < unix_timestamp(now())"""

    result = runQuery(query)
    if result == None:
        logging.error("An error has occurred in killBareMetalMachine")
        return None

    for row in result:
        hostip = row[0]
        hostid = row[1]

        logging.info("Killing "+hostip)
        serverURI = "http://"+cobbler_server+"/cobbler_api_rw"

        try:
            xmlrpcServer = xmlrpclib.ServerProxy(serverURI)

            creds = xmlrpcServer.login(cobbler_username, cobbler_password)
            system = xmlrpcServer.new_system(creds)
        
            xmlrpcServer.modify_system(system, "name", hostip, creds)
            xmlrpcServer.modify_system(system, "profile", cobbler_default_profile,
                                        creds)
            xmlrpcServer.save_system(system, creds)
        except Exception, e:
            errMsg = "An error has occured contacting the Cobbler server '"
            errMsg += str(e)+"'"
            logging.error(errMsg)
            return None

        query = "SELECT fence_command FROM host WHERE ipaddr = '"+hostip+"'"
        result2 = runQuery(query)

        if result2 == None:
            errMsg = "No fence_command found for '"+hostip+"'"
            logging.error(errMsg)
    
        for row2 in result2:
            fence_command = row2[0]
            os.system(fence_command)
            errMsg =  "Ran fence command '"+fence_command+"'"
            errMsg += "for '"+str(hostid)+"'"
            logging.debug(errMsg)

        query = """INSERT INTO running_profiles (sessionid, hostid, remove) 
                    VALUES (-1, """+str(hostid)+""", 
                    from_unixtime(unix_timestamp(now()) + (60 * 5)))"""
        runQuery(query)

    query = """DELETE FROM running_baremetal WHERE 
                unix_timestamp(end) < unix_timestamp(now())"""
    runQuery(query)

def cleanupCourses():
    query = """DELETE from running_courses WHERE 
                unix_timestamp(end) < unix_timestamp(now())"""
    runQuery(query)
        
def removeUsedProfiles():
    """Removes the profiles from cobbler after a system has had enough time to
        boot
    """

    query = """SELECT h.ipaddr AS hostip, rp.sessionid AS sessionid FROM 
                running_profiles rp, host h WHERE h.hostid = rp.hostid AND 
                unix_timestamp(rp.remove) < unix_timestamp(now())"""

    result = runQuery(query)

    if result == None:
        errMsg = "An error occurred while retreving used profiles"
        logging.error(errMsg)
        return None

    for row in result:
        hostip = row[0]
        sessionid = row[1]

        query = """UPDATE running_courses SET warning = 3 WHERE
                    sessionid = """+str(sessionid)
        runQuery(query)

        serverURI = "http://"+cobbler_server+"/cobbler_api_rw"
        try:
            xmlrpcServer = xmlrpclib.ServerProxy(serverURI)

            creds = xmlrpcServer.login(cobbler_username, cobbler_password)
            system = xmlrpcServer.remove_system(hostip,creds)

        except Exception, e:
            errMsg = "An error has occured contacting the Cobbler server '"
            errMsg += str(e)+"'"
            logging.error(errMsg)
            return None

    cleanupCourses()

def warnMachine (minutes):
    warnVirtMachine(minutes)
    warnBareMetalMachine(minutes)

def killMachine():
    query = """INSERT INTO complete_courses (userid, courseid, start, end, 
                extend) (SELECT userid, courseid, start, end, extended FROM 
                running_courses WHERE 
                unix_timestamp(end) < unix_timestamp(now()))"""
    runQuery(query)

    killVirtMachine()
    killBareMetalMachine()
    cleanupCourses()

class EipenDaemon (Daemon):
    def run(self):

        logging.debug("Starting eipend loop")
        while True:
            # Check for to see if a ks_profile has been used.
            # If so, delete it
            removeUsedProfiles()

            # Give 10 minute warning
            warnMachine(10)

            # Give 5 minute warning
            warnMachine(5)

            # Kill EET!!
            killMachine()

            # Try again in a minute
            time.sleep(60)

if __name__ == "__main__":
    daemon = EipenDaemon('/var/run/eipen-server.pid',
                            stdout='/var/log/eipen/error_log',
                            stderr='/var/log/eipen/error_log')
    if len(sys.argv) == 2:
        if 'start' == sys.argv[1]:
            daemon.start()
        elif 'stop' == sys.argv[1]:
            daemon.stop()
        elif 'restart' == sys.argv[1]:
            daemon.restart()
    else:
        print "Unknown command"
        sys.exit(2)
    sys.exit(0)
else:
    print "usage: %s start|stop|restart" % sys.argv[0]
    sys.exit(2)
