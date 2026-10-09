#!/usr/bin/env bash
# php -l 全部 PHP 文件 + PHP 7.4 语法限制检查
cd "$(dirname "$0")/.."
fail=0
while IFS= read -r f; do
  out=$(php -l "$f" 2>&1) || { echo "$out"; fail=1; }
done < <(find Nimbus tests -name '*.php')
bash tests/lint-php74.sh || fail=1
[ $fail -eq 0 ] && echo "lint ok"
exit $fail
