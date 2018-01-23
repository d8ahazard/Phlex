"""Discovers Chromecasts on the network using mDNS/zeroconf."""
from uuid import UUID

import six
import time
from zeroconf import ServiceBrowser, Zeroconf, BadTypeInNameException
import logging


DISCOVER_TIMEOUT = 5

log = logging.getLogger(__name__)
log.setLevel(logging.DEBUG)
log.addHandler(logging.NullHandler())

class CastListener(object):
    """Zeroconf Cast Services collection."""
    def __init__(self, callback=None):
        self.services = {}
        self.callback = callback

    @property
    def count(self):
        """Number of discovered cast services."""
        return len(self.services)

    @property
    def devices(self):
        """List of tuples (ip, host) for each discovered device."""
        return list(self.services.values())

    # pylint: disable=unused-argument
    def remove_service(self, zconf, typ, name):
        """ Remove a service from the collection. """
        self.services.pop(name, None)

    def add_service(self, zconf, typ, name):
        log.debug("pcc:discover:add_service called for name of %s" % name)
        """ Add a service to the collection. """
        service = None
        tries = 0
        while service is None and tries < 4:
            try:
                service = zconf.get_service_info(typ, name)
            except IOError:
                break
            tries += 1

        if not service:
            return

        def get_value(key):
            """Retrieve value and decode for Python 2/3."""
            value = service.properties.get(key.encode('utf-8'))

            if value is None or isinstance(value, six.text_type):
                return value
            return value.decode('utf-8')

        ips = zconf.cache.entries_with_name(service.server.lower())
        host = repr(ips[0]) if ips else service.server

        model_name = get_value('md')
        uuid = get_value('id')
        friendly_name = get_value('fn')

        if uuid:
            uuid = UUID(uuid)

        self.services[name] = (host, service.port, uuid, model_name,
                               friendly_name)

        if self.callback:
            self.callback(name)


def start_discovery(callback=None):
    """
    Start discovering chromecasts on the network.

    This method will start discovering chromecasts on a separate thread. When
    a chromecast is discovered, the callback will be called with the
    discovered chromecast's zeroconf name. This is the dictionary key to find
    the chromecast metadata in listener.services.

    This method returns the CastListener object and the zeroconf ServiceBrowser
    object. The CastListener object will contain information for the discovered
    chromecasts. To stop discovery, call the stop_discovery method with the
    ServiceBrowser object.
    """
    listener = CastListener(callback)
    return listener, \
        ServiceBrowser(Zeroconf(), "_googlecast._tcp.local.", listener)


def stop_discovery(browser):
    """Stop the chromecast discovery thread."""
    browser.zc.close()


def discover_chromecasts(max_devices=None, timeout=DISCOVER_TIMEOUT):
    """ Discover chromecasts on the network. """
    browser = None
    zconf = None
    try:
        zconf = Zeroconf()
        listener = CastListener()
        browser = ServiceBrowser(zconf, "_googlecast._tcp.local.", listener)

        if max_devices is None:
            time.sleep(timeout)
            return listener.devices

        else:
            start = time.time()

            while (time.time() - start < timeout and
                   listener.count < max_devices):
                time.sleep(.1)

            return listener.devices
    except Exception as e:
        log.debug("Caught an error: " + e.message)

    finally:
        if browser:
            browser.cancel()
        if zconf:
            zconf.close()