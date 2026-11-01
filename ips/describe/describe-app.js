/* The Describe page (describe/index.php): the records of a folder, the open one's questions, fields, photographs and a preview. Data comes from record_list.php and record_get.php; writes post to
   record_save.php, record_status.php, photo_assign.php and json/json-photo_rotate.php (same-origin, with the user's CSRF token in X-CSRF). Only the text fields are "saved" with the Save button: a
   photograph's move, role and turn go to the server at once and are never unsaved. A change that is not saved is not left without asking (an inline notice, no dialog). Vue is the base shell's own copy. */
(function () {
  'use strict';
  var cfg = window.IPS_DESCRIBE;
  if (!cfg || !window.Vue) { return; }

  function reply(r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); }
  function post(url, fields) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(fields) })
      .then(function (r) { return reply(r).then(function (d) { d.status = r.status; return d; }); }).catch(function () { return { success: false, error: 'The server could not be reached.', status: 0 }; });
  }
  function get(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) { return reply(r).then(function (d) { d.status = r.status; return d; }); }).catch(function () { return { success: false, error: 'The server could not be reached.', status: 0 }; });
  }
  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  var CONFIRM = {
    author: 'Confirm: no author is known', date: 'Confirm: it is undated', place: 'Confirm: no place on the title page', publisher: 'Confirm: no publisher on the title page',
    edition: 'Confirm the edition as written', volume: 'Confirm the numbers as written', title: 'Confirm: I supplied this title', photographs: 'Confirm: there is no photograph', 'title-page': 'Confirm: no title page survives'
  };
  var FOCUS = { author: '#dc-sec-author input', date: '#dc-date-text', place: '#dc-place', publisher: '#dc-publisher', edition: '#dc-edition', volume: '#dc-add-scope', title: '#dc-sec-title input', photographs: '#dc-sec-photographs summary', 'title-page': '#dc-sec-photographs select' };

  Vue.createApp({
    data: function () {
      return { dir: cfg.dir || '', loadedDir: '', folders: cfg.folders || [], records: [], tray: [], roles: {}, photoRoles: [], loaded: false, listError: '', onlyOpen: false,
               rec: null, form: null, notesText: '', snapshot: '', preview: null, busy: false, error: '', saved: false, pending: null, conflict: '', statusChoice: 'draft', trayRole: {}, trayOpen: true,
               confirmWords: CONFIRM };
    },
    computed: {
      shown: function () { var only = this.onlyOpen; return this.records.filter(function (r) { return !only || r.error || (r.open && r.open.length); }); },
      openQuestions: function () { return this.rec && this.rec.questions ? this.rec.questions : []; },
      recPhotos: function () { return this.rec && this.rec.photos ? this.rec.photos : []; },
      decisionList: function () { var d = (this.form && this.form.decisions) || {}; return Object.keys(d).map(function (k) { return { key: k, who: d[k].who, when: d[k].when, text: d[k].text }; }); },
      dirty: function () { return !!this.form && JSON.stringify(this.payload()) !== this.snapshot; },
      needsSplit: function () { return !!this.form && this.form.contributors.some(function (c) { return /[\/,&;]|\(|\bog\b|\band\b/i.test(c.name); }); },
      isChecked: {
        get: function () { return !!(this.form && this.form.checked); },
        set: function (v) { if (this.form) { this.form.checked = v ? (this.form.checked || { who: '', when: '' }) : null; } }
      }
    },
    methods: {
      isReady: function (r) { return !r.error && (!r.open || r.open.length === 0); },
      dot: function (r) { return r.error ? 'bad' : r.status === 'published' ? 'done' : r.open.length ? 'open' : 'ready'; },
      dotText: function (r) { return r.error ? 'cannot open' : r.open.length ? r.open.length + (r.open.length === 1 ? ' question' : ' questions') : r.status === 'published' ? 'published' : 'ready'; },
      isOpen: function (key) { return this.openQuestions.some(function (q) { return q.key === key; }); },
      payload: function () {
        var f = this.form;
        if (!f) { return null; }
        return { titles: f.titles, contributors: f.contributors, pseudonym: f.pseudonym, place: f.place, publisher: f.publisher, date: f.date, edition: f.edition, scopes: f.scopes, language: f.language,
                 notes: this.notesText.split('\n').map(function (n) { return n.trim(); }).filter(Boolean), marks: f.marks, binding: f.binding, condition: f.condition, decisions: f.decisions,
                 checked: f.checked, cataloguer_note: f.cataloguer_note };
      },
      url: function (endpoint, params) { return endpoint + '?' + new URLSearchParams(Object.assign({ dir: this.dir }, params || {})); },
      loadList: function () {
        var self = this;
        self.listError = '';
        return get(self.url('record_list.php')).then(function (d) {
          if (!d.success) { self.listError = d.error || 'The records could not be listed.'; self.records = []; self.tray = []; self.loaded = true; return; }
          self.records = d.records; self.tray = d.tray; self.roles = d.roles; self.photoRoles = d.photo_roles; self.loadedDir = self.dir; self.loaded = true;
        });
      },
      changeFolder: function () {
        var to = this.dir;
        if (this.dirty) { this.dir = this.loadedDir; this.pending = { folder: to }; return; }
        this.enterFolder(to);
      },
      enterFolder: function (to) {
        this.dir = to; this.rec = null; this.form = null; this.records = []; this.tray = []; this.loaded = false; this.error = ''; this.conflict = ''; this.pending = null;
        try { history.replaceState(null, '', to ? '?dir=' + encodeURIComponent(to) : 'index.php'); } catch (e) { /* a nicety */ }
        if (to) { return this.loadList(); }
      },
      go: function (id) { if (this.dirty) { this.pending = { record: id }; return; } this.open(id); },
      discardAndGo: function () { var p = this.pending; this.snapshot = JSON.stringify(this.payload()); this.pending = null; if (p && p.folder !== undefined) { this.enterFolder(p.folder); } else if (p) { this.open(p.record); } },
      open: function (id) {
        var self = this;
        self.error = ''; self.saved = false; self.conflict = ''; self.pending = null;
        return get(self.url('record_get.php', { id: id })).then(function (d) {
          if (!d.success) { self.rec = { id: id, error: d.error || 'The record could not be read.' }; self.form = null; return; }
          self.adopt(d.record, d.preview, true);
          try { history.replaceState(null, '', '?dir=' + encodeURIComponent(self.dir) + '&id=' + encodeURIComponent(id)); } catch (e) { /* a nicety */ }
        });
      },
      reload: function () { var id = this.rec && this.rec.id; this.conflict = ''; if (id) { this.snapshot = ''; this.open(id); } },
      // take a record the server sent: all of it when `all`, else only what a photograph operation changes (the photographs and the etag), so unsaved text edits stay
      adopt: function (view, preview, all) {
        this.rec = view;
        if (preview !== undefined && preview !== null) { this.preview = preview; }
        if (all) {
          this.form = clone(view.struct);
          this.notesText = (view.struct.notes || []).join('\n');
          this.statusChoice = view.struct.status;
          this.snapshot = JSON.stringify(this.payload());
        } else {
          this.form.photos = clone(view.struct.photos);
        }
        this.syncList(view);
      },
      syncList: function (view) {
        var i = this.records.findIndex(function (r) { return r.id === view.id; }), s = view.struct;
        if (i >= 0 && s) { this.records.splice(i, 1, { id: view.id, title: s.titles[0] ? s.titles[0].text : view.id, reference: s.reference, status: s.status, open: view.open, photos: s.photos.length, checked: s.checked !== null, etag: view.etag }); }
      },
      save: function (thenGo) {
        var self = this;
        if (!self.form || self.busy) { return; }
        self.busy = true; self.error = ''; self.saved = false;
        return post('record_save.php', { dir: self.dir, id: self.rec.id, etag: self.rec.etag, struct: JSON.stringify(self.payload()) }).then(function (d) {
          self.busy = false;
          if (d.status === 409) { self.conflict = d.error; self.pending = null; return; }
          if (!d.success) { self.error = d.error || 'The record could not be saved.'; self.pending = null; return; }
          var go = thenGo ? self.pending : null;
          self.adopt(d.record, d.preview, true);
          self.saved = true;
          if (go) { self.pending = null; if (go.folder !== undefined) { self.enterFolder(go.folder); } else { self.open(go.record); } }
        });
      },
      confirm: function (key) { if (!this.form) { return; } this.form.decisions = Object.assign({}, this.form.decisions, (function (o) { o[key] = { text: CONFIRM[key].replace(/^Confirm:? ?/, 'Confirmed: ') }; return o; })({})); return this.save(false); },
      unconfirm: function (key) { var d = Object.assign({}, this.form.decisions); delete d[key]; this.form.decisions = d; return this.save(false); },
      applyStatus: function () {
        var self = this;
        self.busy = true; self.error = '';
        return post('record_status.php', { dir: self.dir, id: self.rec.id, etag: self.rec.etag, status: self.statusChoice }).then(function (d) {
          self.busy = false;
          if (d.status === 409) { self.conflict = d.error; return; }
          if (!d.success) { self.error = d.error || 'The status could not be changed.'; return; }
          self.rec = d.record; self.form.status = d.record.struct.status; self.statusChoice = d.record.struct.status; self.syncList(d.record);
        });
      },
      focusField: function (key) {
        var el = document.querySelector(FOCUS[key] || '#dc-form');
        if (el) { el.scrollIntoView({ block: 'center' }); if (el.focus) { el.focus(); } }
      },
      splitNames: function () {
        var out = [], pseudonym = this.form.pseudonym;
        this.form.contributors.forEach(function (c) {
          var name = c.name, role = c.role;
          var pen = /\(\s*pen name of\s+([^)]+)\)/i.exec(name);
          if (pen) { pseudonym = pseudonym || pen[1].trim(); name = name.replace(pen[0], ''); }
          var m = /\(\s*(eds?|editors?)\.?\s*\)\s*$/i.exec(name);
          if (m) { role = 'editor'; name = name.replace(m[0], ''); }
          m = /\(\s*(transl?|translators?)\.?\s*\)\s*$/i.exec(name);
          if (m) { role = 'translator'; name = name.replace(m[0], ''); }
          name.split(/\s*[\/;&,]\s*|\s+(?:og|and)\s+/i).map(function (n) { return n.trim(); }).filter(Boolean).forEach(function (n) { out.push({ name: n, role: role, resp: c.resp || '' }); });
        });
        this.form.contributors = out; this.form.pseudonym = pseudonym;
      },
      turnStyle: function (p) {
        var t = p.rotation || 0;
        if (!t) { return null; }
        if (t === 180) { return { transform: 'rotate(180deg)' }; }
        return { position: 'absolute', left: '50%', top: '50%', width: '75%', height: '133.3334%', transform: 'translate(-50%, -50%) rotate(' + t + 'deg)' };
      },
      // photographs: each goes to the server at once
      photoOp: function (fields) {
        var self = this;
        self.busy = true; self.error = '';
        return post('photo_assign.php', Object.assign({ dir: self.dir }, fields)).then(function (d) {
          self.busy = false;
          if (!d.success) { self.error = d.error || 'The photograph could not be moved.'; return false; }
          d.records.forEach(function (v) { if (v.id === self.rec.id) { self.adopt(v, null, false); } else { self.syncList(v); } });
          self.tray = d.tray;
          return true;
        });
      },
      setRole: function (p, role) { return this.photoOp({ address: p.path, from: this.rec.id, to: this.rec.id, role: role }); },
      toTray: function (p) { return this.photoOp({ address: p.path, from: this.rec.id, to: '' }); },
      fromTray: function (p) { return this.photoOp({ address: p.path, from: '', to: this.rec.id, role: this.trayRole[p.name] || 'title-page' }); },
      rotate: function (p, delta) {
        var self = this;
        if (!p.store_dir) { return; }
        self.busy = true; self.error = '';
        return post('../json/json-photo_rotate.php', { dir: p.store_dir, name: p.name, turn: String(delta) }).then(function (d) {
          self.busy = false;
          if (!d.success) { self.error = d.error || 'The turn could not be saved.'; return; }
          p.rotation = d.rotation;
        });
      }
    },
    mounted: function () {
      var self = this;
      window.addEventListener('beforeunload', function (e) { if (self.dirty) { e.preventDefault(); e.returnValue = ''; } });
      if (self.dir) {
        self.loadList().then(function () {
          var m = /[?&]id=([^&]+)/.exec(location.search);
          if (m) { self.open(decodeURIComponent(m[1])); }
        });
      }
    }
  }).mount('#dc-app');
})();
