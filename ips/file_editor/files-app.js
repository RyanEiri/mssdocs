/* The Files page of the file editor module (file_editor/index.php): the files this user may open, grouped by kind, with a filter, the New file dialog and a two-click Delete.
   The list comes from list.php; creating posts to new.php and deleting to delete.php (same-origin, with the user's CSRF token in X-CSRF). */
(function () {
  'use strict';
  var cfg = window.IPS_FE;
  if (!cfg || !window.Vue) { return; }

  function sizeText(bytes) {
    var units = ['Bytes', 'KB', 'MB', 'GB'];
    if (!bytes) { return '0 Bytes'; }
    var i = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i)) + ' ' + units[i];
  }

  // the tree as groups of files, one group per kind of file; the folder a file is in (a user's own, or the top) is its "dir"
  function groups(tree) {
    return (tree.items || []).map(function (top) {
      var files = [];
      (function walk(items, folder) {
        items.forEach(function (d) {
          if (d.type === 'file') {
            files.push({ name: d.name, path: d.path, kind: d.kind, ext: d.name.split('.').pop().toLowerCase(), dir: folder, sizeText: sizeText(d.size), writable: d.writable === true, deletable: d.deletable === true });
          } else if (d.type === 'folder') { walk(d.items || [], d.name); }
        });
      }(top.items || [], 'shared'));
      return { name: top.label || top.name, note: top.name, files: files };
    });
  }

  Vue.createApp({
    data: function () {
      var kinds = cfg.types || [];
      var types = [{ id: 'all', label: 'All' }].concat(kinds.map(function (t) { return { id: t.id, label: t.label }; }));
      return { dirs: cfg.dirs, kinds: kinds, armed: null, deleteError: '', tree: [], query: '', type: 'all', types: types,
               isNew: false, newName: '', newType: kinds.length ? kinds[0].id : '', newError: '', busy: false };
    },
    computed: {
      // the extension a new file of the chosen type gets (the type's first)
      suffix: function () {
        var t = this.kinds.filter(function (k) { return k.id === this.newType; }.bind(this))[0];
        return t ? t.ext[0] : '';
      },
      shown: function () {
        var q = this.query.trim().toLowerCase(), t = this.type;
        return this.tree.map(function (g) {
          return Object.assign({}, g, { files: g.files.filter(function (f) {
            return (t === 'all' || f.kind === t) && (!q || f.name.toLowerCase().indexOf(q) >= 0 || f.dir.toLowerCase().indexOf(q) >= 0);
          }) });
        }).filter(function (g) { return g.files.length; });
      }
    },
    methods: {
      editUrl: function (f) { return 'edit.php?file=' + encodeURIComponent(f.path); },
      open: function (f) { window.location.href = this.editUrl(f); },
      load: function () {
        var self = this;
        fetch('list.php', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) { self.tree = groups(d); })
          .catch(function () { self.tree = []; });
      },
      // Delete: the first click asks ("Sure?"), the second moves the file to .deleted/ (server side) and the list is read again
      remove: function (f) {
        var self = this;
        if (self.armed !== f.path) {
          self.armed = f.path;
          setTimeout(function () { if (self.armed === f.path) { self.armed = null; } }, 4000);
          return;
        }
        self.armed = null; self.deleteError = '';
        fetch('delete.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ file: f.path }) })
          .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
          .then(function (d) { if (d.success) { self.load(); } else { self.deleteError = d.error || 'The file was not deleted.'; } })
          .catch(function () { self.deleteError = 'The server could not be reached.'; });
      },
      openNew: function () {
        this.isNew = true; this.newName = ''; this.newError = ''; this.newType = this.kinds.length ? this.kinds[0].id : '';
        this.$nextTick(function () { var el = this.$refs.nameEl; if (el) { el.focus(); } }.bind(this));
      },
      create: function () {
        var self = this;
        self.busy = true; self.newError = '';
        fetch('new.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ name: self.newName, type: self.newType }) })
          .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
          .then(function (d) {
            if (d.success) { window.location.href = d.edit; return; }
            self.newError = d.error || 'The file was not created.'; self.busy = false;
          })
          .catch(function () { self.newError = 'The server could not be reached.'; self.busy = false; });
      }
    },
    mounted: function () { this.load(); }
  }).mount('#fe-app');
}());
