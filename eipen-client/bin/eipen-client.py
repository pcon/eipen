#!/usr/bin/python

"""Creates the eipen-client interface

This creates an XMLRPC gateway with the following functions.  These are only available
to the 'eipen_server' as set in the /etc/eipen-client.conf file.

startMachine(name, disk, macaddr, memory, vmtype)
   name    - The name of the virt guest to create
   disk    - The disk image on the shared storage to use
   macaddr - The macaddr of the virt guest
   memory  - How much memory to allocate
   vmtype  - Type of machine (linuxparavirt, linuxfullvirt, solarisparavirt,
             solarisfullvirt, windows)

destroyMachine(name)
   name    - The name of the virt guest to destroy

saveMachine(name, disk)
   name    - The name of the virt guest to save
   disk    - The disk image to save the guest on the shared storage

"""

__author__ = "Patrick Connelly <patrick@deadlypenguin.com>"

__version__ = "2.1"

from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler
from daemon import Daemon
import SocketServer
import libvirt
import shutil
import os
import sys
import ConfigParser
import re
import signal
import time
import cPickle
import md5
import logging

config = ConfigParser.ConfigParser()
config.read('/etc/eipen-client.conf')

eipen_server = config.get("main","eipen_server")
client_port = (int)(config.get("main","client_port"))

images_dir = config.get("main","images_dir")
max_mem = (int)(config.get("main","max_mem")) * 1024

check_md5 = config.get("main","check_md5")
md5file = config.get("main","md5file")

log_file = config.get("logging","log_file")
log_level = config.get("logging", "log_level")

running_images_dir = config.get("main","running_images_dir")

solaris_kernel = config.get("solaris","kernel")
solaris_initrd = config.get("solaris","initrd")

try:
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

def isValidMacaddr(macaddr):
    result = re.match("^[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}:[0-9A-Fa-f]{2}$", macaddr)
    if result == None:
        return False
    return True

class ForkingXMLRPCServer (SocketServer.ForkingMixIn, SimpleXMLRPCServer):
    pass

class EipenHandler(SimpleXMLRPCRequestHandler):
    def _dispatch(self, method, params):
        """Checks to see if the requested method is known to the server,
            and checks to see if the connecting client is the 
            eipen_server as set in the /etc/eipen-client.conf
        """

        connecting_addr = self.address_string()
        if connecting_addr == eipen_server:
            try:
                func = getattr(self, 'export_' + method)
            except AttributeError:
                raise Exception('Method "%s" is not supported' % method)
            return apply(func, params)
        else:
            logging.error(connecting_addr+" - Invalid hostname")
        return False

    def log_message(self, format, *args):
        pass

    def checkMD5(self,orig,new):
        """Checks the md5sum of the newly copied file to verify that the
            copy was successful. This only runs if check_md5 is 
            set to "True"
        
            orig - The original disk image
            new  - The new disk image
        """

        errMsg = "comparing md5sum for \""+new+"\" against \""+orig+"\""
        logging.debug(errMsg)

        try:
            FILE = open(md5file, 'r')
            md5list = cPickle.load(FILE)
            FILE.close()
        except:
            logging.error("Failed to open \""+md5file+"\"")
            return -1

        foundmd5 = False

        for imageset in md5list:
            if orig in imageset:
                sum = imageset[1]
                foundmd5 = True
                break        
                
        if foundmd5 == False:
            logging.error("Did not find disk in md5file")
            return -1

        try:
            FILE = file(new, 'rb')
        except:
            logging.error("Failed to open \""+new+"\"")
            return -1

        m = md5.new()

        while True:
            d = FILE.read(8096)
            if not d:
                break
            m.update(d)

        FILE.close()

        digest = m.hexdigest()

        if not digest == sum:
            errMsg =  new+" - Failed md5sum\n Got: "+digest
            errMsg += "\nExpected: "+sum
            logging.error(errMsg)
            return 1

        return 0

    def export_startMachine(self, name, disk, macaddr, memory, vmtype):
        """Starts the guest
        
            name    - The name of the guest
            disk    - The disk image on the shared storage
            macaddr - The macaddr of the guest
            memory  - The amount of memory to allocate to the guest
            vmtype  - The type of image to create
        """

        full_image_path = images_dir+"/"+disk
        new_image_path = running_images_dir+"/"+name+".disk"

        memory = memory * 1024

        logging.debug(name+" - Starting machine")

        # Check to see if the disk image is readable
        if not os.access(full_image_path, os.R_OK):
            logging.error(name+" - Invalid disk image \""+full_image_path+"\"")
            return False 

        # Check to see if the macaddr is valid
        if isValidMacaddr(macaddr) == False:
            logging.error(name+" - Invalid macaddr "+macaddr)
            return False

        # Check to see if the memory amount is valid
        if memory < 0 or memory > max_mem:
            logging.error(name+" - Invalid memory "+`memory`)
            return False

        # Make sure we're not going to overwrite an existing image
        if os.access(new_image_path, os.R_OK):
            errMsg = name+" - New image already exists \""+new_image_path+"\""
            logging.error(errMsg)
            return False

        kernel=open("/proc/version").read().split()
        kernelOv = kernel[2]

        if kernelOv.find('hypervisor'):
            kernelU = "/boot/vmlinuz-" + kernelOv.replace('hypervisor', 'guest')
            initrdU = "/boot/initrd-" + kernelOv.replace('hypervisor', 'guest') 
            initrdU += ".img"
        elif kernelOv.find('xen0'):
            kernelU = "/boot/vmlinuz-" + kernelOv.replace('xen0', 'xenU')
            initrdU = "/boot/initrd-" + kernelOv.replace('xen0', 'xenU')
            initrdU += ".img"

        if not os.access(kernelU, os.R_OK):
            logging.error(name+" - Did not found the guest kernel "+kernelU)
            return False

        kernelU = "<kernel>" + kernelU + "</kernel>"

        if not os.access(initrdU, os.R_OK):
            logging.error(name+" - Did not found the guest initrd "+initrdU)
            initrdU = ""
        else:
            initrdU = "<initrd>" + initrdU + "</initrd>"

        conn = libvirt.open(None)
        if conn == None:
            logging.error(name+" - Failed to open connection to the hypervisor")
            return False

		# create config file depending on vmtype
		# linuxparavirt, linuxfullvirt, solarisparavirt have been tested.
        if vmtype == "linuxparavirt":
            newxmldesc="""<domain type='xen'>
  <name>""" + name + """</name>
  <bootloader>/usr/bin/pygrub</bootloader>
  <os>
    <type>linux</type>
""" + kernelU + initrdU + """
    <cmdline>ro root=/dev/VolGroup00/LogVol00 console=xvc0</cmdline>
  </os>
  <memory>""" + `memory` + """</memory>
  <vcpu>1</vcpu>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>restart</on_crash>
  <devices>
    <interface type='bridge'>
      <source bridge='xenbr0'/>
      <mac address='""" + macaddr + """'/>
      <script path='vif-bridge'/>
    </interface>
    <disk type='file' device='disk'>
      <driver name='tap' type='aio'/>
      <source file='""" + new_image_path + """'/>
      <target dev='xvda'/>
    </disk>
    <console tty='/dev/pts/4'/>
  </devices>
</domain>
"""
        elif vmtype == "linuxfullvirt":
            newxmldesc="""<domain type='xen'>
  <name>""" + name + """</name>
  <os>
    <type>hvm</type>
    <loader>/usr/lib/xen/boot/hvmloader</loader>
    <boot dev='hd'/>
  </os>
  <memory>""" + `memory` + """</memory>
  <vcpu>1</vcpu>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>restart</on_crash>
  <features>
     <pae/>
     <acpi/>
     <apic/>
  </features>
  <clock sync="utc"/>
  <devices>
    <emulator>/usr/lib/xen/bin/qemu-dm</emulator>
    <interface type='bridge'>
      <source bridge='xenbr0'/>
      <mac address='""" + macaddr + """'/>
      <script path='vif-bridge'/>
    </interface>
    <disk type='file' device='disk'>
      <source file='""" + new_image_path + """'/>
      <target dev='hda'/>
    </disk>
    <graphics type='vnc' port='5904'/>
  </devices>
</domain>
"""
        elif vmtype == "solarisparavirt":
            newxmldesc="""<domain type='xen'>
  <name>""" + name + """</name>
  <bootloader>/usr/bin/pygrub</bootloader>
  <os>
    <type>linux</type>
    <kernel>""" + solaris_kernel + """</kernel>
    <initrd>""" + solaris_initrd + """</initrd>
    <root>/dev/dsk/c0d0s0</root>
    <cmdline>/platform/i86xpv/kernel/amd64/unix</cmdline>
  </os>
  <memory>""" + `memory` + """</memory>
  <vcpu>1</vcpu>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>restart</on_crash>
  <devices>
    <interface type='bridge'>
      <source bridge='xenbr0'/>
      <mac address='""" + macaddr + """'/>
      <script path='vif-bridge'/>
    </interface>
    <disk type='file' device='disk'>
      <driver name='tap' type='aio'/>
      <source file='""" + new_image_path + """'/>
      <target dev='0'/>
    </disk>
    <console tty='/dev/pts/4'/>
  </devices>
</domain>
"""
        elif vmtype == "solarisfullvirt":
            newxmldesc="""<domain type='xen'>
  <name>""" + name + """</name>
  <os>
    <type>hvm</type>
    <loader>/usr/lib/xen/boot/hvmloader</loader>
    <boot dev='hd'/>
  </os>
  <memory>""" + `memory` + """</memory>
  <vcpu>1</vcpu>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>restart</on_crash>
  <features>
     <pae/>
     <acpi/>
     <apic/>
  </features>
  <clock sync="utc"/>
  <devices>
    <emulator>/usr/lib/xen/bin/qemu-dm</emulator>
    <interface type='bridge'>
      <source bridge='xenbr0'/>
      <mac address='""" + macaddr + """'/>
      <script path='vif-bridge'/>
    </interface>
    <disk type='file' device='disk'>
      <source file='""" + new_image_path + """'/>
      <target dev='hda'/>
    </disk>
    <graphics type='vnc' port='5904'/>
  </devices>
</domain>
"""
        elif vmtype == "windows":
            newxmldesc="""<domain type='xen'>
  <name>""" + name + """</name>
  <os>
    <type>hvm</type>
    <loader>/usr/lib/xen/boot/hvmloader</loader>
    <boot dev='hd'/>
  </os>
  <memory>""" + `memory` + """</memory>
  <vcpu>1</vcpu>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>restart</on_crash>
  <features>
     <pae/>
     <acpi/>
     <apic/>
  </features>
  <clock sync="localtime"/>
  <devices>
    <emulator>/usr/lib/xen/bin/qemu-dm</emulator>
    <interface type='bridge'>
      <source bridge='xenbr0'/>
      <mac address='""" + macaddr + """'/>
      <script path='vif-bridge'/>
    </interface>
    <disk type='file' device='disk'>
      <source file='""" + new_image_path + """'/>
      <target dev='hda'/>
    </disk>
    <graphics type='vnc' port='5904'/>
  </devices>
</domain>
"""

        # Copy image over
        try:
            shutil.copy(full_image_path, new_image_path)
            os.chmod(new_image_path, 0666)
        except:
            logging.error(name+" - Unable to copy disk image")
            return False

        if check_md5 == "True":
            status = self.checkMD5(disk,new_image_path)
            while not status == 0:
                if status == -1:
                    return False
                status = self.checkMD5(disk,new_image_path)

        dom = conn.createLinux(newxmldesc, 0)
        if dom == None:
            logging.error(name+" - Failed to create the domain")
            return False

        return True

    def export_isDomainRunning(self, name):
        """Checks to see if the domain is currently running
                
            name - The name of the guest
        """

        logging.debug(name+" - Checking status")
                
        conn = libvirt.open(None)
        if conn == None:
            logging.error(name+" - Failed to open connection to the hypervisor")
            return False

        try:
            dom0 = conn.lookupByName(name)
        except:
            logging.debug(name+" - Did not find name")
            return False

        return True

    def destroyDomain(self, name):
        """Destroys the domain 
        
            name - The name of the guest
        """

        guestRunning = True

        logging.debug(name+" - Destroying domain")

        conn = libvirt.open(None)
        if conn == None:
            logging.error(name+" - Failed to open connection to the hypervisor")
            return False

        try:
            dom0 = conn.lookupByName(name)
        except:
            logging.error(name+" - Unable to find domain")
            guestRunning = False

        if guestRunning == True:
            try:
                dom0.destroy()
            except:
                logging.error(name+" - Unable to destroy domain")
                return False

        return True

    def export_saveMachine(self, name, disk, overwrite):
        """Saves the machine's image to the shared storage

            name - The name of the guest
            disk - The name of the disk to save it too
        """

        save_image_path = images_dir+"/"+disk
        running_image_path = running_images_dir+"/"+name+".disk"

        logging.debug(name+" - Saving to \""+save_image_path+"\"")

        # Make sure we're not going to overwrite an existing image
        if os.access(save_image_path, os.R_OK) and overwrite != 1:
            errMsg = name+" - Destination file exists \""+save_image_path+"\""
            logging.error(errMsg)
            return False

        # Check to see if the guest's disk exists
        if not os.access(running_image_path, os.R_OK):
            errMsg = name+" - Image does not exist \""+running_image_path+"\""
            logging.error(errMsg)
            return False

        self.destroyDomain(name)

        try:
            FILE = file(running_image_path, 'rb')
        except:
            logging.error(name+" - Unable to access \""+running_image_path+"\"")
            return False

        # Generate the md5sum for the guest image before copying it to the shared storage
        if check_md5 == "True":
            m = md5.new()

            while True:
                d = FILE.read(8096)
                if not d:
                    break
                m.update(d)
        
            FILE.close()

            digest = m.hexdigest()

            try:
                FILE = file(md5file, 'r')
                md5list = cPickle.load(FILE)
                FILE.close()
                FILE = file(md5file, 'w')
                md5list.append([disk,digest])
                cPickle.dump(md5list,FILE)
                FILE.close()
            except Exception, e:
                logging.error(name+" Error saving md5sum '"+str(e)+"'")
                return False

        # Copy the disk image from the shared storage
        try:
            shutil.copy(running_image_path, save_image_path)
        except:
            errMsg =  name+" - Unable to copy \""+running_image_path+"\" to \""
            errMsg += save_image_path+"\""
            logging.error(errMsg)
            return False

        # Check the md5sum after copying the image.  If it fails, re-copy it
        md5sumTries = 0

        if check_md5 == "True":
            status = self.checkMD5(disk,save_image_path)
            md5sumTries += 1
            while not status == 0 and md5sumTries<3:
                if status == -1:
                    return False
                status = self.checkMD5(disk, save_image_path)
                md5sumTries += 1

            if md5sumTries >= 3:
                logging.error(name+" - Exceeded max md5sum tries")
                return False

        os.chmod(save_image_path, 0444)

        try:
            os.remove(running_image_path)
        except:
            errMsg = name+" - Unable to remove disk image \""
            errMsg += running_image_path+"\""
            logging.error(errMsg)
            return False

        return True

    def export_destroyMachine(self, name):
        """Destroys the machine and removes the disk image

            name - The guest's image name
        """

        new_image_path = running_images_dir+"/"+name+".disk"

        if not self.destroyDomain(name):
            return False

        try:
            os.remove(new_image_path)
        except:
            errMsg =  name+" - Unable to remove disk image \""
            errMsg += new_image_path+"\""
            logging.error(errMsg)
            return False

        return True

class EipenXMLRPC (Daemon):
    def run(self):
        logging.debug("Starting XMLRPC Server")
        try:
            server = ForkingXMLRPCServer(('', client_port), EipenHandler)
            server.socket.settimeout(1.0)
            server.serve_forever()
        except Exception, e:
            logging.error("An error has occurred'"+str(e)+"'")
            sys.exit(1)

if __name__ == "__main__":
    xmlrpc = EipenXMLRPC('/var/run/eipen-client.pid',
                        stdout='/var/log/eipen-xmlrpc.log',
                        stderr='/var/log/eipen-xmlrpc.log')
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
