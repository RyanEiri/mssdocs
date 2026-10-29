/* The Projects page (projects/index.php): the projects, the open project's details and its files with role, label, note, order and removal. Data comes from list.php and
   files.php; writes post to save.php, delete.php, update_link.php, move.php and unlink.php (same-origin, with the user's CSRF token in X-CSRF). Vue is the base shell's own copy.
   With units of description (cfg.units: units.sql has been run) a project also has an Archival description section (unit_get.php, unit_save.php, unit_remove.php) and the left
   list can show the Hierarchy (unit_tree.php); the rules the server applies (which level may sit under which) are repeated here only to offer sensible parents. */
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
  // which levels a unit of each level may sit under (the server's IPS_UNIT_PARENT_LEVELS decides; this only narrows the choices offered)
  var PARENT_LEVELS = { fonds: [], collection: [], series: ['fonds', 'collection', 'series'], file: ['fonds', 'collection', 'series'] };
  var UNIT_FIELDS = ['level', 'parent_id', 'ref_segment', 'extent', 'accruals', 'language', 'repository', 'admin_history', 'custodial_history', 'scope_content', 'access_note', 'terms_note', 'general_note', 'date_start', 'date_end', 'visibility'];
  var blank = function () { return { id: 0, title: '', kind: 'artefact', status: 'active', notes: '', created_by: cfg.user }; };

  Vue.createApp({
    data: function () {
      return { projects: [], kinds: [], statuses: [], roles: [], loaded: false, listError: '', query: '', showArchived: false,
               current: null, form: null, formError: '', busy: false, files: [], filesLoaded: false, filesError: '', armed: null,
               admin: !!cfg.admin, units: !!cfg.units, view: 'projects', tree: [], levels: [], visibilities: [], unit: null, info: null, unitError: '', unitBusy: false, previewKind: '', previewNote: '', exportPublished: false };
    },
    computed: {
      shown: function () {
        var q = this.query.trim().toLowerCase(), all = this.showArchived, cur = this.current;
        return this.projects.filter(function (p) { return (all || p.status !== 'archived' || (cur && cur.id === p.id)) && (!q || p.title.toLowerCase().indexOf(q) >= 0); });
      },
      // the units in tree order with their depth and parent, and by project id
      flat: function () {
        var out = [];
        (function walk(nodes, depth, parent) { nodes.forEach(function (n) { out.push(Object.assign({ depth: depth, parent: parent }, n)); walk(n.children, depth + 1, n.project_id); }); }(this.tree, 0, null));
        return out;
      },
      unitIndex: function () { var m = {}; this.flat.forEach(function (n) { m[n.project_id] = n; }); return m; },
      needsParent: function () { return !!this.unit && PARENT_LEVELS[this.unit.level] && PARENT_LEVELS[this.unit.level].length > 0; },
      // the units this one could sit under: of a level that may hold it, and not itself or anything below it
      parentOptions: function () {
        if (!this.unit || !this.form) { return []; }
        var allowed = PARENT_LEVELS[this.unit.level] || [], self = this, below = {}, id = this.form.id;
        (function mark(nodes, inside) { nodes.forEach(function (n) { var now = inside || n.project_id === id; if (now) { below[n.project_id] = true; } mark(n.children, now); }); }(this.tree, false));
        return this.flat.filter(function (n) { return !below[n.project_id] && allowed.indexOf(n.level) >= 0; });
      },
      exportHref: function () { return this.form ? 'unit_export.php?id=' + encodeURIComponent(this.form.id) + (this.exportPublished ? '&published=1' : '') : '#'; },
      canRemoveUnit: function () { return !!this.form && (cfg.admin || this.form.created_by === cfg.user); },
      canDelete: function () { return !!this.form && (cfg.admin || this.form.created_by === cfg.user); }
    },
    methods: {
      levelLabel: function (id) { var l = this.levels.filter(function (x) { return x.id === id; })[0]; return l ? l.label : id; },
      visLabel: function (id) { var v = this.visibilities.filter(function (x) { return x.id === id; })[0]; return v ? v.label : id; },
      unitOf: function (id) { return this.unitIndex[id] || null; },
      // what an empty inherited field shows: the value from the nearest unit above, and which unit it comes from
      hint: function (field) {
        var i = this.info && this.info.inherited && this.info.inherited[field];
        if (!i || !i.value) { return ''; }
        var from = this.unitIndex[i.from];
        return 'As above: ' + i.value + (from ? ' (' + from.title + ')' : '');
      },
      loadUnits: function (then) {
        var self = this;
        if (!self.units) { if (then) { then(); } return; }
        get('unit_tree.php').then(function (d) {
          if (d.success) { self.tree = d.units; self.levels = d.levels; self.visibilities = d.visibilities; }
          if (then) { then(); }
        });
      },
      // the open project's description, if it has one
      loadUnit: function (id) {
        var self = this;
        self.unit = null; self.info = null; self.unitError = ''; self.previewKind = ''; self.previewNote = '';
        if (!self.units || !self.unitIndex[id]) { return; } // the tree says which projects are units, so a plain project makes no request that would answer 404
        get('unit_get.php?id=' + encodeURIComponent(id)).then(function (d) {
          if (!d.success || !self.current || self.current.id !== id) { return; }
          self.setUnit(d);
        });
      },
      setUnit: function (d) {
        var u = {};
        UNIT_FIELDS.forEach(function (f) { u[f] = d.unit[f]; });
        u.title = d.unit.own_title;
        this.unit = u; this.info = d;
      },
      // the unit's Dublin Core or RAD description, made by the stylesheets on the server; the RAD XHTML is copied node by node (div, p, span only), never put in as markup
      preview: function (kind) {
        var self = this, body = self.$refs.previewBody;
        self.previewKind = kind; self.previewNote = '';
        if (body) { body.textContent = ''; }
        if (!kind || !self.form) { return; }
        get('unit_views.php?id=' + encodeURIComponent(self.form.id)).then(function (d) {
          if (self.previewKind !== kind || !body) { return; }
          if (!d.success) { self.previewNote = d.error || 'The preview could not be made.'; return; }
          if (!d.available) { self.previewNote = 'The preview needs the file editor module and PHP’s dom and xsl extensions.'; return; }
          body.textContent = '';
          if (kind === 'dc') {
            var table = document.createElement('table'); table.className = 'fe-meta-table';
            d.dc.forEach(function (row) {
              var tr = document.createElement('tr'), th = document.createElement('th'), td = document.createElement('td');
              th.textContent = row[0]; td.textContent = row[1]; tr.appendChild(th); tr.appendChild(td); table.appendChild(tr);
            });
            body.appendChild(table);
            return;
          }
          var xml = new DOMParser().parseFromString(d.rad || '<div/>', 'application/xml');
          (function copy(node, into) {
            for (var c = node.firstChild; c; c = c.nextSibling) {
              if (c.nodeType === 3) { into.appendChild(document.createTextNode(c.nodeValue)); continue; }
              if (c.nodeType !== 1 || ['div', 'p', 'span'].indexOf(c.localName.toLowerCase()) < 0) { continue; }
              var made = document.createElement(c.localName.toLowerCase()), cls = c.getAttribute('class'), rule = c.getAttribute('data-rule');
              if (cls && /^[a-z0-9 -]+$/.test(cls)) { made.className = cls; }
              if (rule && /^[0-9A-Za-z.]+$/.test(rule)) { made.setAttribute('data-rule', rule); made.title = 'RAD ' + rule; }
              copy(c, made); into.appendChild(made);
            }
          }(xml.documentElement, body));
        });
      },
      describe: function () {
        var u = { level: 'fonds', parent_id: null, title: '', visibility: 'draft' };
        UNIT_FIELDS.forEach(function (f) { if (!(f in u)) { u[f] = ''; } });
        this.unit = u; this.info = null; this.unitError = '';
      },
      saveUnit: function () {
        var self = this, u = self.unit, fields = { project_id: self.form.id, title: u.title };
        UNIT_FIELDS.forEach(function (f) { fields[f] = u[f] === null ? '' : u[f]; });
        self.unitBusy = true; self.unitError = '';
        post('unit_save.php', fields).then(function (d) {
          self.unitBusy = false;
          if (!d.success) { self.unitError = d.error || 'The description was not saved.'; return; }
          self.setUnit(d);
          self.loadUnits();
          if (self.previewKind) { self.preview(self.previewKind); }
        });
      },
      removeUnit: function () {
        var self = this;
        if (self.armed !== 'unit') { self.armed = 'unit'; setTimeout(function () { if (self.armed === 'unit') { self.armed = null; } }, 4000); return; }
        self.armed = null; self.unitError = '';
        post('unit_remove.php', { project_id: self.form.id }).then(function (d) {
          if (!d.success) { self.unitError = d.error || 'The description was not removed.'; return; }
          self.unit = null; self.info = null;
          self.loadUnits();
        });
      },
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
        this.loadUnit(id);
        try { history.replaceState(null, '', '?id=' + id); } catch (e) { /* the address is a nicety */ }
        get('files.php?id=' + encodeURIComponent(id)).then(function (d) {
          if (self.current && self.current.id === id) {
            if (d.success) { self.files = d.files; } else { self.filesError = d.error || 'The files could not be read.'; }
            self.filesLoaded = true;
          }
        });
      },
      openNew: function () {
        this.current = null; this.form = blank(); this.formError = ''; this.files = []; this.armed = null; this.unit = null; this.info = null;
        this.$nextTick(function () { var el = this.$refs.titleEl; if (el) { el.focus(); } }.bind(this));
      },
      cancelNew: function () { this.form = null; },
      save: function () {
        var self = this, f = self.form;
        self.busy = true; self.formError = '';
        post('save.php', { id: f.id, title: f.title, kind: f.kind, status: f.status, notes: f.notes }).then(function (d) {
          self.busy = false;
          if (!d.success) { self.formError = d.error || 'The project was not saved.'; return; }
          self.load(function () { self.loadUnits(function () { self.open(d.project.id); }); });
        });
      },
      removeProject: function () {
        var self = this;
        if (self.armed !== 'project') { self.armed = 'project'; setTimeout(function () { if (self.armed === 'project') { self.armed = null; } }, 4000); return; }
        self.armed = null;
        post('delete.php', { id: self.form.id }).then(function (d) {
          if (!d.success) { self.formError = d.error || 'The project was not deleted.'; return; }
          self.current = null; self.form = null; self.files = []; self.unit = null; self.info = null;
          try { history.replaceState(null, '', location.pathname); } catch (e) { /* the address is a nicety */ }
          self.load(); self.loadUnits();
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
      self.loadUnits(function () { self.load(function () { if (want) { self.open(want); } }); });
    }
  }).mount('#pj-app');
}());
