
import time
import os
import logging
import sys
from daemon.runner import DaemonRunner

_base_path = os.path.dirname(os.path.realpath(__file__))


class ACClipboard(object):
    """
      Define the required attributes
    """
    stdin_path = "/dev/null"
    stdout_path =  "/dev/null"#sys.stdout#os.path.join(_base_path, "app.out") # Can also be /dev/null 
    stderr_path =  "/dev/null"#sys.stderr#os.path.join(_base_path, "app.err") # Can also be /dev/null
    pidfile_path = "/dev/null"#os.path.join(_base_path, "app.pid")
    pidfile_timeout = 3 

    def run(self):
        #Basic logging 
        logging.basicConfig(format="%(asctime)s [ARDUINO/%(processName)s] %(levelname)s %(message)s",
                            filename=os.path.join(_base_path, "app.out"),
                            level=logging.INFO)
        logging.info("Starting")
        try:
            with open(os.path.join(_base_path,'spam.data'), 'w') as o:
                while True:
                    # Loop will be broken by signal
                    o.write("Hey\n")
                    logging.info("Hearbeat")
                    time.sleep(1)
                o.write("Done\n")
        except (SystemExit,KeyboardInterrupt):
            # Normal exit getting a signal from the parent process
            pass
        except:
            # Something unexpected happened? 
            logging.exception("Exception")
        finally:
            logging.info("Finishing")

if __name__ == '__main__':
    """
      Call with arguments start/restart/stop
    """
    run = DaemonRunner(ACClipboard())
    run.do_action()
