/* Nimbus 后台配置页：分组 Tab、记忆当前 Tab、图片预览 */
function nimbusAdmin() {
  var root = document.querySelector('[data-nimbus-admin]');
  if (!root) return;
  var tabs = root.querySelectorAll('[data-tab]');
  var rows = document.querySelectorAll('ul.n-admin-item');
  var groups = {};
  rows.forEach(function (ul) {
    var m = (ul.className || '').match(/n-tab-(\w+)/);
    if (!m) return;
    (groups[m[1]] = groups[m[1]] || []).push(ul);
    var field = ul.querySelector('input[type=text], textarea');
    if (field && /(Image|Avatar|Favicon|Reward|HeroImages|ThumbPool)/.test(field.name || '')) {
      var url = (field.value || '').split('\n')[0].trim();
      if (/^https?:\/\/|^\//.test(url)) {
        var img = document.createElement('img');
        img.className = 'n-admin-preview';
        img.src = url;
        img.onerror = function () { img.remove(); };
        field.parentNode.appendChild(img);
      }
    }
  });
  // 未保存过的选项：单选按默认值勾选，文本框显示默认值占位
  var defaults = {};
  try { defaults = JSON.parse(root.getAttribute('data-defaults') || '{}'); } catch (e) {}
  var unsaved = [];
  try { unsaved = JSON.parse(root.getAttribute('data-unsaved') || '[]'); } catch (e) {}
  unsaved.forEach(function (name) {
    var boxes = document.querySelectorAll('input[type=checkbox][name="' + name + '[]"]');
    var want = Array.isArray(defaults[name]) ? defaults[name].map(String) : [];
    boxes.forEach(function (b) { b.checked = want.indexOf(b.value) !== -1; });
  });
  // 多选全部取消时 Typecho 不会提交该字段，保存后又回落到默认值；提交时补一个占位值「-」
  var form = document.querySelector('form[action*="themes-edit"]');
  if (form) form.addEventListener('submit', function () {
    Object.keys(defaults).forEach(function (name) {
      if (!Array.isArray(defaults[name])) return;
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = name + '[]'; h.value = '-';
      form.appendChild(h);
    });
  });
  Object.keys(defaults).forEach(function (name) {
    var radios = document.querySelectorAll('input[type=radio][name="' + name + '"]');
    if (radios.length) {
      var any = Array.prototype.some.call(radios, function (r) { return r.checked; });
      if (!any) radios.forEach(function (r) { if (r.value === String(defaults[name])) r.checked = true; });
      return;
    }
    var field = document.querySelector('input[type=text][name="' + name + '"], textarea[name="' + name + '"]');
    if (field && !field.value && defaults[name]) field.placeholder = '默认：' + String(defaults[name]).split('\n')[0];
  });
  // 配置导入 / 导出：纯前端，导出当前表单值为 JSON；导入只填回表单，确认后由站长点「保存设置」
  (function () {
    var form = document.querySelector('form[action*="themes-edit"]');
    var head = root.querySelector('.n-admin-head');
    if (!form || !head) return;
    var names = Object.keys(defaults);
    function collect() {
      var out = {};
      names.forEach(function (name) {
        var multi = form.querySelectorAll('input[type=checkbox][name="' + name + '[]"]');
        if (multi.length) { out[name] = Array.prototype.filter.call(multi, function (b) { return b.checked; }).map(function (b) { return b.value; }); return; }
        var radios = form.querySelectorAll('input[type=radio][name="' + name + '"]');
        if (radios.length) { var c = Array.prototype.find.call(radios, function (r) { return r.checked; }); if (c) out[name] = c.value; return; }
        var f = form.querySelector('[name="' + name + '"]');
        if (f) out[name] = f.value;
      });
      return out;
    }
    function apply(settings) {
      var n = 0, skipped = 0;
      form.querySelectorAll('.n-admin-changed').forEach(function (el) { el.classList.remove('n-admin-changed'); });
      Object.keys(settings).forEach(function (name) {
        if (names.indexOf(name) === -1) { skipped++; return; }
        var v = settings[name], hit = null;
        var multi = form.querySelectorAll('input[type=checkbox][name="' + name + '[]"]');
        var radios = form.querySelectorAll('input[type=radio][name="' + name + '"]');
        if (multi.length) {
          var want = (Array.isArray(v) ? v : String(v).split(',')).map(String);
          multi.forEach(function (b) { b.checked = want.indexOf(b.value) !== -1; });
          hit = multi[0];
        } else if (radios.length) {
          radios.forEach(function (r) { if (r.value === String(v)) { r.checked = true; hit = r; } });
          if (!hit) { skipped++; return; }
        } else {
          var f = form.querySelector('[name="' + name + '"]');
          if (!f || typeof v === 'object') { skipped++; return; }
          if (f.tagName === 'SELECT' && !Array.prototype.some.call(f.options, function (o) { return o.value === String(v); })) { skipped++; return; }
          f.value = String(v);
          hit = f;
        }
        var row = hit && hit.closest('ul.typecho-option');
        if (row) row.classList.add('n-admin-changed');
        n++;
      });
      return { n: n, skipped: skipped };
    }
    var bar = document.createElement('div');
    bar.className = 'n-admin-io';
    bar.innerHTML = '<button type="button" data-io-export>导出配置</button><label><input type="file" accept=".json,application/json" hidden data-io-file>导入配置</label>';
    head.insertBefore(bar, head.querySelector('.n-admin-link'));
    var note = document.createElement('div');
    note.className = 'n-admin-note';
    note.hidden = true;
    head.parentNode.insertBefore(note, head.nextSibling);
    function say(text, bad) { note.textContent = text; note.hidden = false; note.classList.toggle('is-bad', !!bad); }
    bar.querySelector('[data-io-export]').addEventListener('click', function () {
      var data = { theme: 'Nimbus', version: (root.querySelector('.n-admin-ver') || {}).textContent || '', exported: new Date().toISOString(), settings: collect() };
      var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      var a = document.createElement('a');
      var d = new Date();
      a.href = URL.createObjectURL(blob);
      a.download = 'nimbus-config-' + d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0') + '.json';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
      say('已导出当前表单中的 ' + Object.keys(data.settings).length + ' 项设置（包括尚未保存的修改）。');
    });
    bar.querySelector('[data-io-file]').addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      e.target.value = '';
      if (!file) return;
      if (file.size > 1024 * 1024) { say('文件过大，不像是 Nimbus 配置文件。', true); return; }
      var reader = new FileReader();
      reader.onload = function () {
        var data;
        try { data = JSON.parse(String(reader.result)); } catch (err) { say('无法解析：不是有效的 JSON 文件。', true); return; }
        if (!data || data.theme !== 'Nimbus' || typeof data.settings !== 'object' || !data.settings) { say('这不是 Nimbus 导出的配置文件。', true); return; }
        var r = apply(data.settings);
        say('已填入 ' + r.n + ' 项设置' + (r.skipped ? '，跳过 ' + r.skipped + ' 项（当前版本不存在或取值无效）' : '') + '。请检查高亮的选项，确认无误后点击页面底部的「保存设置」。');
      };
      reader.readAsText(file);
    });
    window.__nimbusConfigIO = { collect: collect, apply: apply };
  })();
  var key = 'nimbus-admin-tab';
  var current = null;
  try { current = localStorage.getItem(key); } catch (e) {}
  if (!current || !groups[current]) current = tabs[0] && tabs[0].getAttribute('data-tab');
  function show(name) {
    Object.keys(groups).forEach(function (g) {
      groups[g].forEach(function (ul) { ul.classList.toggle('n-hidden', g !== name); });
    });
    tabs.forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === name); });
    try { localStorage.setItem(key, name); } catch (e) {}
  }
  tabs.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-tab')); }); });
  show(current);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', nimbusAdmin); else nimbusAdmin();
