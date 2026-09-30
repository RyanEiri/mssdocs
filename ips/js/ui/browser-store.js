/* IPS file browser, store: the folder tree from json/json-file_info.php (one recursive load), and everything derived from
   it: the current folder, selection, search, filter, sort and view. One reactive object, IPS.browser.store, shared by
   the browser components. The server stays the source of truth: after a change the tree is loaded again. */
(function () {
  "use strict";
  const { reactive, computed } = Vue;
  const IPS = window.IPS, CFG = IPS.cfg, U = IPS.util;
  const ROOT = "files";

  // ---- paths ("files/Photographs/Eyrarbakki", always relative to upload/) ------------------------------------------
  const nameOf = (p) => p.slice(p.lastIndexOf("/") + 1);
  const parentOf = (p) => (p.indexOf("/") < 0 ? "" : p.slice(0, p.lastIndexOf("/")));
  const join = (dir, name) => dir + "/" + name;
  const within = (p, dir) => p === dir || p.startsWith(dir + "/");
  // The classic page put "../upload/files/x" in the hash; take whatever follows the first "files" segment.
  function cleanPath(p) {
    const m = /(^|\/)files(\/.*)?$/i.exec(String(p || ""));
    return m ? "files" + (m[2] || "") : ROOT;
  }
  const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: "base" });
  const byName = (a, b) => collator.compare(a.name, b.name);

  function load() {
    store.loading = true;
    return IPS.postForm("json/json-file_info.php", { recursive: "1" })
      .then((d) => {
        if (d && d.success === false) throw new Error("refused");
        store.tree = d;
        store.error = "";
        store.ready = true;
        if (!(store.cwd in nodes.value)) store.cwd = ROOT;
        store.selection = store.selection.filter((p) => p in nodes.value);
        loadTitles();
        return d;
      })
      .catch((e) => { store.error = "The file list couldn’t be loaded (" + e.message + ")."; })
      .finally(() => (store.loading = false));
  }

  // Catalogue titles of the files directly inside the current folder (the listing endpoint has none).
  function loadTitles() {
    const dir = store.cwd;
    return IPS.getJSON("json/json-file_titles.php", { dir })
      .then((d) => { if (d && d.titles) Object.assign(store.titles, d.titles); })
      .catch(() => { /* titles are a nicety: the browser works without them */ });
  }

  function saved(key, fallback) { try { return localStorage.getItem("ips.browser." + key) || fallback; } catch (e) { return fallback; } }
  function save(key, value) { try { localStorage.setItem("ips.browser." + key, value); } catch (e) { /* private mode */ } }

  const store = reactive({
    tree: null, loading: false, ready: false, error: "", titles: {},
    cwd: cleanPath(decodeURIComponent((location.hash || "").slice(1))),
    expanded: { [ROOT]: true },
    selection: [], anchor: null,
    view: saved("view", "grid"), sortKey: "name", sortDir: 1, filter: "all", query: "",
    panelOpen: saved("panel", "1") === "1",
  });

  // path -> node for every folder and file except the thumbnail folders (they hold generated copies, not content)
  const nodes = computed(() => {
    const map = {};
    (function walk(node, parent) {
      if (!node || node.name === "thumbnail") return;
      map[node.path] = Object.assign({}, node, { parent, folder: node.type === "folder" });
      if (node.type === "folder") (node.items || []).forEach((c) => walk(c, node.path));
    })(store.tree, "");
    return map;
  });
  const children = (path) => {
    const n = nodes.value[path];
    return n && n.folder ? (n.items || []).filter((c) => c.name !== "thumbnail").map((c) => nodes.value[c.path]).filter(Boolean) : [];
  };
  function stats(path) {
    let folders = 0, files = 0, size = 0;
    (function walk(p) { children(p).forEach((c) => { if (c.folder) { folders++; walk(c.path); } else { files++; size += c.size || 0; } }); })(path);
    return { folders, files, size };
  }

  const inFolder = computed(() => children(store.cwd));
  const searching = computed(() => store.query.trim().length > 0);
  const matches = computed(() => {
    const q = store.query.trim().toLowerCase();
    return q ? Object.values(nodes.value).filter((n) => n.path !== ROOT && n.name.toLowerCase().includes(q)) : [];
  });
  const typeOf = (n) => (n.folder ? "folder" : U.typeOf(n.name));
  const FILTERS = { all: null, folder: "folder", image: "image", doc: "doc", xml: "xml", audio: "audio", archive: "archive" };
  const visible = computed(() => {
    const want = FILTERS[store.filter];
    const list = (searching.value ? matches.value : inFolder.value).filter((n) => !want || typeOf(n) === want);
    const key = store.sortKey, dir = store.sortDir;
    const val = (n) => (key === "size" ? (n.folder ? stats(n.path).files : n.size || 0) : key === "modified" ? n.modified || 0 : key === "type" ? (n.folder ? "" : U.extOf(n.name)) : n.name);
    return list.slice().sort((a, b) => {
      if (a.folder !== b.folder) return a.folder ? -1 : 1; // folders always first
      const x = val(a), y = val(b);
      const c = typeof x === "number" ? x - y : collator.compare(String(x), String(y));
      return (c || collator.compare(a.name, b.name)) * dir;
    });
  });
  const crumbs = computed(() => {
    const out = [];
    let p = "";
    store.cwd.split("/").forEach((seg, i) => { p = i ? join(p, seg) : seg; out.push({ path: p, label: i ? seg : "All files" }); });
    return out;
  });

  // ---- navigation ---------------------------------------------------------------------------------------------
  function go(path) {
    path = cleanPath(path);
    const hash = "#" + encodeURIComponent(path);
    if (location.hash !== hash) location.hash = hash; // the hashchange handler below does the rest
    else apply(path);
  }
  function apply(path) {
    if (store.cwd !== path) { store.selection = []; store.anchor = null; }
    store.cwd = path;
    store.query = "";
    for (let p = path; p; p = parentOf(p)) store.expanded[p] = true; // the tree opens down to the current folder
    if (store.ready) loadTitles();
  }
  window.addEventListener("hashchange", () => apply(cleanPath(decodeURIComponent(location.hash.slice(1)))));

  // ---- selection ----------------------------------------------------------------------------------------------
  const isSelected = (p) => store.selection.includes(p);
  function select(path, ev) {
    const list = visible.value.map((n) => n.path);
    if (ev && ev.shiftKey && store.anchor && list.includes(store.anchor)) {
      const a = list.indexOf(store.anchor), b = list.indexOf(path);
      store.selection = list.slice(Math.min(a, b), Math.max(a, b) + 1);
    } else if (ev && (ev.ctrlKey || ev.metaKey)) {
      store.selection = isSelected(path) ? store.selection.filter((p) => p !== path) : store.selection.concat(path);
      store.anchor = path;
    } else {
      store.selection = [path];
      store.anchor = path;
    }
  }
  function toggle(path) { store.selection = isSelected(path) ? store.selection.filter((p) => p !== path) : store.selection.concat(path); store.anchor = path; }
  function selectAll() { store.selection = visible.value.map((n) => n.path); }
  function clearSelection() { store.selection = []; store.anchor = null; }
  function setSort(key) { if (store.sortKey === key) store.sortDir = -store.sortDir; else { store.sortKey = key; store.sortDir = 1; } }
  function setView(v) { store.view = v; save("view", v); }
  function togglePanel() { store.panelOpen = !store.panelOpen; save("panel", store.panelOpen ? "1" : "0"); }

  // ---- what the user may change -------------------------------------------------------------------------------
  // Administrators anywhere; everyone else only inside files/<their username> (the server enforces the same rule).
  const canWrite = (path) => CFG.admin || within(path, join(ROOT, CFG.user));
  const thumbUrl = (n) => CFG.base + "upload/" + join(join(parentOf(n.path), "thumbnail"), n.name);
  const fileUrl = (n) => CFG.base + "upload/" + n.path;

  const visibleClear = () => { store.query = ""; store.filter = "all"; };

  IPS.browser = Object.assign(IPS.browser || {}, {
    ROOT, store, load, loadTitles, go, apply, select, toggle, selectAll, clearSelection, setSort, setView, togglePanel, isSelected, visibleClear,
    nodes, children, stats, visible, inFolder, searching, matches, crumbs, typeOf, canWrite, thumbUrl, fileUrl,
    byName, path: { nameOf, parentOf, join, within, cleanPath },
  });
})();
