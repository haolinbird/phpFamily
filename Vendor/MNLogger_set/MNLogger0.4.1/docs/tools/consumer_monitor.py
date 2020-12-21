#!/usr/bin/env python

# Only used to montior the consumers on unreliable net environments.
# Remember to stop this script when you want to stop the consumer.

import sys
if sys.version_info[0] < 2 or sys.version_info[1] < 6:
    print "Sorry, mq worker monitor requires at least Python 2.6."
    sys.exit(1)

import httplib
import urllib
import urlparse
import base64
import json
import os
import socket
import time
from time import localtime, strftime
from subprocess import call

VERSION = '3.1.5'

options = { "hostname"        : "192.168.49.5",
            "port"            : "15672",
            "declare_vhost"   : "/ms",
            "username"        : "ms",
            "password"        : "ms",
            "ip": ""}

queue = '/api/queues/%2Fms/queue.warehouse.web'

def main():
    print 'Started monitor the worker of queue: ' + queue
    #options['ip'] = socket.gethostbyname(socket.gethostname())
    #print options['ip']
    while(1):
        try:
            data = http('GET', queue)
            data = parse_json(data)
            if len(data['consumer_details']) == 0:
                time.sleep(1)
                call(["/etc/init.d/supervisor", "stop"])
                call(["/etc/init.d/supervisor", "start"])
                print "restarted the worker."
        except:
            print "HTTP access error."
            pass
        time.sleep(3)

def parse_json(text):
        try:
            return json.loads(text)
        except ValueError:
            print "Could not parse JSON:\n  {0}".format(text)
            sys.exit(1)

def die(s):
    print s
    #exit(1)

def http(method, path, body = ''):
    
    conn = httplib.HTTPConnection(options['hostname'], options['port'])
    headers = {"Authorization":
                   "Basic " + base64.b64encode(options['username'] + ":" +
                                               options['password'])}
    if body != "":
        headers["Content-Type"] = "application/json"
    try:
        conn.request(method, path, body, headers)
    except socket.error, e:
        die("Could not connect: {0}".format(e))
    resp = conn.getresponse()
    if resp.status == 400:
        die(json.loads(resp.read())['reason'])
    if resp.status == 401:
        die("Access refused: {0}".format(path))
    if resp.status == 404:
        die("Not found: {0}".format(path))
    if resp.status == 301:
        url = urlparse.urlparse(resp.getheader('location'))
        [host, port] = url.netloc.split(':')
        options['hostname'] = host
        options['port'] = int(port)
        return http(options, method, url.path + '?' + url.query, body)
    if resp.status < 200 or resp.status > 400:
        raise Exception("Received %d %s for path %s\n%s"
                        % (resp.status, resp.reason, path, resp.read()))
    return resp.read()

if __name__ == "__main__":
    main()
