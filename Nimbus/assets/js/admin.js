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
