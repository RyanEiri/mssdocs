/* IPS file browser, projects: link files to the projects of the optional Projects module (projects/), from the upload and later. A project filter shows one project's
   files in its own order. Everything here is off unless the module is installed and its tables exist (projects/list.php answers), so a site without it sees no change.
   Calls: projects/list.php, files.php, for_file.php, link.php, unlink.php, save.php (same-origin; the user's CSRF token goes with every POST). The server decides who
   may link which file (an administrator anywhere, anyone else inside files/<their username>). */
(function () {
  "use strict";
  const { reactive, computed, ref, watch } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store, CFG = IPS.cfg;

  const installed = (CFG.modules || []).some((m) => m.id === "projects");
  function saved(key, fallback) { try { return localStorage.getItem("ips.browser." + key) || fallback; } catch (e) { return fallback; } }
  function remember(key, value) { try { localStorage.setItem("ips.browser." + key, String(value)); } catch (e) { /* private mode */ } }

  const P = reactive({ ready: false, list: [], roles: [], kinds: [], rev: 0 });
  const dlg = reactive({ kind: null, nodes: [], count: 0, dir: "", projectId: 0, newTitle: "", newKind: "artefact", role: "artefact-page", busy: false, error: "" });
  let resolver = null;

  const active = () => P.list.filter((p) => p.status !== "archived");
  const title = (id) => { const p = P.list.find((x) => x.id === id); return p ? p.title : "Project"; };
  const roleLabel = (id) => { const r = P.roles.find((x) => x.id === id); return r ? r.label : id; };
  const canLink = (nodes) => nodes.length > 0 && nodes.every((n) => !n.folder && B.canWrite(n.path));
  const failure = (d, fallback) => (d && d.error) || fallback;

  function load() {
    if (!installed) return Promise.resolve();
    return IPS.getJSON("projects/list.php")
      .then((d) => {
        if (d && d.success) { P.list = d.projects; P.roles = d.roles; P.kinds = d.kinds; P.ready = true; } else { P.ready = false; }
      })
      .catch(() => { P.ready = false; });
  }

  // The browser shows one project's files, in the project's order, wherever they are; choosing a folder (or "All files") ends it.
  function setFilter(id) {
    if (!id) { st.project = 0; st.projectPaths = []; return Promise.resolve(); }
    return IPS.getJSON("projects/files.php", { id })
      .then((d) => {
        if (d && d.success) { st.projectPaths = d.files.map((f) => f.path); st.project = id; st.selection = []; } else { IPS.toast(failure(d, "The project’s files couldn’t be read."), { icon: "alert-circle" }); }
      })
      .catch(() => IPS.toast("Couldn’t reach the server.", { icon: "alert-circle" }));
  }

  // Link files (paths "files/...") to a project with a role, at the end of its order. Resolves {ok, linked, failed, message}.
  function link(projectId, role, paths) {
    return IPS.postForm("projects/link.php", { project_id: projectId, role, paths })
      .then((d) => {
        if (!d || d.success !== true) return { ok: false, linked: 0, failed: [], message: failure(d, "The files couldn’t be added to the project.") };
        P.rev++;
        load();
        if (st.project === projectId) setFilter(projectId);
        const bad = d.failed || [];
        return { ok: bad.length === 0, linked: d.linked.length, failed: bad, message: bad.length ? bad[0].error + (bad.length > 1 ? " (and " + (bad.length - 1) + " more)" : "") : "" };
      })
      .catch(() => ({ ok: false, linked: 0, failed: [], message: "Couldn’t reach the server." }));
  }
  const create = (name, kind) => IPS.postForm("projects/save.php", { title: name, kind })
    .then((d) => (d && d.success ? { ok: true, project: d.project } : { ok: false, message: failure(d, "The project couldn’t be made.") }))
    .catch(() => ({ ok: false, message: "Couldn’t reach the server." }));
  const unlink = (projectId, fileId) => IPS.postForm("projects/unlink.php", { project_id: projectId, file_id: fileId })
    .then((d) => (d && d.success ? (P.rev++, load(), { ok: true }) : { ok: false, message: failure(d, "The file couldn’t be taken out of the project.") }))
    .catch(() => ({ ok: false, message: "Couldn’t reach the server." }));

  // ---- the dialog: before an upload (the choice is handed back, the upload then links what it sent) and for files already there ------
  function open(kind, extra) {
    const last = Number(saved("project", "0")), usable = active().some((p) => p.id === last);
    Object.assign(dlg, { kind, nodes: [], count: 0, dir: "", newTitle: "", newKind: "artefact", busy: false, error: "", role: saved("projectRole", "artefact-page") }, extra || {});
    dlg.projectId = usable ? last : kind === "upload" ? 0 : active().length ? active()[0].id : -1;
  }
  function chooseForUpload(count, dir) {
    return new Promise((resolve) => { resolver = resolve; open("upload", { count, dir }); });
  }
  function openLink(nodes) {
    const files = nodes.filter((n) => !n.folder);
    if (!files.length) return;
    open("link", { nodes: files, count: files.length });
  }
  function finish(value) { const r = resolver; resolver = null; dlg.kind = null; dlg.nodes = []; if (r) r(value); }

  const Dialog = {
    name: "ips-project-dialog",
    setup() {
      const projects = computed(() => active());
      const needsTitle = computed(() => dlg.projectId === -1);
      const blocked = computed(() => dlg.busy || (needsTitle.value && !dlg.newTitle.trim()));
      const heading = computed(() => (dlg.kind === "upload" ? "Upload " + U.plural(dlg.count, "file") : "Add " + (dlg.count === 1 ? "“" + dlg.nodes[0].name + "”" : U.plural(dlg.count, "file")) + " to a project"));
      const subtitle = computed(() => (dlg.kind === "upload" ? "Into " + (dlg.dir.replace(/^files\//, "").split("/").join(" / ") || "All files") + ". Link the files to a project as they arrive, or choose no project." : "Pick a project and say what the files are in it."));
      watch(() => dlg.projectId, () => { dlg.error = ""; });

      function cancel() { if (dlg.busy) return; if (dlg.kind === "upload") finish(null); else { dlg.kind = null; dlg.nodes = []; } }
      async function confirm() {
        if (blocked.value) return;
        dlg.error = "";
        let id = dlg.projectId;
        if (dlg.kind === "upload" && id === 0) { finish({ project_id: 0, role: dlg.role }); return; }
        dlg.busy = true;
        if (id === -1) {
          const made = await create(dlg.newTitle.trim(), dlg.newKind);
          if (!made.ok) { dlg.busy = false; dlg.error = made.message; return; }
          await load();
          id = made.project.id;
        }
        remember("project", id);
        remember("projectRole", dlg.role);
        if (dlg.kind === "upload") { dlg.busy = false; finish({ project_id: id, role: dlg.role }); return; }
        const nodes = dlg.nodes.slice().sort(B.byName), name = title(id);
        const r = await link(id, dlg.role, nodes.map((n) => n.path));
        dlg.busy = false;
        if (!r.linked) { dlg.error = r.message; return; }
        dlg.kind = null; dlg.nodes = [];
        IPS.toast("Added " + U.plural(r.linked, "file") + " to “" + name + "”" + (r.failed.length ? ", but " + r.message : ""), r.failed.length ? { icon: "alert-circle", ms: 10000 } : undefined);
        // a file that had no catalogue row now has one (linking makes it): read its entry again so the panel stops saying it is missing
        nodes.forEach((n) => { const d = st.details[n.path]; if (d && d.found === false) B.actions.loadDetail(n, true); });
      }
      return { S, dlg, P, projects, needsTitle, blocked, heading, subtitle, cancel, confirm };
    },
    template: `
      <ips-modal v-if="dlg.kind" :title="heading" :subtitle="subtitle" :busy="dlg.busy" @close="cancel">
        <form id="proj-form" @submit.prevent="confirm" class="grid gap-3" data-dlg="projects">
          <div class="grid gap-1.5"><label :class="S.label" for="proj-pick">Project</label>
            <select id="proj-pick" v-model.number="dlg.projectId" :class="S.input" data-dlg="project">
              <option v-if="dlg.kind === 'upload'" :value="0">No project</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.title }}</option>
              <option :value="-1">New project…</option>
            </select></div>
          <template v-if="needsTitle">
            <div class="grid gap-1.5"><label :class="S.label" for="proj-new">New project’s title</label>
              <input id="proj-new" v-model="dlg.newTitle" type="text" maxlength="255" autocomplete="off" :class="S.input" data-dlg="newtitle"></div>
            <div class="grid gap-1.5"><label :class="S.label" for="proj-kind">Kind</label>
              <select id="proj-kind" v-model="dlg.newKind" :class="S.input" data-dlg="kind"><option v-for="k in P.kinds" :key="k.id" :value="k.id">{{ k.label }}</option></select></div>
          </template>
          <div v-if="dlg.projectId !== 0" class="grid gap-1.5"><label :class="S.label" for="proj-role">Role of the files</label>
            <select id="proj-role" v-model="dlg.role" :class="S.input" data-dlg="role"><option v-for="r in P.roles" :key="r.id" :value="r.id">{{ r.label }}</option></select></div>
          <p v-if="dlg.error" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ dlg.error }}</p>
        </form>
        <template #footer>
          <button type="button" @click="cancel" :disabled="dlg.busy" :class="S.btnSecondary" data-dlg="cancel">Cancel</button>
          <button type="submit" form="proj-form" :disabled="blocked" :class="S.btnPrimary" data-dlg="confirm">{{ dlg.busy ? 'Working…' : dlg.kind === 'upload' ? 'Upload' : 'Add to project' }}</button>
        </template>
      </ips-modal>`,
  };

  // ---- the details panel's section for one file ------------------------------------------------------------------
  const FileProjects = {
    name: "ips-file-projects",
    props: { node: { type: Object, required: true } },
    setup(props) {
      const items = ref([]), fileId = ref(null), loading = ref(true), msg = ref("");
      const allowed = computed(() => canLink([props.node]));
      function fetchProjects() {
        const path = props.node.path;
        loading.value = true;
        IPS.getJSON("projects/for_file.php", { path })
          .then((d) => { if (path === props.node.path && d && d.success) { items.value = d.projects; fileId.value = d.file_id; } })
          .catch(() => { items.value = []; })
          .finally(() => { loading.value = false; });
      }
      watch([() => props.node.path, () => P.rev], () => { msg.value = ""; fetchProjects(); }, { immediate: true });
      async function remove(item) {
        msg.value = "";
        const r = await unlink(item.project_id, fileId.value);
        if (!r.ok) msg.value = r.message;
        else if (st.project === item.project_id) setFilter(item.project_id);
      }
      return { S, P, CFG, items, loading, msg, allowed, roleLabel, remove, openLink: () => openLink([props.node]), show: (id) => setFilter(id) };
    },
    template: `
      <section class="grid gap-2" data-projects="panel">
        <h3 :class="S.eyebrow">Projects</h3>
        <p v-if="loading && !items.length" class="text-[12.5px] text-muted">Loading…</p>
        <p v-else-if="!items.length" class="text-[12.5px] text-muted" data-projects="none">In no project yet.</p>
        <ul v-else class="grid gap-1.5 text-[13px]">
          <li v-for="i in items" :key="i.project_id" class="rounded-lg border border-line px-3 py-2" data-projects="item">
            <div class="flex items-start gap-2">
              <a :href="CFG.base + 'projects/?id=' + i.project_id" class="min-w-0 flex-1 font-medium text-accent hover:underline break-words">{{ i.title }}</a>
              <button type="button" @click="show(i.project_id)" :class="S.btnGhost" class="!h-[30px] !px-1.5 !text-[12.5px]" data-projects="show">Show</button>
              <button type="button" @click="remove(i)" :class="S.btnGhost" class="!h-[30px] !px-1.5 !text-[12.5px]" data-projects="remove" :aria-label="'Remove from ' + i.title">Remove</button>
            </div>
            <p class="mt-0.5 text-[12px] text-muted">{{ roleLabel(i.role) }}<span v-if="i.label"> · {{ i.label }}</span></p>
          </li>
        </ul>
        <p v-if="msg" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[12.5px] text-danger" data-projects="error">{{ msg }}</p>
        <div><button type="button" @click="openLink" :disabled="!allowed" :title="allowed ? 'Add this file to a project' : 'You can link files inside files/' + CFG.user"
          :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px]" data-act="project"><ips-icon name="layers" :size="14"></ips-icon>Add to project…</button></div>
      </section>`,
  };

  Object.assign(P, { title, roleLabel, canLink, load, setFilter, link, create, unlink, chooseForUpload, openLink });
  IPS.browser.projects = P;
  IPS.components = Object.assign(IPS.components || {}, { "ips-project-dialog": Dialog, "ips-file-projects": FileProjects });

  // a link from the Projects page ("Show in Files") opens the browser on that project
  load().then(() => {
    const m = /[?&]project=(\d+)/.exec(location.search);
    if (m && P.list.some((p) => p.id === Number(m[1]))) setFilter(Number(m[1]));
  });
})();
