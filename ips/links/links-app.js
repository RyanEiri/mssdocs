/* The Links page (links/index.php): the links by group, a filter, the Add / Edit dialog, Move up / down and a two-click Delete. The list comes from list.php;
   writes post to save.php, move.php and delete.php (same-origin, with the user's CSRF token in X-CSRF). Vue is the base shell's own copy. */
(function () {
  'use strict';
  var cfg = window.IPS_LINKS;
  if (!cfg || !window.Vue) { return; }

  function post(url, fields) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(fields) })
      .then(function (r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); })
      .catch(function () { return { success: false, error: 'The server could not be reached.' }; });
  }

  Vue.createApp({
    data: function () {
      return { groups: [], links: [], loaded: false, listError: '', query: '', armed: null, form: null, formError: '', busy: false, maxAlso: cfg.maxAlso };
    },
    computed: {
      // the groups in their order with their links; a filter hides the links that do not match (and moving is off while it is on, as the order would not show)
      shown: function () {
        var q = this.query.trim().toLowerCase(), links = this.links;
        return this.groups.map(function (g) {
          return { id: g.id, name: g.name, links: links.filter(function (l) {
            return l.group_id === g.id && (!q || (l.name + ' ' + l.description + ' ' + l.url).toLowerCase().indexOf(q) >= 0);
          }) };
        }).filter(function (g) { return g.links.length; });
      }
    },
    methods: {
      // only a web address becomes a link: a row written some other way (SQL by hand) cannot put script in an href
      href: function (url) { return /^https?:\/\//i.test(url) ? url : null; },
      load: function () {
        var self = this;
        fetch('list.php', { credentials: 'same-origin' }).then(function (r) { return r.json().then(function (d) { return [r.ok, d]; }); }).then(function (res) {
          if (res[0] && res[1].success) { self.groups = res[1].groups; self.links = res[1].links; self.listError = ''; } else { self.listError = res[1].error || 'The links could not be read.'; }
          self.loaded = true;
        }).catch(function () { self.listError = 'The server could not be reached.'; self.loaded = true; });
      },
      openForm: function (l) {
        this.formError = '';
        this.form = l
          ? { id: l.id, name: l.name, url: l.url, description: l.description, group_id: l.group_id, also: l.also.map(function (a) { return [a[0], a[1]]; }), hidden: l.hidden }
          : { id: 0, name: '', url: '', description: '', group_id: this.groups.length ? this.groups[0].id : 0, also: [], hidden: false };
        this.$nextTick(function () { var el = this.$refs.nameEl; if (el) { el.focus(); } }.bind(this));
      },
      save: function () {
        var self = this, f = self.form;
        self.busy = true; self.formError = '';
        post('save.php', { id: f.id, group_id: f.group_id, name: f.name, url: f.url, description: f.description, hidden: f.hidden ? '1' : '0',
                           also: JSON.stringify(f.also.filter(function (a) { return a[0].trim() !== '' || a[1].trim() !== ''; })) })
          .then(function (d) {
            self.busy = false;
            if (d.success) { self.form = null; self.load(); } else { self.formError = d.error || 'The link was not saved.'; }
          });
      },
      move: function (l, dir) {
        var self = this;
        post('move.php', { id: l.id, dir: dir }).then(function (d) { if (d.success) { self.load(); } else { self.listError = d.error || 'The link was not moved.'; } });
      },
      // Delete: the first click asks ("Sure?"), the second deletes
      remove: function (l) {
        var self = this;
        if (self.armed !== l.id) {
          self.armed = l.id;
          setTimeout(function () { if (self.armed === l.id) { self.armed = null; } }, 4000);
          return;
        }
        self.armed = null; self.listError = '';
        post('delete.php', { id: l.id }).then(function (d) { if (d.success) { self.load(); } else { self.listError = d.error || 'The link was not deleted.'; } });
      }
    },
    mounted: function () { this.load(); }
  }).mount('#ln-app');
}());
