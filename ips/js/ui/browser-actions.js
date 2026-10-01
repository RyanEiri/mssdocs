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

  IPS.browser.actions = { problem, loadDetail, saveFile };
})();
