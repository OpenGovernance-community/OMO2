"""Private JVB counters -> signed OMO receiver. No media, identities or database credentials."""
import hashlib
import hmac
import json
import os
from pathlib import Path
import re
import signal
import sys
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

MAX_BODY = 16 * 1024 * 1024
MAX_PENDING = 10000
stopping = threading.Event()


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def packet_bytes(tree):
    values = []
    if not isinstance(tree, dict):
        return None
    for key, value in tree.items():
        if not isinstance(value, dict):
            continue
        if 'PacketStreamStats' in key:
            counter = value.get('bytes_sent', value.get('bytes'))
            if type(counter) is int and 0 <= counter <= 2**63 - 1:
                values.append(counter)
        nested = packet_bytes(value)
        if nested is not None:
            values.append(nested)
    return max(values) if values else None


def observations(payload, bridge):
    conferences = payload.get('conferences')
    if conferences == []:
        conferences = {}
    if not isinstance(conferences, dict):
        raise ValueError('Unsupported JVB response')
    result = []
    for conference_id, conference in conferences.items():
        if not isinstance(conference, dict):
            continue
        match = re.match(r'omo-([a-f0-9]{32})(?:@|$)', str(conference.get('name', '')))
        if not match:
            continue
        endpoints = conference.get('endpoints', {})
        if endpoints == []:
            endpoints = {}
        if not isinstance(endpoints, dict):
            raise ValueError('JVB full counters unavailable')
        for endpoint_id, endpoint in endpoints.items():
            if not isinstance(endpoint, dict):
                raise ValueError('JVB full counters unavailable')
            transceiver = endpoint.get('transceiver', {})
            received = packet_bytes(transceiver.get('receiver', {}))
            sent = packet_bytes(transceiver.get('sender', {}))
            if received is None or sent is None:
                raise ValueError('Unsupported JVB packet counter format')
            creation = conference.get('creation_time')
            identity = ':'.join([bridge, str(conference_id), '' if creation is None else str(creation), str(endpoint_id)])
            result.append({'key': hashlib.sha256(identity.encode()).hexdigest(), 'code': match[1], 'received': received, 'sent': sent})
    return result


def read_json(opener, request, limit, timeout):
    with opener.open(request, timeout=timeout) as response:
        if response.status != 200:
            raise ValueError('Unexpected HTTP status')
        body = response.read(limit + 1)
        if len(body) > limit:
            raise ValueError('Response too large')
        return json.loads(body)


def deliver(opener, target, key, batch, source_status='ok'):
    body = json.dumps({'version': 1, 'source_status': source_status, 'observations': batch}, separators=(',', ':')).encode()
    timestamp = str(int(time.time()))
    signature = hmac.new(key.encode(), timestamp.encode() + b'\n' + body, hashlib.sha256).hexdigest()
    request = urllib.request.Request(target, data=body, method='POST', headers={
        'Content-Type': 'application/json', 'X-OMO-Traffic-Timestamp': timestamp,
        'X-OMO-Traffic-Signature': signature, 'User-Agent': 'OMO2-Visio-Traffic/1.0',
    })
    result = read_json(opener, request, 4096, 15)
    if not isinstance(result, dict) or result.get('status') != 'ok':
        raise ValueError('Receiver rejected collection')
    return result


def save_pending(path, pending):
    temporary = path.with_suffix('.tmp')
    temporary.write_text(json.dumps(pending, separators=(',', ':')), encoding='utf-8')
    os.replace(temporary, path)


def run():
    target = os.environ['VISIO_TRAFFIC_PUSH_URL']
    parsed = urllib.parse.urlsplit(target)
    if parsed.scheme != 'https' or parsed.username or parsed.password or parsed.query or parsed.fragment or parsed.path != '/visio/traffic.php':
        raise ValueError('An HTTPS OMO receiver URL is required')
    key = os.environ['VISIO_TRAFFIC_SIGNING_KEY']
    if not re.fullmatch('[a-f0-9]{64}', key):
        raise ValueError('Invalid collector signing key')
    source = os.environ.get('VISIO_TRAFFIC_URL', 'http://jvb:8080/debug?full=true')
    bridge = os.environ.get('VISIO_TRAFFIC_BRIDGE', 'meet.opengov.tools')
    path = Path(os.environ.get('VISIO_TRAFFIC_STATE', '/state/pending.json'))
    opener = urllib.request.build_opener(NoRedirect())
    pending = json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
    if not isinstance(pending, dict) or len(pending) > MAX_PENDING:
        raise ValueError('Invalid pending counter state')
    while not stopping.is_set():
        try:
            try:
                payload = read_json(opener, source, MAX_BODY, 10)
                current = observations(payload, bridge)
            except Exception as error:
                print('JVB collection failed: ' + type(error).__name__, flush=True)
                deliver(opener, target, key, [], 'unavailable')
                stopping.wait(30)
                continue
            for observation in current:
                previous = pending.get(observation['key'])
                if previous:
                    observation['received'] = max(previous['received'], observation['received'])
                    observation['sent'] = max(previous['sent'], observation['sent'])
                pending[observation['key']] = observation
            if len(pending) > MAX_PENDING:
                raise ValueError('Pending counter limit reached; delivery must be restored')
            save_pending(path, pending)
            batches = [list(pending.values())[offset:offset + 1000] for offset in range(0, len(pending), 1000)] or [[]]
            samples = unmatched = 0
            for batch in batches:
                result = deliver(opener, target, key, batch)
                samples += result.get('samples', 0)
                unmatched += result.get('unmatched', 0)
                for observation in batch:
                    pending.pop(observation['key'], None)
                save_pending(path, pending)
            print(json.dumps({'status': 'ok', 'samples': samples, 'endpoints': len(current), 'unmatched': unmatched}), flush=True)
        except urllib.error.HTTPError as error:
            print('Traffic delivery failed: HTTP ' + str(error.code), flush=True)
        except Exception as error:
            # Never print response bodies, addresses, room codes or signing credentials.
            print('Traffic collector failed: ' + type(error).__name__, flush=True)
        stopping.wait(30)


if __name__ == '__main__':
    for signum in (signal.SIGTERM, signal.SIGINT):
        signal.signal(signum, lambda *_: stopping.set())
    try:
        run()
    except Exception as error:
        print('Traffic collector stopped: ' + type(error).__name__, file=sys.stderr, flush=True)
        sys.exit(1)
