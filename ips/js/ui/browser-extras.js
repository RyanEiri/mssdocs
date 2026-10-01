/* IPS file browser, pointer extras: the right-click menu and drag-and-drop moves between folders. Both act on the
   selection (a right-click or a drag on an unselected item selects it first) and go through the same actions and
   rules as the dialogs, so the server's confinement and ownership checks decide what is allowed. */
(function () {
  "use strict";
  const { reactive, computed, onMounted, onBeforeUnmount } = Vue;
  const IPS = window.IPS, S = IPS.S, B = IPS.browser, st = B.store, A = B.actions;
  const { parentOf, within } = B.path;

  const menu = reactive({ open: false, x: 0, y: 0 });
  const selected = () => st.selection.map((p) => B.nodes.value[p]).filter(Boolean);

  function showMenu(node, ev) {
    if (!st.selection.includes(node.path)) st.selection = [node.path];
    menu.x = Math.min(ev.clientX, window.innerWidth - 220);
    menu.y = Math.min(ev.clientY, window.innerHeight - 230);
    menu.open = true;
  }

  const Menu = {
    name: "ips-context-menu",
    setup() {
      const nodes = computed(() => (menu.open ? selected() : []));
      const one = computed(() => (nodes.value.length === 1 ? nodes.value[0] : null));
      const items = computed(() => {
        const list = [], n = one.value;
        if (n) list.push({ id: "open", icon: n.folder ? "folder" : B.typeOf(n) === "image" ? "eye" : "external-link", label: n.folder ? "Open" : B.typeOf(n) === "image" ? "View" : "Open file", run: () => B.openNode(n) });
        if (n && n.folder) list.push({ id: "zip", icon: "download", label: "Download as zip", run: () => B.openDialog("zip", [n]) });
        if (n) list.push({ id: "details", icon: "info", label: "Details", run: () => { st.panelOpen = true; } });
        if (B.can.move(nodes.value)) list.push({ id: "move", icon: "move", label: nodes.value.length > 1 ? "Move " + nodes.value.length + " items…" : "Move…", run: () => B.openDialog("move", nodes.value) });
        if (B.can.remove(nodes.value)) list.push({ id: "remove", icon: "trash-2", label: nodes.value.length > 1 ? "Remove " + nodes.value.length + " items…" : "Remove…", danger: true, run: () => B.openDialog("remove", nodes.value) });
        return list;
      });
      const hide = () => { menu.open = false; };
      const onKey = (e) => { if (menu.open && e.key === "Escape") { e.stopPropagation(); hide(); } };
      onMounted(() => { document.addEventListener("keydown", onKey, true); window.addEventListener("scroll", hide, true); window.addEventListener("resize", hide); });
      onBeforeUnmount(() => { document.removeEventListener("keydown", onKey, true); window.removeEventListener("scroll", hide, true); window.removeEventListener("resize", hide); });
      return { menu, items, hide, S, run: (it) => { it.run(); hide(); } };
    },
    template: `
      <div v-if="menu.open" class="fixed inset-0 z-[42]" @mousedown="hide" @contextmenu.prevent="hide">
        <ul role="menu" class="absolute min-w-[200px] py-1 rounded-[10px] bg-surface border border-line shadow-modal text-[13px]" :style="{ left: menu.x + 'px', top: menu.y + 'px' }" @mousedown.stop data-menu="root">
          <li v-for="it in items" :key="it.id" role="none">
            <button type="button" role="menuitem" @click="run(it)" :data-menu="it.id" :class="['flex w-full items-center gap-2.5 px-3 h-8 text-left cursor-pointer hover:bg-hover', it.danger ? 'text-danger' : 'text-ink']"><ips-icon :name="it.icon" :size="14"></ips-icon>{{ it.label }}</button>
          </li>
        </ul>
      </div>`,
  };

  // Drag and drop: cards and rows are draggable (data-path), folders in the pane and the tree (data-drop) are the targets.
  // The highlight is an inline outline so it needs no stylesheet rebuild.
  let dragging = [];
  const targetOf = (el) => { const t = el && el.closest && el.closest("[data-drop]"); return t ? t.dataset.drop : null; };
  const ok = (dest) => dragging.length > 0 && B.canWrite(dest) && dragging.every((n) => B.canWrite(n.path) && parentOf(n.path) !== dest && !(n.folder && within(dest, n.path)));
  const mark = (el, on) => { if (el) el.style.outline = on ? "2px solid var(--ips-accent)" : ""; };
  let lit = null;

  function dnd() {
    const start = (e) => {
      const card = e.target.closest && e.target.closest("[data-path]");
      if (!card || !e.dataTransfer || Array.from(e.dataTransfer.types || []).includes("Files")) return;
      const node = B.nodes.value[card.dataset.path];
      if (!node) return;
      if (!st.selection.includes(node.path)) st.selection = [node.path];
      dragging = selected();
      e.dataTransfer.setData("text/plain", dragging.map((n) => n.name).join("\n"));
      e.dataTransfer.effectAllowed = "move";
    };
    const over = (e) => {
      if (!dragging.length) return;
      const dest = targetOf(e.target), el = dest && e.target.closest("[data-drop]");
      if (lit && lit !== el) mark(lit, false);
      if (dest && ok(dest)) { e.preventDefault(); e.dataTransfer.dropEffect = "move"; mark(el, true); lit = el; } else lit = null;
    };
    const end = () => { dragging = []; mark(lit, false); lit = null; };
    const drop = (e) => {
      if (!dragging.length) return;
      const dest = targetOf(e.target);
      const moving = dragging;
      const allowed = dest && ok(dest);
      end();
      if (!allowed) return;
      e.preventDefault();
      A.moveWithUndo(moving, dest).then((r) => { if (r.ok) st.selection = []; else IPS.toast(r.message, { icon: "alert-circle" }); });
    };
    document.addEventListener("dragstart", start);
    document.addEventListener("dragover", over);
    document.addEventListener("dragend", end);
    document.addEventListener("drop", drop);
    return () => { document.removeEventListener("dragstart", start); document.removeEventListener("dragover", over); document.removeEventListener("dragend", end); document.removeEventListener("drop", drop); };
  }

  Object.assign(IPS.browser, { showMenu, dnd });
  IPS.components = Object.assign(IPS.components || {}, { "ips-context-menu": Menu });
})();
