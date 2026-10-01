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
    remove: (nodes) => IPS.cfg.admin && nodes.length > 0,
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

      watch(() => dialog.kind, (k) => {
        busy.value = false; error.value = ""; name.value = "";
        if (k === "move") { const first = targets.value.find((p) => !nodes.value.every((n) => parentOf(n.path) === p)); dest.value = first || ""; }
      });

      function done(r, message, after) {
        busy.value = false;
        if (!r.ok) { error.value = r.message; return; }
        close();
        IPS.toast(message);
        if (after) after(r);
      }
      function submit() {
        if (busy.value) return;
        error.value = "";
        if (dialog.kind === "add") {
          if (!name.value.trim() || nameProblem.value) return;
          busy.value = true;
          const v = name.value.trim();
          A.addFolder(st.cwd, v).then((r) => done(r, "Created “" + v + "”", () => { st.expanded[st.cwd] = true; B.go(r.path); }));
        } else if (dialog.kind === "move") {
          if (!dest.value || here.value || clash.value) return;
          busy.value = true;
          const moved = nodes.value, to = dest.value;
          A.moveItems(moved, to).then((r) => done(r, "Moved " + (moved.length === 1 ? "“" + moved[0].name + "”" : U.plural(moved.length, "item")) + " to " + folderLabel(to), () => { st.selection = []; }));
        } else if (dialog.kind === "remove") {
          if (blocked.value.length) return;
          busy.value = true;
          const gone = nodes.value;
          A.removeItems(gone).then((r) => done(r, "Removed " + (gone.length === 1 ? "“" + gone[0].name + "”" : U.plural(gone.length, "item")), () => { st.selection = []; }));
        }
      }
      return { S, dialog, close, busy, error, name, dest, nodes, summary, targets, folderLabel, here, clash, blocked, nameProblem, submit, st };
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
