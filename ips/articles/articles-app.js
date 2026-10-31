/* The Articles page (articles/index.php): the articles, the open article's form with its body in CKEditor (a plain box when CKEditor is not installed), the status and schedule for an
   administrator, the versions and a preview of what the sanitiser kept. Data from article_list.php and article_get.php; writes post to article_save.php, article_status.php,
   article_restore.php and article_remove.php (same-origin, the user's CSRF token in X-CSRF). The page never shows the editor's HTML as it is: the preview and a viewed version are the
   server's cleaned copy. A change that is not saved is not left without asking (an inline notice, no dialog). Vue is the base shell's own copy. */
(function () {
  'use strict';
  var cfg = window.IPS_ARTICLES;
  if (!cfg || !window.Vue) { return; }

  function reply(r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); }
  function post(url, fields) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(fields) })
      .then(reply).catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  function get(url) {
    return fetch(url, { credentials: 'same-origin' }).then(reply).catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  var editor = null; // not in the data: Vue would wrap it in a proxy and CKEditor's own calls would no longer reach it
  var FIELDS = ['title', 'summary', 'authors', 'section', 'issue_id', 'issue_page', 'slug', 'image', 'seo_title', 'seo_description', 'access'];
  var EMPTY = { id: 0, title: '', summary: '', authors: '', section: '', issue_id: null, issue_page: null, slug: '', image: '', seo_title: '', seo_description: '', access: 'open', body: '',
                status: 'draft', effective_status: 'draft', created_by: '', updated_by: '', updated_at: '', publish_at: null };
  // 2026-11-05T14:30:00Z -> 2026-11-05T14:30, the value of a datetime-local input (read as UTC)
  function toInput(iso) { return iso ? iso.replace(/:\d\dZ$/, '') : ''; }

  Vue.createApp({
    data: function () {
      return { articles: [], statuses: [], accessLevels: [], sections: [], issues: [], loaded: false, listError: '', query: '', form: null, snapshot: '', bodyText: '', termsText: '', revisions: [],
               formError: '', busy: false, armed: false, saved: false, preview: false, viewing: null, pendingOpen: null, statusChoice: 'draft', publishAt: '', statusError: '',
               admin: !!cfg.admin, hasEditor: !!(cfg.ckeditor && window.CKEDITOR) };
    },
    computed: {
      shown: function () {
        var q = this.query.trim().toLowerCase();
        return this.articles.filter(function (a) { return !q || [a.title, a.authors, a.section].join(' ').toLowerCase().indexOf(q) >= 0; });
      },
      canEdit: function () { return !!this.form && (cfg.admin || this.form.status === 'draft'); },
      canRemove: function () { return !!this.form && (cfg.admin || (this.form.created_by === cfg.user && this.form.status === 'draft')); },
      dirty: function () { return !!this.form && this.state() !== this.snapshot; }
    },
    methods: {
      state: function () {
        var f = this.form;
        return JSON.stringify([FIELDS.map(function (k) { return f[k] === null || f[k] === undefined ? '' : String(f[k]); }), this.bodyText, this.termsText]);
      },
      stateLabel: function (a) {
        if (a.status === 'scheduled') { return a.effective_status === 'published' ? 'Published (was scheduled)' : 'Scheduled ' + (a.publish_at || '').replace('T', ' ').replace(/:\d\dZ$/, ' UTC'); }
        return { draft: 'Draft', published: 'Published', unpublished: 'Unpublished' }[a.status] || a.status;
      },
      load: function (then) {
        var self = this;
        get('article_list.php').then(function (d) {
          if (d.success) { self.articles = d.articles; self.statuses = d.statuses; self.accessLevels = d.access; self.sections = d.sections; self.issues = d.issues; self.listError = ''; } else { self.listError = d.error || 'The articles could not be read.'; }
          self.loaded = true;
          if (then) { then(); }
        });
      },
      // the body editor is made the first time an article is shown (CKEditor needs its textarea visible to size itself) and then only given new text
      ensureEditor: function () {
        var self = this;
        if (!self.hasEditor || editor) { return; }
        CKEDITOR.disableAutoInline = true;
        editor = CKEDITOR.replace('article-body', {
          customConfig: '', versionCheck: false, removePlugins: 'scayt,wsc,exportpdf,easyimage,cloudservices,uploadimage,uploadfile', entities: true, height: 340, width: '100%',
          toolbar: [['Undo', 'Redo'], ['Format'], ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'], ['NumberedList', 'BulletedList', 'Blockquote'], ['Link', 'Unlink'], ['Image', 'Table', 'HorizontalRule', 'SpecialChar'], ['Source']]
        });
        editor.on('instanceReady', function () { self.readOnly(); });
        editor.on('change', function () { self.bodyText = editor.getData(); });
        editor.on('mode', function () { self.bodyText = editor.getData(); });
      },
      // a locked article is read-only in the editor too (only once the editor is ready: instanceReady calls this again)
      readOnly: function () { if (editor && editor.status === 'ready') { editor.setReadOnly(!this.canEdit); } },
      setBody: function (html) {
        var self = this;
        self.bodyText = html;
        if (editor) { editor.setData(html, { callback: function () { self.bodyText = editor.getData(); self.snapshot = self.state(); } }); }
      },
      fill: function (article, terms, revisions, options) {
        this.form = Object.assign({}, EMPTY, article);
        this.termsText = (terms || []).join(', ');
        this.revisions = revisions || [];
        this.statusChoice = article.status; this.publishAt = toInput(article.publish_at);
        this.formError = ''; this.statusError = ''; this.armed = false; this.viewing = null; this.pendingOpen = null;
        if (options) { this.sections = options.sections || this.sections; this.issues = options.issues || this.issues; }
        this.bodyText = article.body || '';
        this.snapshot = this.state();
        var self = this;
        Vue.nextTick(function () { self.ensureEditor(); self.readOnly(); self.setBody(article.body || ''); self.snapshot = self.state(); });
      },
      openNew: function () { this.go(0); },
      go: function (id) {
        if (this.dirty) { this.pendingOpen = id; return; }
        this.open(id);
      },
      discardAndGo: function () { var id = this.pendingOpen; this.snapshot = this.state(); this.pendingOpen = null; this.open(id); },
      open: function (id) {
        var self = this;
        self.saved = false; self.preview = false;
        if (!id) {
          self.fill(Object.assign({}, EMPTY, { created_by: cfg.user }), [], [], null);
          try { history.replaceState(null, '', 'index.php'); } catch (e) { /* a nicety */ }
          return Promise.resolve();
        }
        return get('article_get.php?id=' + encodeURIComponent(id)).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The article could not be read.'; return; }
          self.fill(d.article, d.terms, d.revisions, d);
          try { history.replaceState(null, '', '?id=' + id); } catch (e) { /* a nicety */ }
        });
      },
      save: function (thenGo) {
        var self = this, f = self.form, fields = { id: f.id, body: self.bodyText, terms: self.termsText };
        if (!f || self.busy) { return; }
        FIELDS.forEach(function (k) { fields[k] = f[k] === null || f[k] === undefined ? '' : f[k]; });
        if (!f.id) { delete fields.slug; }
        self.busy = true; self.formError = ''; self.saved = false;
        return post('article_save.php', fields).then(function (d) {
          self.busy = false;
          if (!d.success) { self.formError = d.error || 'The article could not be saved.'; self.pendingOpen = null; return; }
          var go = thenGo ? self.pendingOpen : null;
          self.fill(d.article, d.terms, d.revisions, null);
          self.saved = true;
          self.load();
          if (go !== null && go !== undefined) { self.open(go); }
        });
      },
      applyStatus: function () {
        var self = this, fields = { id: self.form.id, status: self.statusChoice };
        if (self.statusChoice === 'scheduled') { fields.publish_at = self.publishAt; }
        self.busy = true; self.statusError = '';
        post('article_status.php', fields).then(function (d) {
          self.busy = false;
          if (!d.success) { self.statusError = d.error || 'The status could not be changed.'; return; }
          self.form = Object.assign({}, self.form, { status: d.article.status, effective_status: d.article.effective_status, publish_at: d.article.publish_at, published_at: d.article.published_at, updated_by: d.article.updated_by, updated_at: d.article.updated_at });
          self.statusChoice = d.article.status; self.publishAt = toInput(d.article.publish_at);
          self.load();
        });
      },
      viewRevision: function (n) {
        var self = this;
        get('article_revision.php?id=' + self.form.id + '&revision=' + n).then(function (d) { self.viewing = d.success ? d.revision : null; if (!d.success) { self.formError = d.error; } });
      },
      restore: function (n) {
        var self = this;
        self.busy = true; self.formError = '';
        post('article_restore.php', { id: self.form.id, revision: n }).then(function (d) {
          self.busy = false;
          if (!d.success) { self.formError = d.error || 'The version could not be restored.'; return; }
          self.fill(d.article, d.terms, d.revisions, null);
          self.saved = true;
          self.load();
        });
      },
      remove: function () {
        var self = this;
        if (!self.armed) { self.armed = true; return; }
        post('article_remove.php', { id: self.form.id }).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The article could not be removed.'; self.armed = false; return; }
          self.form = null; self.snapshot = ''; self.armed = false;
          self.load();
        });
      }
    },
    mounted: function () {
      var self = this;
      window.addEventListener('beforeunload', function (e) { if (self.dirty) { e.preventDefault(); e.returnValue = ''; } });
      self.load(function () {
        var m = /[?&]id=(\d+)/.exec(location.search);
        if (m) { self.open(m[1]); }
      });
    }
  }).mount('#ar-app');
})();
