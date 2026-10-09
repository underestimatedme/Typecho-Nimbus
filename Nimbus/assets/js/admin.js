/* Nimbus 后台配置页：分组 Tab、记忆当前 Tab、图片预览 */
(function () {
  var root = document.querySelector('[data-nimbus-admin]');
  if (!root) return;
  var tabs = root.querySelectorAll('[data-tab]');
  var items = document.querySelectorAll('.n-admin-item');
  var groups = {};
  items.forEach(function (el) {
    var m = (el.className || '').match(/n-tab-(\w+)/);
    if (!m) return;
    var li = el.closest('li') || el.closest('.typecho-option') || el.parentElement;
    if (!li) return;
    li.classList.add('n-admin-row');
    (groups[m[1]] = groups[m[1]] || []).push(li);
    if (/(Image|Avatar|Favicon|Reward|HeroImages|ThumbPool|LogoImage)/.test(el.name || '') && el.tagName !== 'INPUT' || /(Image|Avatar|Favicon|Reward|LogoImage)/.test(el.name || '')) {
      var url = (el.value || '').split('\n')[0].trim();
      if (/^https?:\/\/|^\//.test(url)) {
        var img = document.createElement('img');
        img.className = 'n-admin-preview';
        img.src = url;
        img.onerror = function () { img.remove(); };
        li.appendChild(img);
      }
    }
  });
  var key = 'nimbus-admin-tab';
  var current;
  try { current = localStorage.getItem(key); } catch (e) {}
  if (!current || !groups[current]) current = tabs[0] && tabs[0].getAttribute('data-tab');
  function show(name) {
    Object.keys(groups).forEach(function (g) {
      groups[g].forEach(function (li) { li.classList.toggle('n-hidden', g !== name); });
    });
    tabs.forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tab') === name); });
    try { localStorage.setItem(key, name); } catch (e) {}
  }
  tabs.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-tab')); }); });
  show(current);
})();
