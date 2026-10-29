/* The Metadata panel of the editor page (file_editor/edit.php): the Dublin Core record, the RAD description and what the record lacks, generated from the TEI header of the text in the
   editor (unsaved changes included) by metadata.php. Opened with the Metadata button; refreshed by its button and after every save, and marked as changed while the text is being edited.
   Reads window.IPS_FE_EDIT (csrf) and window.IPS_FE_TEXT() from edit-page.js, and calls window.IPS_FE_FIT() when it opens or closes so the editor makes room for it.
   Nothing the server sends is put in the page as markup: the Dublin Core and the notes are text, and the RAD view (XHTML from the stylesheet) is copied node by node, keeping only the
   elements div, p and span, their class and data-rule, and their text. */
(function () {
  'use strict';
  var cfg = window.IPS_FE_EDIT;
  var btn = document.getElementById('fe-meta-btn'), panel = document.getElementById('fe-meta');
  if (!cfg || !btn || !panel) { return; }
  var $ = function (id) { return document.getElementById(id); };
  var body = $('fe-meta-body'), statusEl = $('fe-meta-status'), tabs = panel.querySelectorAll('[data-view]');
  var data = null, view = 'dc', stale = false, busy = false;

  var NOTE = {
    draft: 'Draft: not shown in public zones',
    private: 'Private: not shown in public zones',
    published: 'Published: may be shown in public zones'
  };

  function fit() { if (typeof window.IPS_FE_FIT === 'function') { window.IPS_FE_FIT(); } }
  function el(name, className, text) {
    var e = document.createElement(name);
    if (className) { e.className = className; }
    if (text !== undefined) { e.textContent = text; }
    return e;
  }

  // the stylesheet's XHTML, rebuilt from what is allowed
  var ALLOWED = { div: true, p: true, span: true };
  function copy(node, into) {
    for (var child = node.firstChild; child; child = child.nextSibling) {
      if (child.nodeType === 3) { into.appendChild(document.createTextNode(child.nodeValue)); continue; }
      if (child.nodeType !== 1) { continue; }
      var name = child.localName.toLowerCase();
      if (!ALLOWED[name]) { continue; }
      var made = document.createElement(name);
      var cls = child.getAttribute('class'), rule = child.getAttribute('data-rule');
      if (cls && /^[a-z0-9 -]+$/.test(cls)) { made.className = cls; }
      if (rule && /^[0-9A-Za-z.]+$/.test(rule)) { made.setAttribute('data-rule', rule); made.title = 'RAD ' + rule; }
      copy(child, made);
      into.appendChild(made);
    }
  }

  function showDc() {
    var table = el('table', 'fe-meta-table');
    data.dc.forEach(function (row) {
      var tr = el('tr');
      tr.appendChild(el('th', '', row[0])); tr.appendChild(el('td', '', row[1]));
      table.appendChild(tr);
    });
    if (!data.dc.length) { return el('p', 'fe-muted', 'Nothing to show: the record holds none of the fields.'); }
    return table;
  }
  function showRad() {
    var holder = el('div', 'fe-meta-rad');
    if (!data.rad) { return el('p', 'fe-muted', 'No description could be made.'); }
    var xml = new DOMParser().parseFromString(data.rad, 'application/xml');
    if (xml.getElementsByTagName('parsererror').length) { return el('p', 'fe-muted', 'The description could not be read.'); }
    copy(xml.documentElement, holder);
    // the record's own wrapper is a div: show its contents
    return holder.firstChild && holder.firstChild.className === 'rad-record' ? holder.firstChild : holder;
  }
  function showGaps() {
    if (!data.completeness.length) { return el('p', 'fe-muted', 'Nothing is missing that the Dublin Core or RAD views want.'); }
    var list = el('ul', 'fe-meta-gaps');
    data.completeness.forEach(function (gap) {
      var li = el('li');
      li.appendChild(el('strong', '', gap[0])); li.appendChild(document.createTextNode(': ' + gap[1]));
      list.appendChild(li);
    });
    return list;
  }

  function render() {
    body.textContent = '';
    statusEl.textContent = ''; statusEl.className = 'fe-meta-status';
    var gaps = $('fe-meta-tab-gaps');
    gaps.textContent = 'Gaps';
    if (!data) { return; }
    if (data.stage) {
      body.appendChild(el('p', 'fe-muted', data.message || 'The record could not be described.'));
      return;
    }
    var state = data.record ? data.record.status : 'draft';
    statusEl.textContent = NOTE[state] || state;
    statusEl.className = 'fe-meta-status ' + state;
    if (data.completeness.length) { gaps.textContent = 'Gaps (' + data.completeness.length + ')'; }
    body.appendChild(view === 'dc' ? showDc() : view === 'rad' ? showRad() : showGaps());
    if (stale) { statusEl.textContent += ' · changed since this was made'; statusEl.className += ' stale'; }
  }

  function selectView(next) {
    view = next;
    Array.prototype.forEach.call(tabs, function (t) { t.setAttribute('aria-selected', t.getAttribute('data-view') === next ? 'true' : 'false'); });
    render();
  }

  function load() {
    if (busy) { return; }
    busy = true; $('fe-meta-refresh').disabled = true;
    fetch('metadata.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ text: window.IPS_FE_TEXT() }) })
      .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
      .then(function (d) {
        if (d.success === false) { throw new Error(d.error || 'refused'); }
        data = d; stale = false; render();
      })
      .catch(function (e) { data = null; body.textContent = ''; body.appendChild(el('p', 'fe-muted', 'Could not make the metadata: ' + e.message)); })
      .then(function () { busy = false; $('fe-meta-refresh').disabled = false; });
  }

  function open(on) {
    panel.hidden = !on;
    btn.setAttribute('aria-expanded', on ? 'true' : 'false');
    if (on) { load(); }
    fit();
  }

  btn.addEventListener('click', function () { open(panel.hidden); });
  $('fe-meta-close').addEventListener('click', function () { open(false); btn.focus(); });
  $('fe-meta-refresh').addEventListener('click', load);
  Array.prototype.forEach.call(tabs, function (t) { t.addEventListener('click', function () { selectView(t.getAttribute('data-view')); }); });
  window.addEventListener('ipsfe:changed', function () { if (!panel.hidden && data && !stale) { stale = true; render(); } });
  window.addEventListener('ipsfe:saved', function () { if (!panel.hidden) { load(); } });
}());
