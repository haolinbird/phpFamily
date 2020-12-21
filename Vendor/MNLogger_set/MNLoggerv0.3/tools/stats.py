#!/usr/bin/env python

import sys
if sys.version_info[0] < 2 or sys.version_info[1] < 6:
    print "Sorry, mqstat requires at least Python 2.6."
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

VERSION = '3.1.5'

options = { "hostname"        : "192.168.49.5",
            "port"            : "15672",
            "declare_vhost"   : "/ms",
            "username"        : "guest",
            "password"        : "guest",
            "ip": ""}

def main():
    options['ip'] = socket.gethostbyname(socket.gethostname())
    while(1):
        data = http('GET', '/api/queues')
        for v in parse_json(data):
            if v['vhost'] == options['declare_vhost']:
                if 'messages' not in v:
                    v['messages'] = 0
                write_log_file(log(v['name'], str(v['messages']), str(v['messages_unacknowledged']), str(v['consumers'])))
        time.sleep(5)

def log(queue, messages, messages_unacknowledged, consumers):
    text  = '[MN][0001]'
    text += '[' + strftime("%Y-%m-%d %H:%M:%S", localtime()) + '.0000,mq,' + options['ip'] + ']'
    text += '{mq,' + queue + '}{' + messages + ',' + messages_unacknowledged + ',' + consumers + '}\n'
    return text

def parse_json(text):
        try:
            return json.loads(text)
        except ValueError:
            print "Could not parse JSON:\n  {0}".format(text)
            sys.exit(1)

def die(s):
    sys.stderr.write(maybe_utf8("*** {0}\n".format(s), sys.stderr))
    exit(1)

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

def write_log_file(data):
    f = open("./mqstat/mqstat." + strftime("%Y%m%d", localtime()) + ".log", 'a')
    f.write(data)
    f.close()

if __name__ == "__main__":
    main()
