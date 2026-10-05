/* IPS file browser, dialogs: New folder, Move and Remove. One dialog is open at a time (`B.dialog.kind`); each posts
   through browser-actions.js and, on success, closes with a toast. A refusal from the server is shown inside the dialog. */
(function () {
  "use strict";
  const { reactive, computed, ref, watch } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store, A = B.actions;
  const { parentOf, within } = B.path;

  const dialog = reactive({ kind: null, nodes: [] });
  const open = (kind, nodes) => { dialog.kind = kind; dialog.nodes = nodes || []; };
  const close = () => { dialog.kind = null; dialog.nodes = []; };
  const selected = () => st.selection.map((p) => B.nodes.value[p]).filter(Boolean);

  // What each action may do right now: where it applies and why not, for the toolbar's disabled states.
  const can = {
    add: () => B.canWrite(st.cwd) && !B.searching.value,
    move: (nodes) => nodes.length > 0 && nodes.every((n) => B.canWrite(n.path)),
    // administrators anywhere; everyone else inside their own folder, but not that folder itself (the server enforces the same rule)
    remove: (nodes) => nodes.length > 0 && nodes.every((n) => B.canWrite(n.path) && (IPS.cfg.admin || n.path !== B.path.join(B.ROOT, IPS.cfg.user))),
  };
  // A folder that still holds files can't be removed (the endpoint refuses it too); thumbnails don't count.
  const filled = (n) => n.folder && B.children(n.path).length > 0;

  const Dialogs = {
    name: "ips-dialogs",
    setup() {
      const busy = ref(false), error = ref(""), name = ref(""), dest = ref("");
      const nodes = computed(() => dialog.nodes);
      const summary = computed(() => (nodes.value.length === 1 ? "“" + nodes.value[0].name + "”" : U.plural(nodes.value.length, "item")));
      const folderLabel = (p) => (p === B.ROOT ? "All files" : p.replace(/^files\//, "").split("/").join(" / "));
      // Destinations: writable folders, never a moved folder or anything inside it, never where the items already are.
      const targets = computed(() => {
        const moving = nodes.value.filter((n) => n.folder).map((n) => n.path);
        return Object.values(B.nodes.value)
          .filter((n) => n.folder && B.canWrite(n.path) && !moving.some((m) => within(n.path, m)))
          .map((n) => n.path).sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
      });
      const here = computed(() => nodes.value.length > 0 && nodes.value.every((n) => parentOf(n.path) === dest.value));
      const blocked = computed(() => nodes.value.filter(filled));
      const clash = computed(() => {
        const taken = (d, n) => B.children(d).some((c) => c.name === n);
        if (dialog.kind === "add") return taken(st.cwd, name.value.trim());
        if (dialog.kind === "move") return nodes.value.some((n) => taken(dest.value, n.name) && parentOf(n.path) !== dest.value);
        return false;
      });
      const nameProblem = computed(() => {
        const v = name.value.trim();
        if (!v) return "";
        if (/[\\/?%*:|"<>]/.test(v)) return "A folder name can’t contain \\ / ? % * : | \" < >";
        if (clash.value) return "Something called “" + v + "” is already here.";
        return "";
      });

      // Zip: starts when the dialog opens, polls the worker, then offers the finished file.
      const zip = reactive({ phase: "start", percent: 0, count: 0, total: 0, token: "", filename: "", error: "" });
      let polling = 0;
      function runZip(path) {
        Object.assign(zip, { phase: "start", percent: 0, count: 0, total: 0, token: "", filename: "", error: "" });
        const me = ++polling;
        A.startZip(path).then((r) => {
          if (me !== polling) return;
          if (!r.ok) { zip.phase = "failed"; zip.error = r.message; return; }
          zip.token = r.token; zip.total = r.total; zip.phase = "working";
          const tick = () => A.zipProgress(r.token).then((d) => {
            if (me !== polling) return;
            if (d.error) { zip.phase = "failed"; zip.error = d.error; return; }
            Object.assign(zip, { percent: d.percent || 0, count: d.count || 0, total: d.total || zip.total });
            if (d.done) { zip.filename = d.filename; zip.size = d.filesize; zip.phase = "done"; } else setTimeout(tick, 400);
          });
          tick();
        });
      }

      // Batch edit (administrators): one row per catalogued file in the folder.
      const batch = reactive({ loading: true, folder: null, rows: [], original: [], message: "" });
      const changed = computed(() => batch.rows.filter((r, i) => ["name", "title", "description"].some((k) => r[k] !== batch.original[i][k])));
      const badName = computed(() => batch.rows.some((r) => !r.name.trim() || /[\\/?%*:|"<>]/.test(r.name)));
      function loadBatch(path) {
        Object.assign(batch, { loading: true, folder: null, rows: [], original: [], message: "" });
        A.loadFolderFiles(path).then((r) => {
          batch.loading = false;
          if (!r.ok) { error.value = r.message; return; }
          batch.folder = r; batch.rows = r.files.map((f) => Object.assign({}, f)); batch.original = r.files.map((f) => Object.assign({}, f));
        });
      }

      watch(() => dialog.kind, (k) => {
        if (k === "zip") runZip(nodes.value[0].path); else polling++;
        if (k === "batch") loadBatch(nodes.value[0].path);
        busy.value = false; error.value = ""; name.value = "";
        if (k === "move") { const first = targets.value.find((p) => !nodes.value.every((n) => parentOf(n.path) === p)); dest.value = first || ""; }
      });

      function done(r, message, after, opts) {
        busy.value = false;
        if (!r.ok) { error.value = r.message; return; }
        close();
        if (message) IPS.toast(message, opts);
        if (after) after(r);
      }
      function submit() {
        if (busy.value) return;
        error.value = "";
        if (dialog.kind === "add") {
          if (!name.value.trim() || nameProblem.value) return;
          busy.value = true;
          const v = name.value.trim();
          A.addFolder(st.cwd, v).then((r) => done(r, "Created “" + v + "”", () => { st.expanded[st.cwd] = true; B.go(r.path); }, { ms: 10000, undo: () => A.undoAddFolder(r.path) }));
        } else if (dialog.kind === "move") {
          if (!dest.value || here.value || clash.value) return;
          busy.value = true;
          const moved = nodes.value, to = dest.value;
          A.moveWithUndo(moved, to).then((r) => done(r, null, () => { st.selection = []; }));
        } else if (dialog.kind === "batch") {
          if (!changed.value.length || badName.value) return;
          busy.value = true;
          const n = changed.value.length;
          A.saveFolderFiles(batch.folder, batch.rows.map((r) => Object.assign({}, r, { name: r.name.trim() })).filter((r, i) => changed.value.includes(batch.rows[i])))
            .then((r) => done(r, "Saved " + U.plural(n, "file")));
        } else if (dialog.kind === "remove") {
          if (blocked.value.length) return;
          busy.value = true;
          const gone = nodes.value;
          A.removeItems(gone).then((r) => done(r, "Removed " + (gone.length === 1 ? "“" + gone[0].name + "”" : U.plural(gone.length, "item")), () => { st.selection = []; }));
        }
      }
      return { zip, zipLink: (z) => A.zipUrl(z.token, z.filename), batch, changed, badName, S, dialog, close, busy, error, name, dest, nodes, summary, targets, folderLabel, here, clash, blocked, nameProblem, submit, st };
    },
    template: `
      <ips-modal v-if="dialog.kind === 'add'" title="New folder" :subtitle="'In ' + folderLabel(st.cwd)" :busy="busy" @close="close">
        <form id="dlg-form" @submit.prevent="submit">
          <label :class="S.label">Folder name</label>
          <input v-model="name" type="text" autofocus autocomplete="off" maxlength="120" :class="S.input" aria-label="Folder name" data-dlg="name">
          <p v-if="nameProblem" role="alert" class="mt-1.5 text-[12.5px] text-danger" data-dlg="problem">{{ nameProblem }}</p>
          <p v-if="error" role="alert" class="mt-2 rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ error }}</p>
        </form>
        <template #footer>
          <button type="button" @click="close" :disabled="busy" :class="S.btnSecondary">Cancel</button>
          <button type="submit" form="dlg-form" :disabled="busy || !name.trim() || !!nameProblem" :class="S.btnPrimary" data-dlg="confirm">{{ busy ? 'Creating…' : 'Create folder' }}</button>
        </template>
      </ips-modal>

      <ips-modal v-else-if="dialog.kind === 'move'" :title="'Move ' + summary" subtitle="Choose the folder to move into." :busy="busy" @close="close">
        <form id="dlg-form" @submit.prevent="submit">
          <label :class="S.label">Destination</label>
          <select v-model="dest" :class="S.input" aria-label="Destination folder" data-dlg="dest">
            <option v-for="p in targets" :key="p" :value="p">{{ folderLabel(p) }}</option>
          </select>
          <p v-if="here" class="mt-1.5 text-[12.5px] text-muted" data-dlg="problem">Already in this folder. Choose a different one.</p>
          <p v-else-if="clash" role="alert" class="mt-1.5 text-[12.5px] text-danger" data-dlg="problem">A file or folder with the same name is already there; nothing is replaced.</p>
          <p v-if="error" role="alert" class="mt-2 rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ error }}</p>
        </form>
        <template #footer>
          <button type="button" @click="close" :disabled="busy" :class="S.btnSecondary">Cancel</button>
          <button type="submit" form="dlg-form" :disabled="busy || !dest || here || clash" :class="S.btnPrimary" data-dlg="confirm">{{ busy ? 'Moving…' : 'Move' }}</button>
        </template>
      </ips-modal>

      <ips-modal v-else-if="dialog.kind === 'zip'" :title="'Download ' + summary + ' as a zip'" :width="440" @close="close">
        <div data-dlg="zip">
          <p v-if="zip.phase === 'failed'" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ zip.error }}</p>
          <template v-else>
            <div class="h-2 rounded bg-sidebar overflow-hidden" role="progressbar" :aria-valuenow="zip.percent" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-accent transition-all" :style="{ width: zip.percent + '%' }"></div></div>
            <p class="mt-2 text-[13px] text-muted" data-dlg="status">{{ zip.phase === 'done' ? 'Ready: ' + zip.filename : zip.phase === 'start' ? 'Starting…' : 'Adding files… ' + zip.count + ' of ' + zip.total }}</p>
          </template>
        </div>
        <template #footer>
          <button type="button" @click="close" :class="S.btnSecondary">{{ zip.phase === 'done' ? 'Close' : 'Cancel' }}</button>
          <a v-if="zip.phase === 'done'" :href="zipLink(zip)" @click="close" download :class="S.btnPrimary" class="no-underline" data-dlg="confirm">Download</a>
        </template>
      </ips-modal>

      <ips-modal v-else-if="dialog.kind === 'batch'" :title="'Edit the files in ' + summary" subtitle="Change names, titles and descriptions together. A new name renames the file." :width="880" :busy="busy" @close="close">
        <p v-if="batch.loading" class="text-[13px] text-muted">Loading…</p>
        <p v-else-if="!batch.folder" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ error }}</p>
        <p v-else-if="!batch.rows.length" class="text-[13px] text-muted" data-dlg="status">No catalogued files in this folder.</p>
        <form v-else id="dlg-form" @submit.prevent="submit">
          <div class="max-h-[56vh] overflow-y-auto -mx-1 px-1">
            <table class="w-full text-[13px]" data-dlg="table">
              <thead><tr class="text-left text-[11.5px] uppercase tracking-[0.06em] text-muted"><th class="pb-1.5 pr-2 font-medium">File name</th><th class="pb-1.5 pr-2 font-medium">Title</th><th class="pb-1.5 font-medium">Description</th></tr></thead>
              <tbody>
                <tr v-for="(r, i) in batch.rows" :key="r.id">
                  <td class="pb-2 pr-2 w-[30%]"><input v-model="r.name" type="text" :class="S.input" class="font-mono !text-[12.5px]" :aria-label="'File name ' + (i + 1)"></td>
                  <td class="pb-2 pr-2 w-[30%]"><input v-model="r.title" type="text" :class="S.input" :aria-label="'Title ' + (i + 1)"></td>
                  <td class="pb-2"><input v-model="r.description" type="text" :class="S.input" :aria-label="'Description ' + (i + 1)"></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="badName" role="alert" class="mt-1.5 text-[12.5px] text-danger" data-dlg="problem">A file name can’t be empty or contain \\ / ? % * : | &quot; &lt; &gt;</p>
          <p v-if="error" role="alert" class="mt-2 rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ error }}</p>
        </form>
        <template #footer>
          <span class="mr-auto self-center text-[12.5px] text-muted">{{ changed.length ? changed.length + ' changed' : 'No changes yet' }}</span>
          <button type="button" @click="close" :disabled="busy" :class="S.btnSecondary">Cancel</button>
          <button type="submit" form="dlg-form" :disabled="busy || !changed.length || badName" :class="S.btnPrimary" data-dlg="confirm">{{ busy ? 'Saving…' : 'Save changes' }}</button>
        </template>
      </ips-modal>

      <ips-modal v-else-if="dialog.kind === 'remove'" :title="'Remove ' + summary + '?'" subtitle="This can’t be undone." :busy="busy" @close="close">
        <ul class="max-h-40 overflow-y-auto text-[13px] text-ink">
          <li v-for="n in nodes.slice(0, 8)" :key="n.path" class="truncate">{{ n.name }}<span v-if="n.folder" class="text-muted"> (folder)</span></li>
          <li v-if="nodes.length > 8" class="text-muted">and {{ nodes.length - 8 }} more</li>
        </ul>
        <p v-if="blocked.length" role="alert" class="mt-2 rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="problem">{{ blocked.map((n) => n.name).join(', ') }} still {{ blocked.length === 1 ? 'holds' : 'hold' }} files. Empty {{ blocked.length === 1 ? 'it' : 'them' }} first.</p>
        <p v-if="error" role="alert" class="mt-2 rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger" data-dlg="error">{{ error }}</p>
        <template #footer>
          <button type="button" @click="close" :disabled="busy" :class="S.btnSecondary">Cancel</button>
          <button type="button" @click="submit" :disabled="busy || blocked.length > 0" :class="S.btnDanger" data-dlg="confirm">{{ busy ? 'Removing…' : 'Remove' }}</button>
        </template>
      </ips-modal>`,
  };

  Object.assign(IPS.browser, { dialog, openDialog: open, can, selected });
  IPS.components = Object.assign(IPS.components || {}, { "ips-dialogs": Dialogs });
})();
