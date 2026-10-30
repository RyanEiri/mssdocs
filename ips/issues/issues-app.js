/* The Issues page (issues/index.php): the issues, the open issue's description and the state of its text, and a form to make a new one. Data comes from issue_list.php, issue_get.php and
   source_list.php; writes post to issue_create.php, issue_save.php, issue_extract.php and issue_remove.php (same-origin, with the user's CSRF token in X-CSRF). The correction screen (the PDF
   page beside its text: issue_page.php, issue_page_save.php) and the contents list (issue_toc_save.php) are on the open issue; a page whose text was changed and not saved is not left without asking. While the text of the open
   issue is being read (pending, running) the page asks again every two seconds. An administrator's page also checks, without credentials, that the PDF's direct address is not public (it is
   behind the nginx gate on the sites that have one) and says so when it is. Vue is the base shell's own copy. */
(function () {
  'use strict';
  var cfg = window.IPS_ISSUES;
  if (!cfg || !window.Vue) { return; }

  function reply(r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); }
  function post(url, fields) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(fields) })
      .then(reply).catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  function get(url) {
    return fetch(url, { credentials: 'same-origin' }).then(reply).catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  var FIELDS = ['series', 'label', 'issue_date', 'volume', 'number', 'external_url', 'notes', 'status', 'access'];
  var STATE = { pending: 'Waiting…', running: 'Reading…', done: '', 'no-text': 'Scanned', failed: 'Failed', none: '' };

  Vue.createApp({
    data: function () {
      return { issues: [], statuses: [], accessLevels: [], loaded: false, listError: '', query: '', current: null, form: null, pages: null, formError: '', busy: false, armed: false,
               creating: null, createError: '', reading: false, admin: !!cfg.admin, gateWarning: '', poll: null, finder: null, directUrl: null, correcting: null, toc: [], tocKinds: [], tocError: '', tocBusy: false, tocSaved: false,
               search: { q: '', busy: false, error: '', done: false, total: 0, results: [] } };
    },
    computed: {
      shown: function () {
        var q = this.query.trim().toLowerCase();
        return this.issues.filter(function (i) { return !q || [i.label, i.issue_date, i.volume, i.number, i.series].join(' ').toLowerCase().indexOf(q) >= 0; });
      },
      canEdit: function () { return !!this.form && (cfg.admin || this.form.status === 'draft'); },
      canRead: function () { return !!this.form && (cfg.admin || (this.form.created_by === cfg.user && this.form.status === 'draft')); },
      canRemove: function () { return !!this.form && (cfg.admin || (this.form.created_by === cfg.user && this.form.status === 'draft')); },
      dirty: function () { return !!this.correcting && this.correcting.text !== this.correcting.loaded; },
      correctStatus: function () {
        var c = this.correcting;
        if (!c) { return ''; }
        return c.corrected ? 'Corrected by ' + c.by + (c.at ? ' on ' + c.at : '') : 'The text read from the PDF';
      },
      pdfHref: function () { return this.form ? 'file.php?id=' + encodeURIComponent(this.form.id) : '#'; }
    },
    methods: {
      title: function (i) { return i.label || ('Issue ' + i.id); },
      statusLabel: function (id) { var s = this.statuses.filter(function (x) { return x.id === id; })[0]; return s ? s.label : id; },
      stateText: function (i) {
        if (!i.has_pdf) { return ''; }
        var t = STATE[i.extraction] || '';
        return i.extraction === 'done' ? i.page_count + (i.page_count === 1 ? ' page' : ' pages') : t;
      },
      size: function (n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; },
      load: function (then) {
        var self = this;
        get('issue_list.php').then(function (d) {
          if (d.success) { self.issues = d.issues; self.statuses = d.statuses; self.accessLevels = d.access; self.listError = ''; } else { self.listError = d.error || 'The issues could not be read.'; }
          self.loaded = true;
          if (then) { then(); }
        });
      },
      open: function (id) {
        var self = this;
        self.stopPolling(); self.creating = null; self.armed = false; self.formError = ''; self.gateWarning = ''; self.directUrl = null;
        return get('issue_get.php?id=' + encodeURIComponent(id)).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The issue could not be read.'; return; }
          self.show(d);
          self.correcting = null; self.tocError = ''; self.tocSaved = false;
          self.toc = (d.toc || []).map(function (e) { return { kind: e.kind, title: e.title, page_from: e.page_from, page_to: e.page_to, author: e.author }; });
          self.tocKinds = d.toc_kinds || [];
          try { history.replaceState(null, '', '?id=' + id); } catch (e) { /* the address is a nicety */ }
          self.startPolling();
          if (d.direct_url) { self.checkGate(d.direct_url); }
        });
      },
      show: function (d) {
        this.current = d.issue; this.form = Object.assign({}, d.issue); this.pages = d.pages;
        // keep the list row in step
        var at = this.issues.map(function (x) { return x.id; }).indexOf(d.issue.id);
        if (at >= 0) { this.issues.splice(at, 1, d.issue); }
      },
      // the PDF's direct address, asked for without credentials: 200 means a visitor could fetch it (a site without the nginx gate)
      checkGate: function (url) {
        var self = this;
        self.directUrl = url;
        fetch(url, { method: 'HEAD', credentials: 'omit', cache: 'no-store' }).then(function (r) {
          if (r.status === 200 && self.directUrl === url) {
            self.gateWarning = 'The direct address of this issue’s PDF is open to anyone on this server (it has no access gate for the archives folder). Do not keep an issue that is not public here.';
          }
        }).catch(function () { /* unreachable is not open */ });
      },
      startPolling: function () {
        var self = this;
        self.stopPolling();
        if (!self.form || (self.form.extraction !== 'pending' && self.form.extraction !== 'running')) { return; }
        var id = self.form.id;
        self.poll = setInterval(function () {
          get('issue_get.php?id=' + encodeURIComponent(id)).then(function (d) {
            if (!d.success || !self.current || self.current.id !== id) { return; }
            self.show(d);
            if (d.issue.extraction !== 'pending' && d.issue.extraction !== 'running') { self.stopPolling(); self.load(); }
          });
        }, 2000);
      },
      // the text search: pages that match, newest issue first, with a snippet; "Show more" asks for the next ones
      runSearch: function (fresh) {
        var self = this, s = self.search;
        if (!s.q.trim()) { return; }
        if (fresh) { s.results = []; s.total = 0; s.done = false; }
        s.busy = true; s.error = '';
        get('issue_search.php?q=' + encodeURIComponent(s.q) + '&offset=' + s.results.length).then(function (d) {
          s.busy = false;
          if (!d.success) { s.error = d.error || 'The search failed.'; return; }
          s.results = s.results.concat(d.results); s.total = d.total; s.done = true;
        });
      },
      // the PDF at a page, for the frame (relative) or a link
      pdfAt: function (page, link) { return 'file.php?id=' + encodeURIComponent(this.form.id) + '#page=' + page + (link ? '' : '&view=FitH'); },
      toggleCorrect: function () {
        if (this.correcting) { this.correcting = null; return; }
        this.correcting = { page: 1, pages: [], raw: '', loaded: '', text: '', corrected: false, by: '', at: '', busy: false, error: '', armed: false, pending: 0, showOriginal: false };
        this.loadPage(1);
      },
      loadPage: function (n) {
        var self = this, c = self.correcting;
        return get('issue_page.php?id=' + encodeURIComponent(self.form.id) + '&page=' + n).then(function (d) {
          if (self.correcting !== c) { return; }
          if (!d.success) { c.error = d.error || 'The page could not be read.'; return; }
          self.fillPage(d);
          c.pending = 0; c.error = ''; c.armed = false; c.showOriginal = false;
        });
      },
      fillPage: function (d) {
        var c = this.correcting, p = d.page;
        c.page = p.page; c.pages = d.pages; c.raw = p.text_raw; c.corrected = p.text_corrected !== null;
        c.loaded = p.text_corrected !== null ? p.text_corrected : p.text_raw; c.text = c.loaded; c.by = p.corrected_by; c.at = p.corrected_at;
        this.pages = Object.assign({}, this.pages, { corrected: d.pages.filter(function (x) { return x.corrected; }).length });
      },
      goTo: function (n) {
        var c = this.correcting;
        if (!c || n < 1 || n > c.pages.length || n === c.page) { return; }
        if (this.dirty) { c.pending = n; return; }
        this.loadPage(n);
      },
      saveCorrection: function (andGo) {
        var self = this, c = self.correcting, go = c.pending;
        c.busy = true; c.error = '';
        post('issue_page_save.php', { id: self.form.id, page: c.page, text: c.text }).then(function (d) {
          c.busy = false;
          if (!d.success) { c.error = d.error || 'The correction was not saved.'; return; }
          self.fillPage(d); c.pending = 0;
          if (andGo && go) { self.loadPage(go); }
        });
      },
      discardAndGo: function () { var c = this.correcting, n = c.pending; c.text = c.loaded; c.pending = 0; if (n) { this.loadPage(n); } },
      restoreCorrection: function () {
        var self = this, c = self.correcting;
        if (!c.armed) { c.armed = true; setTimeout(function () { c.armed = false; }, 4000); return; }
        c.armed = false; c.error = '';
        post('issue_page_save.php', { id: self.form.id, page: c.page, restore: '1' }).then(function (d) {
          if (!d.success) { c.error = d.error || 'The correction was not removed.'; return; }
          self.fillPage(d);
        });
      },
      addToc: function () { this.toc.push({ kind: 'article', title: '', page_from: '', page_to: '', author: '' }); this.tocSaved = false; },
      moveToc: function (i, by) { var t = this.toc, e = t.splice(i, 1)[0]; t.splice(i + by, 0, e); this.tocSaved = false; },
      saveToc: function () {
        var self = this, entries = self.toc.map(function (e) {
          return { kind: e.kind, title: e.title, author: e.author, page_from: e.page_from === '' || e.page_from === null ? '' : Number(e.page_from), page_to: e.page_to === '' || e.page_to === null ? '' : Number(e.page_to) };
        });
        self.tocBusy = true; self.tocError = ''; self.tocSaved = false;
        post('issue_toc_save.php', { id: self.form.id, entries: JSON.stringify(entries) }).then(function (d) {
          self.tocBusy = false;
          if (!d.success) { self.tocError = d.error || 'The contents were not saved.'; return; }
          self.toc = d.toc.map(function (e) { return { kind: e.kind, title: e.title, page_from: e.page_from, page_to: e.page_to, author: e.author }; });
          self.tocSaved = true;
        });
      },
      stopPolling: function () { if (this.poll) { clearInterval(this.poll); this.poll = null; } },
      openNew: function () {
        this.stopPolling(); this.current = null; this.form = null; this.createError = ''; this.gateWarning = '';
        this.creating = { source: 'pdf', q: '', files: [], searched: false, path: '', external_url: '', label: '', issue_date: '', volume: '', number: '', series: '' };
        try { history.replaceState(null, '', location.pathname); } catch (e) { /* nicety */ }
        this.findSources();
      },
      findSources: function () {
        var self = this, c = self.creating;
        if (!c) { return; }
        clearTimeout(self.finder);
        self.finder = setTimeout(function () {
          get('source_list.php?q=' + encodeURIComponent(c.q)).then(function (d) {
            if (self.creating !== c) { return; }
            c.files = d.success ? d.files : []; c.searched = true;
          });
        }, 200);
      },
      pick: function (f) {
        var c = this.creating;
        c.path = f.path;
        if (!c.label) { c.label = f.title || f.name.replace(/\.pdf$/i, ''); }
      },
      create: function () {
        var self = this, c = self.creating, fields = { label: c.label, issue_date: c.issue_date, volume: c.volume, number: c.number, series: c.series };
        if (c.source === 'pdf') { fields.path = c.path; } else { fields.external_url = c.external_url; }
        if (c.source === 'pdf' && !c.path) { self.createError = 'Choose a PDF from the file store.'; return; }
        self.busy = true; self.createError = '';
        post('issue_create.php', fields).then(function (d) {
          self.busy = false;
          if (!d.success) { self.createError = d.error || 'The issue was not made.'; return; }
          self.creating = null;
          self.load(function () { self.open(d.issue.id); });
        });
      },
      save: function () {
        var self = this, f = self.form, fields = { id: f.id };
        FIELDS.forEach(function (k) { fields[k] = f[k]; });
        self.busy = true; self.formError = '';
        post('issue_save.php', fields).then(function (d) {
          self.busy = false;
          if (!d.success) { self.formError = d.error || 'The issue was not saved.'; return; }
          self.show({ issue: d.issue, pages: self.pages });
        });
      },
      reread: function () {
        var self = this;
        self.reading = true; self.formError = '';
        post('issue_extract.php', { id: self.form.id }).then(function (d) {
          self.reading = false;
          if (!d.success) { self.formError = d.error || 'The text could not be read again.'; return; }
          self.show({ issue: d.issue, pages: self.pages });
          self.startPolling();
        });
      },
      remove: function () {
        var self = this;
        if (!self.armed) { self.armed = true; setTimeout(function () { self.armed = false; }, 4000); return; }
        self.armed = false;
        post('issue_remove.php', { id: self.form.id }).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The issue was not removed.'; return; }
          self.stopPolling(); self.current = null; self.form = null; self.pages = null; self.gateWarning = '';
          try { history.replaceState(null, '', location.pathname); } catch (e) { /* nicety */ }
          self.load();
        });
      }
    },
    mounted: function () {
      var self = this, want = parseInt((new URLSearchParams(location.search)).get('id'), 10);
      self.load(function () { if (want) { self.open(want); } });
    },
    beforeUnmount: function () { this.stopPolling(); }
  }).mount('#is-app');
}());
