"""本地假 GitHub OAuth 服务器（仅测试）：授权页直接同意并回跳，换 token，返回固定用户。
用法：python3 tests/fake-github.py [端口]   默认 8098"""
import json, os, sys
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlparse, parse_qs, urlencode

CLIENT_ID, CLIENT_SECRET, CODE, TOKEN = 'test-client', 'test-secret', 'fake-code', 'gho_fake'
USER = {'id': 583231, 'login': 'octocat', 'name': os.environ.get('FAKE_GH_NAME', 'The <Octocat> (Dev)'), 'email': None, 'html_url': 'https://github.com/octocat'}

class H(BaseHTTPRequestHandler):
    def log_message(self, *a): pass
    def _json(self, code, body):
        b = json.dumps(body).encode()
        self.send_response(code); self.send_header('Content-Type', 'application/json'); self.send_header('Content-Length', str(len(b))); self.end_headers(); self.wfile.write(b)
    def do_GET(self):
        u = urlparse(self.path); q = {k: v[0] for k, v in parse_qs(u.query).items()}
        if u.path == '/login/oauth/authorize':
            if q.get('client_id') != CLIENT_ID: return self._json(400, {'error': 'bad client'})
            loc = q['redirect_uri'] + ('&' if '?' in q['redirect_uri'] else '?') + urlencode({'code': CODE, 'state': q.get('state', '')})
            self.send_response(302); self.send_header('Location', loc); self.end_headers(); return
        if u.path == '/user':
            if self.headers.get('Authorization') != 'Bearer ' + TOKEN: return self._json(401, {'message': 'Bad credentials'})
            return self._json(200, USER)
        self._json(404, {'message': 'Not Found'})
    def do_POST(self):
        u = urlparse(self.path)
        n = int(self.headers.get('Content-Length') or 0)
        q = {k: v[0] for k, v in parse_qs(self.rfile.read(n).decode()).items()}
        if u.path == '/login/oauth/access_token':
            if q.get('client_id') == CLIENT_ID and q.get('client_secret') == CLIENT_SECRET and q.get('code') == CODE:
                return self._json(200, {'access_token': TOKEN, 'token_type': 'bearer', 'scope': ''})
            return self._json(200, {'error': 'bad_verification_code'})
        self._json(404, {'message': 'Not Found'})

port = int(sys.argv[1]) if len(sys.argv) > 1 else 8098
HTTPServer(('127.0.0.1', port), H).serve_forever()
