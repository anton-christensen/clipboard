import daemon
import lockfile
import sys
import socket

with daemon.DaemonContext(
        pidfile=lockfile.FileLock('/var/run/acclipboard.pid'),
        stdout=sys.stdout,
        stderr=sys.stderr
    ):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('127.0.0.1', 8080))
        s.listen()
        con, addr = s.accept()
        with con:
            print('connected by', addr)
            while(True):
                data = con.recv(1024)
                if(not data):
                    break
                con.sendall(data)
    
