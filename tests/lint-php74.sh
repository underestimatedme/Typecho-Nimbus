#!/usr/bin/env bash
# 粗略检查 PHP 8.0+ 专有语法，保证 7.4 兼容
cd "$(dirname "$0")/.."
bad=0
pat='\bmatch\s*\(|^\s*enum\s+[A-Z]|\breadonly\s+(public|private|protected|int|string|array|\?)|\bstr_contains\(|\bstr_starts_with\(|\bstr_ends_with\(|\)\s*:\s*(int|string|array|bool|float|\?[a-z]+)\|[a-z]|\bnever\b|\bnew\s+class\s*\(.*\bthis\b|\?->|\$[a-zA-Z_]+\s*\?\?=|\bfn\s*\(.*\):\s*static\b'
hits=$(grep -rnE "$pat" Nimbus NimbusKit --include='*.php' | grep -vE '^\S+:\s*(//|\*|/\*)')
if [ -n "$hits" ]; then echo "PHP 8+ syntax found:"; echo "$hits"; bad=1; fi
# 命名参数 foo(name: value) —— 排除三元与 case 标签的粗略检测
hits2=$(grep -rnE '\b[a-zA-Z_]+\(\s*[a-zA-Z_]+:\s*[^:=]' Nimbus NimbusKit --include='*.php' | grep -vE 'case |\?|https?:|//|\*' )
if [ -n "$hits2" ]; then echo "Possible named args:"; echo "$hits2"; bad=1; fi
exit $bad
