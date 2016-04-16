import json, sys, subprocess, multiprocessing, re

class DataObject:
    def __init__(self, params):
        self.domain, self.ip, self.digPath, self.resolver = params


def run_process(obj_instance):
    domain, ip, digPath, resolver = obj_instance.domain, obj_instance.ip, obj_instance.digPath, obj_instance.resolver
    processed = []
    processed.append(subprocess.check_output([digPath + ' @' + resolver + ' ' + ip + '.' + domain + ' TXT'], shell=True))
    return processed


if __name__ == '__main__':
    """ sys.argv[1] = test ip address (must be reversed)
        sys.argv[2] = dig path (/usr/bin/dig)
        sys.argv[3] = resolver ip address
        sys.argv[4] = blacklists json array
        Usage example: python ./quickDig.py 2.0.0.127 /usr/bin/dig 127.0.0.1 /<path>/blacklistsAlive.txt
    """
    try:
        blacklist = json.loads(sys.argv[4])
        objList = []
        for domain in blacklist:
            objList.append(DataObject((domain, sys.argv[1], sys.argv[2], sys.argv[3])))
        pool = multiprocessing.Pool()
        results = pool.map(run_process, objList)
        print(json.dumps(results))
    except Exception as e:
        print ('error')
