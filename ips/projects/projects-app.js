/* The Projects page (projects/index.php): the projects, the open project's details and its files with role, label, note, order and removal. Data comes from list.php and
   files.php; writes post to save.php, delete.php, update_link.php, move.php and unlink.php (same-origin, with the user's CSRF token in X-CSRF). Vue is the base shell's own copy. */
(function () {
  'use strict';
  var cfg = window.IPS_PROJECTS;
  if (!cfg || !window.Vue) { return; }

  function post(url, fields) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(fields) })
      .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
      .catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  function get(url) {
    return fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
      .catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }
  var blank = function () { return { id: 0, title: '', kind: 'artefact', status: 'active', notes: '', created_by: cfg.user }; };

  Vue.createApp({
    data: function () {
      return { projects: [], kinds: [], statuses: [], roles: [], loaded: false, listError: '', query: '', showArchived: false,
               current: null, form: null, formError: '', busy: false, files: [], filesLoaded: false, filesError: '', armed: null };
    },
    computed: {
      shown: function () {
        var q = this.query.trim().toLowerCase(), all = this.showArchived, cur = this.current;
        return this.projects.filter(function (p) { return (all || p.status !== 'archived' || (cur && cur.id === p.id)) && (!q || p.title.toLowerCase().indexOf(q) >= 0); });
      },
      canDelete: function () { return !!this.form && (cfg.admin || this.form.created_by === cfg.user); }
    },
    methods: {
      kindLabel: function (id) { var k = this.kinds.filter(function (x) { return x.id === id; })[0]; return k ? k.label : id; },
      filesHref: function (id) { return '../browser.php?project=' + encodeURIComponent(id); },
      // the folder a file sits in, as the Files page's address names it ("#files%2FA")
      folderHref: function (path) { return '../browser.php#' + encodeURIComponent(path.replace(/\/[^\/]*$/, '')); },
      load: function (then) {
        var self = this;
        get('list.php').then(function (d) {
          if (d.success) { self.projects = d.projects; self.kinds = d.kinds; self.statuses = d.statuses; self.roles = d.roles; self.listError = ''; } else { self.listError = d.error || 'The projects could not be read.'; }
          self.loaded = true;
          if (then) { then(); }
        });
      },
      open: function (id) {
        var self = this, p = this.projects.filter(function (x) { return x.id === id; })[0];
        if (!p) { return; }
        this.current = p; this.form = Object.assign({}, p); this.formError = ''; this.armed = null;
        this.files = []; this.filesLoaded = false; this.filesError = '';
        try { history.replaceState(null, '', '?id=' + id); } catch (e) { /* the address is a nicety */ }
        get('files.php?id=' + encodeURIComponent(id)).then(function (d) {
          if (self.current && self.current.id === id) {
            if (d.success) { self.files = d.files; } else { self.filesError = d.error || 'The files could not be read.'; }
            self.filesLoaded = true;
          }
        });
      },
      openNew: function () {
        this.current = null; this.form = blank(); this.formError = ''; this.files = []; this.armed = null;
        this.$nextTick(function () { var el = this.$refs.titleEl; if (el) { el.focus(); } }.bind(this));
      },
      cancelNew: function () { this.form = null; },
      save: function () {
        var self = this, f = self.form;
        self.busy = true; self.formError = '';
        post('save.php', { id: f.id, title: f.title, kind: f.kind, status: f.status, notes: f.notes }).then(function (d) {
          self.busy = false;
          if (!d.success) { self.formError = d.error || 'The project was not saved.'; return; }
          self.load(function () { self.open(d.project.id); });
        });
      },
      removeProject: function () {
        var self = this;
        if (self.armed !== 'project') { self.armed = 'project'; setTimeout(function () { if (self.armed === 'project') { self.armed = null; } }, 4000); return; }
        self.armed = null;
        post('delete.php', { id: self.form.id }).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The project was not deleted.'; return; }
          self.current = null; self.form = null; self.files = [];
          try { history.replaceState(null, '', location.pathname); } catch (e) { /* the address is a nicety */ }
          self.load();
        });
      },
      saveLink: function (f) {
        var self = this;
        self.filesError = '';
        post('update_link.php', { project_id: self.current.id, file_id: f.file_id, role: f.role, label: f.label, note: f.note }).then(function (d) {
          if (!d.success) { self.filesError = d.error || 'The change was not saved.'; self.open(self.current.id); }
        });
      },
      move: function (f, dir) {
        var self = this;
        post('move.php', { project_id: self.current.id, file_id: f.file_id, direction: dir }).then(function (d) {
          if (d.success) { self.files = d.files; } else { self.filesError = d.error || 'The file was not moved.'; }
        });
      },
      // Remove from the project (the file stays): the first click asks, the second removes
      unlink: function (f) {
        var self = this;
        if (self.armed !== f.file_id) { self.armed = f.file_id; setTimeout(function () { if (self.armed === f.file_id) { self.armed = null; } }, 4000); return; }
        self.armed = null; self.filesError = '';
        post('unlink.php', { project_id: self.current.id, file_id: f.file_id }).then(function (d) {
          if (!d.success) { self.filesError = d.error || 'The file was not removed.'; return; }
          self.files = self.files.filter(function (x) { return x.file_id !== f.file_id; });
          self.load();
        });
      }
    },
    mounted: function () {
      var self = this, want = parseInt((new URLSearchParams(location.search)).get('id'), 10);
      self.load(function () { if (want) { self.open(want); } });
    }
  }).mount('#pj-app');
}());
