/* IPS file browser, upload: add files to the folder being viewed, by the Upload button or by dropping them from the
   desktop anywhere on the page. Each file is its own request (upload/index.php, `dir` names the folder) so the tray
   can show real progress and one refusal doesn't stop the rest. The server decides what may go where and which file
   types it accepts; its reason is shown beside the file. */
(function () {
  "use strict";
  const { reactive, computed, ref, onMounted, onBeforeUnmount } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store;

  const queue = reactive({ items: [], dir: "", running: false });
  let seq = 0;

  // Upload needs a real folder the user may write to: not "All files" (the endpoint has nowhere to put them) and not search results.
  const can = () => st.cwd !== B.ROOT && B.canWrite(st.cwd) && !B.searching.value;
  const why = () => (st.cwd === B.ROOT ? "Open a folder to upload into it." : B.searching.value ? "Clear the search to upload." : "You can upload inside files/" + IPS.cfg.user + ".");

  // One file: resolves {ok, name, message}.
  function send(item) {
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest(), form = new FormData();
      form.append("files[]", item.file, item.file.name);
      form.append("dir", item.dir);
      xhr.open("POST", IPS.cfg.base + "upload/index.php");
      xhr.setRequestHeader("X-CSRF", IPS.cfg.csrf);
      xhr.setRequestHeader("Accept", "application/json");
      xhr.withCredentials = true;
      xhr.upload.onprogress = (e) => { if (e.lengthComputable) item.pct = Math.round((e.loaded / e.total) * 100); };
      xhr.onerror = () => resolve({ ok: false, message: "Couldn’t reach the server." });
      xhr.onload = () => {
        let d = null;
        try { d = JSON.parse(xhr.responseText.slice(xhr.responseText.indexOf("{"))); } catch (e) { /* not JSON */ }
        const f = d && d.files && d.files[0];
        if (f && !f.error && xhr.status < 300) resolve({ ok: true, name: f.name.split("/").pop() });
        else resolve({ ok: false, message: (f && f.error) || "The upload didn’t go through (" + xhr.status + ")." });
      };
      xhr.send(form);
    });
  }

  function add(fileList) {
    const dir = st.cwd, files = Array.from(fileList || []).filter((f) => f.name);
    if (!files.length) return;
    if (!can()) { IPS.toast(why(), { icon: "alert-circle" }); return; }
    files.forEach((file) => queue.items.push(reactive({ id: ++seq, file, name: file.name, size: file.size, dir, state: "queued", pct: 0, message: "" })));
    if (!queue.running) run();
  }

  async function run() {
    queue.running = true;
    let sent = 0, here = 0;
    const dirs = new Set();
    for (;;) {
      const item = queue.items.find((i) => i.state === "queued");
      if (!item) break;
      item.state = "sending";
      const r = await send(item);
      if (r.ok) { item.state = "done"; item.pct = 100; item.name = r.name; sent++; dirs.add(item.dir); } else { item.state = "error"; item.message = r.message; }
    }
    queue.running = false;
    if (sent) {
      await B.load();
      here = [...dirs].filter((d) => d === st.cwd).length;
      IPS.toast("Uploaded " + U.plural(sent, "file") + (here ? "" : " to " + [...dirs].map((d) => d.replace(/^files\//, "")).join(", ")));
    }
  }

  const clearFinished = () => { queue.items = queue.items.filter((i) => i.state === "queued" || i.state === "sending"); };

  const Tray = {
    name: "ips-upload-tray",
    setup() {
      const total = computed(() => queue.items.length);
      const open = ref(true);
      const summary = computed(() => {
        const left = queue.items.filter((i) => i.state === "queued" || i.state === "sending").length, failed = queue.items.filter((i) => i.state === "error").length;
        return left ? "Uploading " + left + " of " + total.value + "…" : failed ? U.plural(failed, "file") + " didn’t upload" : "Uploaded " + U.plural(total.value, "file");
      });
      return { S, queue, total, open, summary, clearFinished, U, running: computed(() => queue.running) };
    },
    template: `
      <div v-if="total" class="fixed bottom-12 right-5 z-30 w-[340px] rounded-xl bg-surface border border-line shadow-modal overflow-hidden" role="region" aria-label="Uploads" data-upload="tray">
        <div class="flex items-center gap-2 px-3.5 h-10 border-b border-line">
          <ips-icon name="upload-cloud" :size="15"></ips-icon>
          <span class="text-[13px] font-medium" data-upload="summary">{{ summary }}</span>
          <button type="button" @click="open = !open" :aria-label="open ? 'Collapse' : 'Expand'" :class="S.btnGhost" class="ml-auto !px-1.5"><ips-icon :name="open ? 'chevron-down' : 'chevron-left'" :size="15"></ips-icon></button>
          <button v-if="!running" type="button" @click="clearFinished()" aria-label="Dismiss" :class="S.btnGhost" class="!px-1.5"><ips-icon name="x" :size="15"></ips-icon></button>
        </div>
        <ul v-show="open" class="max-h-[260px] overflow-y-auto divide-y divide-line">
          <li v-for="i in queue.items" :key="i.id" class="px-3.5 py-2 text-[12.5px]" :data-state="i.state" data-upload="item">
            <div class="flex items-center gap-2"><span class="truncate flex-1">{{ i.name }}</span><span class="text-muted shrink-0">{{ U.bytesToSize(i.size) }}</span>
              <ips-icon v-if="i.state === 'done'" name="check-circle" :size="14" class="text-accent"></ips-icon>
              <ips-icon v-else-if="i.state === 'error'" name="alert-circle" :size="14" class="text-danger"></ips-icon></div>
            <div v-if="i.state === 'sending' || i.state === 'queued'" class="mt-1 h-1 rounded bg-sidebar overflow-hidden"><div class="h-full bg-accent" :style="{ width: i.pct + '%' }"></div></div>
            <p v-if="i.state === 'error'" class="mt-0.5 text-danger" data-upload="error">{{ i.message }}</p>
          </li>
        </ul>
      </div>`,
  };

  // The desktop-drop overlay: shown while files are dragged over the window, and says where they will go.
  const Drop = {
    name: "ips-drop-overlay",
    setup() {
      const over = ref(false);
      let depth = 0;
      const hasFiles = (e) => e.dataTransfer && Array.from(e.dataTransfer.types || []).includes("Files");
      const enter = (e) => { if (hasFiles(e)) { e.preventDefault(); depth++; over.value = true; } };
      const hover = (e) => { if (hasFiles(e)) e.preventDefault(); };
      const leave = (e) => { if (hasFiles(e) && --depth <= 0) { depth = 0; over.value = false; } };
      const drop = (e) => { if (!hasFiles(e)) return; e.preventDefault(); depth = 0; over.value = false; add(e.dataTransfer.files); };
      onMounted(() => { window.addEventListener("dragenter", enter); window.addEventListener("dragover", hover); window.addEventListener("dragleave", leave); window.addEventListener("drop", drop); });
      onBeforeUnmount(() => { window.removeEventListener("dragenter", enter); window.removeEventListener("dragover", hover); window.removeEventListener("dragleave", leave); window.removeEventListener("drop", drop); });
      return { over, st, ok: computed(can), why, B };
    },
    template: `
      <div v-if="over" class="fixed inset-0 z-40 grid place-items-center bg-[rgba(47,111,126,0.14)] pointer-events-none" data-upload="overlay">
        <div :class="['rounded-2xl border-2 border-dashed px-10 py-8 text-center bg-surface shadow-modal', ok ? 'border-accent' : 'border-danger']">
          <ips-icon :name="ok ? 'upload-cloud' : 'alert-circle'" :size="30" :class="ok ? 'text-accent' : 'text-danger'"></ips-icon>
          <p class="mt-2 font-display text-[22px] font-medium">{{ ok ? 'Drop to upload to ' + (st.cwd.replace(/^files\\//, '').split('/').join(' / ')) : 'Can’t upload here' }}</p>
          <p v-if="!ok" class="mt-1 text-[13px] text-muted">{{ why() }}</p>
        </div>
      </div>`,
  };

  // The Upload button's file picker.
  function pick() {
    const input = document.createElement("input");
    input.type = "file"; input.multiple = true;
    input.onchange = () => add(input.files);
    input.click();
  }

  Object.assign(IPS.browser, { upload: { queue, add, can, why, pick } });
  IPS.components = Object.assign(IPS.components || {}, { "ips-upload-tray": Tray, "ips-drop-overlay": Drop });
})();
