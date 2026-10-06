/* IPS file browser, the detail panel: the current folder when nothing is selected, else the selected file or folder, else
   a summary of a multiple selection. A file's catalogue entry (title, description) and its name and folder are edited
   here and saved with json-file_functions.php (browser-actions.js). */
(function () {
  "use strict";
  const { reactive, computed, watch } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store, CFG = IPS.cfg;

  const Panel = {
    name: "ips-panel",
    emits: ["close"],
    setup(props, { emit }) {
      const sel = computed(() => st.selection.map((p) => B.nodes.value[p]).filter(Boolean));
      const node = computed(() => (sel.value.length === 1 ? sel.value[0] : sel.value.length === 0 ? B.nodes.value[st.cwd] : null));
      const mode = computed(() => (sel.value.length > 1 ? "multi" : node.value && node.value.folder ? "folder" : "file"));
      const label = computed(() => (mode.value === "multi" ? "Selection" : mode.value === "file" ? "File info" : sel.value.length ? "Folder info" : "This folder"));
      const detail = computed(() => (mode.value === "file" && node.value ? st.details[node.value.path] || null : null));
      const isImage = computed(() => mode.value === "file" && node.value && B.typeOf(node.value) === "image");
      const writable = computed(() => !!node.value && B.canWrite(node.value.path));
      const folders = computed(() => Object.values(B.nodes.value).filter((n) => n.folder && B.canWrite(n.path)).sort((a, b) => a.path.localeCompare(b.path, undefined, { numeric: true })));
      const folderLabel = (p) => (p === B.ROOT ? "All files" : p.replace(/^files\//, "").split("/").join(" / "));

      // Public access (folders only): the switch is an administrator's; everyone sees the state.
      const pub = reactive({ busy: false, msg: "" });
      watch(() => (node.value ? node.value.path : null), () => (pub.msg = ""));
      function setPublic(on) {
        if (pub.busy || !node.value) return;
        const name = node.value.name;
        pub.busy = true;
        pub.msg = "";
        IPS.postForm("json/json-folder_public.php", { dir: node.value.path, public: on ? "1" : "0" })
          .then((r) => {
            if (!r || r.success === false) { pub.msg = (r && r.error) || "The change wasn’t saved."; return null; }
            IPS.toast("“" + name + "” is now " + (on ? "public" : "private"));
            return B.load();
          })
          .catch((e) => { pub.msg = "The change wasn’t saved (" + e.message + ")."; })
          .finally(() => (pub.busy = false));
      }

      const form = reactive({ title: "", description: "", name: "", folder: "" });
      const errors = reactive({ msg: "" });
      const saving = Vue.ref(false);
      function fill() {
        const d = detail.value, n = node.value;
        Object.assign(form, { title: d && d.found ? d.title : "", description: d && d.found ? d.description : "", name: n && !n.folder ? n.name : "", folder: n ? B.path.parentOf(n.path) : "" });
        errors.msg = "";
      }
      // Fetch the catalogue entry for the selected file; refill the form when it (or the file) changes.
      watch(() => (mode.value === "file" && node.value ? node.value.path : null), (p) => { if (p) IPS.browser.actions.loadDetail(node.value); fill(); }, { immediate: true });
      watch(detail, fill);

      const dirty = computed(() => {
        const d = detail.value, n = node.value;
        return !!(d && d.found && n && (form.title !== d.title || form.description !== d.description || form.name !== n.name || form.folder !== B.path.parentOf(n.path)));
      });
      function save() {
        if (!dirty.value || saving.value) return;
        if (!form.name.trim()) { errors.msg = "The file needs a name."; return; }
        errors.msg = "";
        saving.value = true;
        const was = node.value, saved = Object.assign({}, form, { name: form.name.trim() });
        IPS.browser.actions.saveFile(was, saved).then((r) => {
          if (!r.ok) { errors.msg = r.message; return; }
          IPS.toast("Saved “" + (saved.title || saved.name) + "”");
          st.selection = B.path.parentOf(r.path) === st.cwd ? [r.path] : [];
          IPS.browser.actions.loadDetail(B.nodes.value[r.path] || { path: r.path, name: form.name }, true);
        }).finally(() => (saving.value = false));
      }

      const multi = computed(() => {
        const n = sel.value.length, folders = sel.value.filter((x) => x.folder).length;
        return { n, folders, files: n - folders, size: sel.value.reduce((t, x) => t + (x.folder ? B.stats(x.path).size : x.size || 0), 0), names: sel.value.slice(0, 6), more: Math.max(0, n - 6) };
      });
      const folderInfo = computed(() => (node.value && node.value.folder ? Object.assign(B.stats(node.value.path), { sub: B.children(node.value.path).filter((c) => c.folder).length }) : null));
      const bytes = (n) => U.bytesToSize(n) + (n >= 1024 ? " (" + Number(n).toLocaleString("en") + " bytes)" : "");
      const when = (sec) => { if (!sec) return "—"; const d = new Date(sec * 1000); return U.fmtEpoch(sec) + ", " + String(d.getHours()).padStart(2, "0") + ":" + String(d.getMinutes()).padStart(2, "0"); };

      return { S, B, st, CFG, U, pub, setPublic, sel, node, mode, label, detail, isImage, writable, folders, folderLabel, form, errors, saving, dirty, save, fill, multi, folderInfo, bytes, when, emit };
    },
    template: `
      <aside class="flex flex-col min-h-0 bg-surface border-l border-line" aria-label="Details">
        <div class="flex items-center h-12 px-5 border-b border-line shrink-0">
          <span :class="S.eyebrow">{{ label }}</span>
          <button type="button" @click="emit('close')" aria-label="Close panel" :class="S.btnGhost" class="ml-auto !px-1.5"><ips-icon name="x" :size="16"></ips-icon></button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 grid gap-[18px] content-start">

          <!-- a file -->
          <template v-if="mode === 'file' && node">
            <div>
              <div v-if="isImage" class="relative aspect-[4/3] rounded-[10px] overflow-hidden border border-line bg-sidebar">
                <img :src="B.thumbUrl(node)" :data-full="B.fileUrl(node)" alt="" class="absolute inset-0 w-full h-full object-contain" onerror="if(!this.dataset.tried){this.dataset.tried=1;this.src=this.dataset.full}">
              </div>
              <div v-else class="grid place-items-center h-[120px] rounded-[10px] border border-line bg-sidebar"><ips-badge :name="node.name" large></ips-badge></div>
              <h2 class="mt-3 font-display text-[21px] font-medium leading-tight break-words">{{ (detail && detail.found && detail.title) || node.name }}</h2>
              <p class="mt-1 font-mono text-[11.5px] text-muted break-all">{{ node.path }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <a :href="B.fileUrl(node)" :download="node.name" :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px] no-underline"><ips-icon name="download" :size="14"></ips-icon>Download</a>
              </div>
            </div>

            <p v-if="!detail || detail.loading" class="text-[13px] text-muted">Loading…</p>
            <template v-else-if="detail.found">
              <form @submit.prevent="save" class="grid gap-[18px]">
                <section class="grid gap-2.5">
                  <h3 :class="S.eyebrow">Catalogue entry</h3>
                  <label class="grid gap-1.5"><span :class="S.label">Title</span>
                    <input v-model="form.title" placeholder="No title" :class="S.input"></label>
                  <label class="grid gap-1.5"><span :class="S.label">Description</span>
                    <textarea v-model="form.description" rows="3" placeholder="No description" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-[13.5px] outline-none focus:border-accent focus:ring-[3px] focus:ring-accent-ring"></textarea></label>
                </section>
                <section class="grid gap-2.5">
                  <h3 :class="S.eyebrow">Filesystem</h3>
                  <label class="grid gap-1.5"><span :class="S.label">File name</span>
                    <input v-model="form.name" :disabled="!writable" :class="S.input" class="font-mono !text-[12.5px]"></label>
                  <label class="grid gap-1.5"><span :class="S.label">Folder</span>
                    <select v-model="form.folder" :disabled="!writable" :class="S.input">
                      <option v-for="f in folders" :key="f.path" :value="f.path">{{ folderLabel(f.path) }}</option>
                    </select></label>
                  <p v-if="!writable" class="text-[12px] text-muted">Only administrators can rename or move files outside <span class="font-mono">files/{{ CFG.user }}</span>.</p>
                </section>
                <p v-if="errors.msg" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[12.5px] text-danger">{{ errors.msg }}</p>
                <div class="flex gap-2">
                  <button type="submit" :disabled="!dirty || saving" :class="S.btnPrimary">{{ saving ? 'Saving…' : 'Save changes' }}</button>
                  <button type="button" :disabled="!dirty || saving" @click="fill" :class="S.btnSecondary">Reset</button>
                </div>
              </form>
              <dl class="grid grid-cols-[84px_minmax(0,1fr)] gap-x-3 gap-y-1.5 text-[12.5px]">
                <dt class="text-muted">Size</dt><dd>{{ bytes(node.size) }}</dd>
                <dt class="text-muted">Type</dt><dd class="font-mono text-[12px] break-all">{{ detail.type }}</dd>
                <dt class="text-muted">File ID</dt><dd class="font-mono text-[12px]">{{ detail.id }}</dd>
                <dt class="text-muted">Modified</dt><dd>{{ when(node.modified) }}</dd>
                <dt class="text-muted">URL</dt><dd class="break-all"><a :href="detail.url" class="text-accent hover:underline">{{ detail.url }}</a></dd>
              </dl>
            </template>
            <template v-else>
              <p class="rounded-lg bg-sidebar px-3 py-2.5 text-[12.5px] text-muted">{{ detail.failed ? 'The catalogue entry couldn’t be loaded.' : 'This file isn’t in the catalogue yet, so it has no title or description to edit.' }}</p>
              <dl class="grid grid-cols-[84px_minmax(0,1fr)] gap-x-3 gap-y-1.5 text-[12.5px]">
                <dt class="text-muted">Size</dt><dd>{{ bytes(node.size) }}</dd>
                <dt class="text-muted">Modified</dt><dd>{{ when(node.modified) }}</dd>
              </dl>
            </template>
          </template>

          <!-- a folder -->
          <template v-else-if="mode === 'folder' && node">
            <div class="flex items-center gap-3.5">
              <span class="grid place-items-center w-[52px] h-[52px] rounded-xl bg-accent-soft text-accent shrink-0"><ips-icon name="folder" :size="26"></ips-icon></span>
              <div class="min-w-0"><h2 class="font-display text-[21px] font-medium leading-tight break-words">{{ node.path === B.ROOT ? 'All files' : node.name }}</h2>
                <p class="mt-0.5 font-mono text-[11.5px] text-muted break-all">{{ node.path }}</p></div>
            </div>
            <div class="grid grid-cols-3 rounded-[10px] border border-line divide-x divide-line text-center">
              <div class="py-2.5"><div class="font-display text-[20px] font-medium">{{ folderInfo.sub }}</div><div class="text-[11.5px] text-muted">Folders</div></div>
              <div class="py-2.5"><div class="font-display text-[20px] font-medium">{{ folderInfo.files }}</div><div class="text-[11.5px] text-muted">Files</div></div>
              <div class="py-2.5"><div class="font-display text-[20px] font-medium">{{ U.bytesToSize(folderInfo.size) }}</div><div class="text-[11.5px] text-muted">Size</div></div>
            </div>
            <div v-if="node.path !== st.cwd"><button type="button" @click="B.go(node.path)" :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px]"><ips-icon name="folder" :size="14"></ips-icon>Open</button></div>
            <div v-if="node.path !== B.ROOT" class="flex flex-wrap gap-2">
              <button type="button" @click="B.openDialog('zip', [node])" data-act="zip" :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px]"><ips-icon name="download" :size="14"></ips-icon>Download as zip</button>
              <button v-if="CFG.admin" type="button" @click="B.openDialog('batch', [node])" data-act="batch" :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px]"><ips-icon name="edit-3" :size="14"></ips-icon>Edit files…</button>
            </div>
            <section v-if="node.path !== B.ROOT" class="grid gap-2" data-public>
              <h3 :class="S.eyebrow">Public access</h3>
              <p v-if="node.public" class="rounded-lg bg-accent-soft px-3 py-2.5 text-[12.5px] text-accent">{{ node.marked ? 'Public. Anyone can see the files in this folder and the folders below it, without signing in.' : 'Public, through a folder above it. Anyone can see its files without signing in.' }}</p>
              <p v-else class="rounded-lg bg-sidebar px-3 py-2.5 text-[12.5px] text-muted">Private. Only signed-in users can see the files in this folder.</p>
              <div v-if="CFG.admin && (node.marked || !node.public)"><button type="button" :disabled="pub.busy" @click="setPublic(!node.marked)" data-act="public" :class="S.btnSecondary" class="!h-[30px] !rounded-[7px] !text-[12.5px]"><ips-icon name="globe" :size="14"></ips-icon>{{ node.marked ? 'Make private' : 'Make public' }}</button></div>
              <p v-else-if="CFG.admin" class="text-[12px] text-muted">To make it private, change the folder above.</p>
              <p v-if="pub.msg" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[12.5px] text-danger">{{ pub.msg }}</p>
            </section>
            <dl class="grid grid-cols-[110px_minmax(0,1fr)] gap-x-3 gap-y-1.5 text-[12.5px]">
              <dt class="text-muted">Last modified</dt><dd>{{ when(node.modified) }}</dd>
            </dl>
          </template>

          <!-- several -->
          <template v-else-if="mode === 'multi'">
            <div>
              <h2 class="font-display text-[24px] font-medium leading-tight">{{ multi.n }} selected</h2>
              <p class="mt-1 text-[13px] text-muted">{{ [multi.folders ? U.plural(multi.folders, 'folder') : '', multi.files ? U.plural(multi.files, 'file') : ''].filter(Boolean).join(' and ') }} · {{ U.bytesToSize(multi.size) }}</p>
            </div>
            <ul class="grid gap-1.5 text-[13px]">
              <li v-for="x in multi.names" :key="x.path" class="flex items-center gap-2 min-w-0"><ips-icon :name="x.folder ? 'folder' : 'file'" :size="14" class="text-muted"></ips-icon><span class="truncate">{{ x.name }}</span></li>
              <li v-if="multi.more" class="text-muted">and {{ multi.more }} more</li>
            </ul>
            <div><button type="button" @click="B.clearSelection()" :class="S.btnSecondary">Clear</button></div>
          </template>
        </div>
      </aside>`,
  };
  IPS.components = Object.assign(IPS.components || {}, { "ips-panel": Panel });
})();
