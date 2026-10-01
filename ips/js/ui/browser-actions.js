/* IPS file browser, actions: the calls that read a file's catalogue entry and change files, each mapped onto the
   existing json/*.php endpoints (their contracts are noted beside each call). Paths are always "files/..." relative to
   upload/. After a change the tree is loaded again: the server is the source of truth. */
(function () {
  "use strict";
  const IPS = window.IPS, B = IPS.browser, st = B.store;
  const { parentOf, nameOf } = B.path;

  // What the server sent back as a readable message, whichever key it used.
  function problem(d, fallback) {
    if (!d) return fallback;
    const parts = [];
    const take = (v) => { if (typeof v === "string") parts.push(v.replace(/<[^>]+>/g, " ").trim()); else if (v && typeof v === "object") Object.values(v).forEach(take); };
    take(d.errors);
    take(d.db_errors);
    const text = parts.filter(Boolean).join(" ");
    return text || fallback;
  }

  // POST json-database_info.php {context: "file", fileName, dbDirName} -> the catalogue row, or "Table lookup failure"
  // when the file has none (a file put on the disk by other means). Cached per path until forced.
  function loadDetail(node, force) {
    const cached = st.details[node.path];
    if (cached && !force) return Promise.resolve(cached);
    st.details[node.path] = { loading: true };
    return IPS.postForm("json/json-database_info.php", { context: "file", fileName: node.name, dbDirName: parentOf(node.path) })
      .then((d) => {
        st.details[node.path] = d && d.success
          ? { found: true, id: d.fileId, type: d.fileType, url: d.fileUrl, title: d.fileTitle || "", description: d.fileDescription || "", date: d.fileDate }
          : { found: false };
      })
      .catch(() => { st.details[node.path] = { found: false, failed: true }; })
      .then(() => st.details[node.path]);
  }

  // The file's URL after a rename or move: everything up to "/upload/" stays, the rest becomes the new files/... path.
  // (The classic page rebuilt it from the file name alone, which left the URL pointing at the old folder after a move.)
  function newUrl(oldUrl, newPath) {
    const i = String(oldUrl || "").indexOf("/upload/");
    return i >= 0 ? oldUrl.slice(0, i + "/upload/".length) + newPath.split("/").map(encodeURIComponent).join("/") : oldUrl.replace(/[^/]*$/, "") + encodeURIComponent(nameOf(newPath));
  }

  // POST json-file_functions.php: title and description, and (when the name or folder changed) the rename or move.
  // Resolves {ok, message, path}; `path` is where the file is afterwards.
  function saveFile(node, form) {
    const d = st.details[node.path];
    const fields = {
      fileId: d.id, dbDirName: form.folder, fsDirName: form.folder,
      previousFileFolder: parentOf(node.path), previousFileName: node.name, fileName: form.name,
      previousFileTitle: d.title, fileTitle: form.title, previousFileDescription: d.description, fileDescription: form.description,
      fileUrl: d.url, newFileUrl: newUrl(d.url, form.folder + "/" + form.name),
    };
    return IPS.postForm("json/json-file_functions.php", fields).then((r) => {
      if (!r || r.success !== true) return { ok: false, message: problem(r, "The file couldn’t be saved.") };
      const path = form.folder + "/" + form.name;
      delete st.details[node.path];
      return B.load().then(() => ({ ok: true, path }));
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }

  const asFile = (n) => ({ dirname: parentOf(n.path), filename: n.name });

  // POST json-add_folder.php {path, folder}: creates `name` inside `parent` (and its thumbnail directory and database row).
  function addFolder(parent, name) {
    return IPS.postForm("json/json-add_folder.php", { path: parent, folder: name }).then((r) => {
      if (!r || r.success !== true) return { ok: false, message: problem(r, "The folder couldn’t be created.") };
      return B.load().then(() => ({ ok: true, path: parent + "/" + name }));
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }

  const join = (a, b) => a + "/" + b;
  const label = (nodes) => (nodes.length === 1 ? "“" + nodes[0].name + "”" : IPS.util.plural(nodes.length, "item"));
  const where = (p) => (p === B.ROOT ? "All files" : p.replace(/^files\//, "").split("/").join(" / "));

  // POST (JSON) json-move_file.php {files: [{dirname, filename}], folders: [path], moveToFolder, dbMoveToFolder}.
  function moveItems(nodes, dest) {
    const body = { files: nodes.filter((n) => !n.folder).map(asFile), folders: nodes.filter((n) => n.folder).map((n) => n.path), moveToFolder: dest, dbMoveToFolder: dest };
    if (!body.files.length) delete body.files;
    if (!body.folders.length) delete body.folders;
    return IPS.postJSON("json/json-move_file.php", body).then((r) => {
      if (!r || r.success !== true) return { ok: false, message: problem(r, "The move didn’t go through.") };
      nodes.forEach((n) => delete st.details[n.path]);
      return B.load().then(() => ({ ok: true }));
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }

  // A move with its undo: the toast offers to put every item back in the folder it came from.
  function moveWithUndo(nodes, dest) {
    const from = nodes.map((n) => ({ name: n.name, parent: parentOf(n.path) }));
    return moveItems(nodes, dest).then((r) => {
      if (!r.ok) return r;
      IPS.toast("Moved " + label(nodes) + " to " + where(dest), { ms: 10000, undo: () => undoMove(from, dest) });
      return r;
    });
  }
  async function undoMove(from, dest) {
    const parents = [...new Set(from.map((f) => f.parent))];
    for (const parent of parents) {
      const back = from.filter((f) => f.parent === parent).map((f) => B.nodes.value[join(dest, f.name)]).filter(Boolean);
      const r = back.length ? await moveItems(back, parent) : { ok: true };
      if (!r.ok) { IPS.toast("Couldn’t undo: " + r.message, { icon: "alert-circle" }); return; }
    }
    IPS.toast("Moved back");
  }

  // A new folder's undo (administrators only: removing is theirs): the empty folder is taken away again.
  function undoAddFolder(path) {
    const node = B.nodes.value[path];
    if (!node) return;
    if (B.stats(path).files || B.children(path).length) { IPS.toast("Not undone: the folder is no longer empty.", { icon: "alert-circle" }); return; }
    const inside = st.cwd === path || st.cwd.startsWith(path + "/"); // load() sends a vanished folder's viewer to the root
    removeItems([node]).then((r) => {
      if (r.ok) { if (inside) B.go(parentOf(path)); IPS.toast("Removed “" + node.name + "”"); } else IPS.toast("Couldn’t undo: " + r.message, { icon: "alert-circle" });
    });
  }

  // POST json-remove_file.php {files: [{dirname, filename}], folders: [path]} (administrators only; folders must be empty).
  function removeItems(nodes) {
    const body = { files: nodes.filter((n) => !n.folder).map(asFile), folders: nodes.filter((n) => n.folder).map((n) => n.path) };
    return IPS.postForm("json/json-remove_file.php", body).then((r) => {
      if (!r || r.success !== true) return { ok: false, message: problem(r, "The removal didn’t go through.") };
      nodes.forEach((n) => delete st.details[n.path]);
      return B.load().then(() => ({ ok: true }));
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }

  // Zip a folder in the background: POST json-folder_functions.php {folderToZip, token} starts a worker, json-progress.php
  // {token} reports {percent, count, total, done, filename, error}; json-download.php?token=&filename= serves the result.
  const newToken = () => "z" + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
  function startZip(path) {
    const token = newToken();
    return IPS.postForm("json/json-folder_functions.php", { folderToZip: path, token })
      .then((r) => (r && r.success ? { ok: true, token, total: r.count } : { ok: false, message: (r && r.error) || problem(r, "The zip couldn’t be started.") }))
      .catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }
  const zipProgress = (token) => IPS.postForm("json/json-progress.php", { token }).catch(() => ({ percent: 0, error: "Couldn’t reach the server." }));
  const zipUrl = (token, filename) => IPS.cfg.base + "json/json-download.php?token=" + encodeURIComponent(token) + "&filename=" + encodeURIComponent(filename);

  // Batch edit (administrators): POST json-database_info.php {context: "folder", dirName} lists the folder's catalogue rows,
  // POST json-folder_files.php saves names, titles and descriptions of those rows in one go (a changed name renames the file).
  function loadFolderFiles(path) {
    return IPS.postForm("json/json-database_info.php", { context: "folder", dirName: path }).then((r) => {
      if (!r || r.success !== true) return { ok: false, message: problem(r, "This folder’s files couldn’t be listed.") };
      const files = Object.values(r.files || {}).map((f) => ({ id: f.id, url: f.url, name: nameOf(f.name), original: nameOf(f.name), title: f.title || "", description: f.description || "" }));
      return { ok: true, folderId: r.folderId, folderName: r.folderName, files };
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }
  function saveFolderFiles(folder, rows) {
    const filesInFolder = {};
    rows.forEach((f, i) => { filesInFolder[i] = { id: f.id, url: f.name === f.original ? f.url : f.url.replace(/[^/]*$/, "") + encodeURIComponent(f.name), name: f.name, title: f.title, description: f.description }; });
    return IPS.postForm("json/json-folder_files.php", { filesInFolder, folderName: folder.folderName, folderId: folder.folderId }).then((r) => {
      if (r && r.success === true) return B.load().then(() => { st.details = {}; return { ok: true }; });
      const clash = r && r.errors && r.errors.file_exists ? Object.values(r.errors.file_exists).map((n) => "“" + n + "” already exists.").join(" ") : "";
      return { ok: false, message: clash || problem(r, "The changes couldn’t be saved.") };
    }).catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  }

  IPS.browser.actions = { moveWithUndo, undoAddFolder, problem, loadDetail, saveFile, addFolder, moveItems, removeItems, startZip, zipProgress, zipUrl, loadFolderFiles, saveFolderFiles };
})();
