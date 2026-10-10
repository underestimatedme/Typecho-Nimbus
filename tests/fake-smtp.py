#!/usr/bin/env python3
"""本地假 SMTP 服务器（仅测试用）：支持 EHLO / AUTH LOGIN / MAIL / RCPT / DATA，把收到的邮件存为 .eml。
用法：python3 tests/fake-smtp.py <端口> <输出目录>"""
import socketserver, sys, os, time, base64

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 2525
OUT = sys.argv[2] if len(sys.argv) > 2 else '/tmp/nimbus-mail'
os.makedirs(OUT, exist_ok=True)

class H(socketserver.StreamRequestHandler):
    def send(self, s): self.wfile.write((s + '\r\n').encode())
    def handle(self):
        self.send('220 fake-smtp ready')
        rcpt, mail_from, auth_state, user = [], None, None, ''
        while True:
            line = self.rfile.readline()
            if not line: return
            cmd = line.decode('utf-8', 'replace').rstrip('\r\n')
            up = cmd.upper()
            if auth_state == 'user':
                user = base64.b64decode(cmd).decode(); auth_state = 'pass'; self.send('334 UGFzc3dvcmQ6'); continue
            if auth_state == 'pass':
                pw = base64.b64decode(cmd).decode(); auth_state = None
                self.send('235 ok' if pw != 'wrong' else '535 auth failed'); continue
            if up.startswith('EHLO') or up.startswith('HELO'):
                self.send('250-fake-smtp'); self.send('250 AUTH LOGIN')
            elif up == 'AUTH LOGIN':
                auth_state = 'user'; self.send('334 VXNlcm5hbWU6')
            elif up.startswith('MAIL FROM'):
                mail_from = cmd[10:]; self.send('250 ok')
            elif up.startswith('RCPT TO'):
                rcpt.append(cmd[8:]); self.send('250 ok')
            elif up == 'DATA':
                self.send('354 end with .')
                data = []
                while True:
                    l = self.rfile.readline()
                    if l in (b'.\r\n', b'.\n', b''): break
                    data.append(l)
                name = os.path.join(OUT, '%d-%s.eml' % (int(time.time() * 1000), rcpt[-1].strip('<>').replace('@', '_at_')))
                with open(name, 'wb') as f:
                    f.write(('X-Fake-From: %s\r\nX-Fake-To: %s\r\nX-Fake-User: %s\r\n' % (mail_from, ','.join(rcpt), user)).encode() + b''.join(data))
                rcpt = []
                self.send('250 queued')
            elif up == 'QUIT':
                self.send('221 bye'); return
            else:
                self.send('250 ok')

class S(socketserver.ThreadingTCPServer):
    allow_reuse_address = True

S(('127.0.0.1', PORT), H).serve_forever()
