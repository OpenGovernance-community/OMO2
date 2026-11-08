import hashlib
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('traffic_collector', Path(__file__).resolve().parents[1] / 'docker/jitsi-vps/traffic/collector.py')
collector = importlib.util.module_from_spec(spec)
spec.loader.exec_module(collector)


class TrafficCollectorTest(unittest.TestCase):
    def test_shared_counters_and_private_payload(self):
        node = lambda value: {'PacketStreamStats': {'bytes_sent': value}}
        code = 'a' * 32
        payload = {'private': 'must not leave bridge', 'conferences': {'c1': {
            'name': 'omo-' + code + '@muc.meet.jitsi', 'creation_time': 123,
            'endpoints': {'e1': {'display_name': 'private identity', 'transceiver': {
                'receiver': node(100), 'sender': {'rtp': node(200), 'rtcp': node(200)}}}}}}}
        result = collector.observations(payload, 'test')
        self.assertEqual(result, [{'key': hashlib.sha256(b'test:c1:123:e1').hexdigest(), 'code': code, 'received': 100, 'sent': 200}])
        self.assertNotIn('private', json.dumps(result))
        self.assertEqual(collector.observations({'conferences': []}, 'test'), [])
        with self.assertRaises(ValueError):
            collector.observations({'conferences': {'x': {'name': 'omo-' + code, 'endpoints': {'e': {}}}}}, 'test')

    def test_counter_is_buffered_until_delivery_even_after_endpoint_disappears(self):
        observation = {'key': 'b' * 64, 'code': 'a' * 32, 'received': 100, 'sent': 200}
        with tempfile.TemporaryDirectory() as directory:
            state = Path(directory) / 'pending.json'
            environment = {'VISIO_TRAFFIC_PUSH_URL': 'https://example.com/visio/traffic.php',
                           'VISIO_TRAFFIC_SIGNING_KEY': 'c' * 64, 'VISIO_TRAFFIC_STATE': str(state)}
            delivered = []
            def deliver(opener, target, key, batch, *args):
                delivered.append(batch)
                if len(delivered) == 1:
                    raise RuntimeError('temporary outage')
                return {'status': 'ok', 'samples': 1}
            collector.stopping.clear()
            def wait(_):
                self.assertEqual(json.loads(state.read_text()), {} if len(delivered) == 2 else {observation['key']: observation})
                if len(delivered) == 2:
                    collector.stopping.set()
            with patch.dict(collector.os.environ, environment), patch.object(collector, 'read_json', return_value={}), \
                    patch.object(collector, 'observations', side_effect=[[observation], []]), \
                    patch.object(collector, 'deliver', side_effect=deliver), patch.object(collector.stopping, 'wait', side_effect=wait):
                collector.run()
            self.assertEqual(delivered, [[observation], [observation]])
            collector.stopping.clear()


if __name__ == '__main__':
    unittest.main()
