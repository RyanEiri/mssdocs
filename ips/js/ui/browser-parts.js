/* IPS file browser, parts: the file-type badge, checkbox, breadcrumbs, the folder tree and the three item views
   (cards, list, thumbnails). Presentational: they take props and emit events; browser.js wires them to the store. */
(function () {
  "use strict";
  const IPS = window.IPS, U = IPS.util;
  const B = () => IPS.browser;

  // ---- file-type badge ----------------------------------------------------------------------------------------
  const BADGE = {
    image: "jpg jpeg png gif webp tif tiff bmp", pdf: "pdf", doc: "doc docx rtf odt", xls: "xls xlsx", txt: "txt",
    audio: "mp3 wav m4a wma flac", video: "mp4 mov avi mkv", archive: "zip rar gz 7z",
  };
  const BADGE_OF = {};
  Object.keys(BADGE).forEach((k) => BADGE[k].split(" ").forEach((e) => (BADGE_OF[e] = k)));
  const DARK_TEXT = { image: true, archive: true }; // white text would not read on the yellow and the grey

  const Badge = {
    name: "ips-badge",
    props: { name: String, large: Boolean },
    computed: {
      ext() { return U.extOf(this.name); },
      kind() { return BADGE_OF[this.ext] || "default"; },
      box() {
        const w = this.large ? 52 : 32, h = this.large ? 66 : 40, fold = this.large ? 14 : 10;
        return { w, h, fold, style: { width: w + "px", height: h + "px", background: "var(--ips-ft-" + this.kind + ")", color: DARK_TEXT[this.kind] ? "var(--ips-ink)" : "#fff" } };
      },
    },
    template: `
      <span class="relative inline-block shrink-0 overflow-hidden rounded-[4px]" :style="box.style" aria-hidden="true">
        <span class="absolute top-0 right-0" :style="{ width: 0, height: 0, borderTop: box.fold + 'px solid var(--ips-surface)', borderLeft: box.fold + 'px solid rgba(0,0,0,.2)' }"></span>
        <span class="absolute inset-x-0 text-center font-mono leading-none" :style="{ bottom: (large ? 10 : 6) + 'px', fontSize: (large ? 13 : 9.5) + 'px' }">{{ ext.slice(0, 4) }}</span>
      </span>`,
  };

  const Check = {
    name: "ips-check",
    props: { on: Boolean, label: String },
    emits: ["toggle"],
    template: `
      <button type="button" role="checkbox" :aria-checked="on" :aria-label="label" @click.stop="$emit('toggle')" @dblclick.stop
        :class="['grid place-items-center w-[18px] h-[18px] rounded-[5px] border-[1.5px] shrink-0 cursor-pointer transition-colors', on ? 'bg-accent border-accent text-white' : 'bg-surface border-line-strong hover:border-accent']">
        <ips-icon v-if="on" name="check" :size="12" :stroke="3"></ips-icon>
      </button>`,
  };

  // The striped stand-in shown until a thumbnail loads (and kept when there isn't one).
  const STRIPES = { background: "repeating-linear-gradient(135deg, var(--ips-stripe) 0 6px, var(--ips-sidebar) 6px 12px)" };

  // ---- breadcrumbs --------------------------------------------------------------------------------------------
  const Crumbs = {
    name: "ips-crumbs",
    props: { crumbs: Array },
    emits: ["go"],
    template: `
      <nav aria-label="Folder path" class="flex flex-wrap items-center text-[12.5px] text-muted">
        <template v-for="(c, i) in crumbs" :key="c.path">
          <ips-icon v-if="i" name="chevron-right" :size="12" class="mx-0.5 text-faint"></ips-icon>
          <span v-if="i === crumbs.length - 1" class="px-1.5 py-[3px] text-ink" aria-current="page">{{ c.label }}</span>
          <a v-else :href="'#' + encodeURIComponent(c.path)" @click.prevent="$emit('go', c.path)" class="px-1.5 py-[3px] rounded-[5px] no-underline text-muted hover:bg-sidebar">{{ c.label }}</a>
        </template>
      </nav>`,
  };

  // ---- folder tree --------------------------------------------------------------------------------------------
  const TreeNode = {
    name: "ips-tree-node",
    props: { path: String, depth: { type: Number, default: 0 } },
    emits: ["go"],
    setup(props) {
      const st = IPS.browser.store;
      return {
        st,
        kids: Vue.computed(() => IPS.browser.children(props.path).filter((c) => c.folder).sort(IPS.browser.byName)),
        node: Vue.computed(() => IPS.browser.nodes.value[props.path]),
        toggle() { st.expanded[props.path] = !st.expanded[props.path]; },
      };
    },
    template: `
      <li role="treeitem" :aria-expanded="kids.length ? !!st.expanded[path] : null">
        <div :data-drop="path" :class="['flex items-center h-[30px] rounded-md text-[13px] cursor-pointer select-none', st.cwd === path ? 'bg-surface text-accent-hover font-semibold shadow-tree-active' : 'hover:bg-tree-hover']"
          :style="{ paddingLeft: 4 + depth * 14 + 'px' }" @click="$emit('go', path)">
          <button type="button" :aria-label="st.expanded[path] ? 'Collapse' : 'Expand'" @click.stop="toggle" :class="['grid place-items-center w-4 h-4 text-muted', kids.length ? '' : 'opacity-0 pointer-events-none']">
            <ips-icon :name="st.expanded[path] ? 'chevron-down' : 'chevron-right'" :size="13"></ips-icon>
          </button>
          <ips-icon :name="depth ? 'folder' : 'hard-drive'" :size="15" :class="['mx-1.5', st.cwd === path ? 'text-accent' : 'text-muted']"></ips-icon>
          <span class="truncate">{{ depth ? node.name : 'All files' }}</span>
          <span v-if="node.public" class="ml-1.5 text-accent shrink-0" title="Public: anyone can see the files in this folder"><ips-icon name="globe" :size="12"></ips-icon></span>
        </div>
        <ul v-if="kids.length && st.expanded[path]" role="group">
          <ips-tree-node v-for="k in kids" :key="k.path" :path="k.path" :depth="depth + 1" @go="$emit('go', $event)"></ips-tree-node>
        </ul>
      </li>`,
  };

  // ---- the item views -----------------------------------------------------------------------------------------
  const Items = {
    name: "ips-items",
    props: { items: Array, view: String, selection: Array, titles: Object, sortKey: String, sortDir: Number, searching: Boolean },
    emits: ["pick", "open", "check", "context", "sort", "check-all"],
    setup(props) {
      const U2 = IPS.util;
      const meta = (n) => {
        if (n.folder) { const c = (B().children(n.path) || []).length; return c === 0 ? "Empty" : U2.plural(c, "item"); }
        const t = props.titles[n.path];
        return U2.bytesToSize(n.size) + (t ? " · " + t : "");
      };
      const where = (n) => B().path.parentOf(n.path).replace(/^files\/?/, "") || "All files";
      const isOn = (n) => props.selection.includes(n.path);
      const allOn = Vue.computed(() => props.items.length > 0 && props.items.every((n) => props.selection.includes(n.path)));
      const COLS = [["name", "Name"], ["title", "Title"], ["type", "Type"], ["size", "Size"], ["modified", "Modified"]];
      return { B, U: U2, STRIPES, meta, where, isOn, allOn, COLS, thumb: (n) => B().thumbUrl(n), isImage: (n) => !n.folder && B().typeOf(n) === "image", size: (n) => (n.folder ? (B().children(n.path).length ? U2.plural(B().stats(n.path).files, "file") : "—") : U2.bytesToSize(n.size)) };
    },
    template: `
      <div>
        <!-- cards -->
        <div v-if="view === 'grid'" class="grid gap-2.5 grid-cols-[repeat(auto-fill,minmax(232px,1fr))]">
          <div v-for="n in items" :key="n.path" :data-path="n.path" :data-drop="n.folder ? n.path : null" draggable="true" role="button" tabindex="0"
            :class="['flex items-center gap-3 h-[68px] px-3.5 rounded-[10px] border cursor-pointer', isOn(n) ? 'bg-accent-soft border-accent' : 'bg-surface border-line hover:border-line-strong']"
            @click.stop="$emit('pick', n, $event)" @dblclick="$emit('open', n)" @keydown.enter="$emit('open', n)" @contextmenu.prevent.stop="$emit('context', n, $event)">
            <span v-if="n.folder" class="grid place-items-center w-10 h-10 rounded-[9px] bg-accent-soft text-accent shrink-0"><ips-icon name="folder" :size="20"></ips-icon></span>
            <span v-else-if="isImage(n)" class="relative block w-10 h-10 rounded-md overflow-hidden shrink-0" :style="STRIPES"><img :src="thumb(n)" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover" onerror="this.style.display='none'"></span>
            <ips-badge v-else :name="n.name"></ips-badge>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-[13.5px] font-medium">{{ n.name }}</span>
              <span class="block truncate text-[12px] text-muted">{{ meta(n) }}</span>
              <span v-if="searching" class="block truncate font-mono text-[11px] text-faint">{{ where(n) }}</span>
            </span>
            <span v-if="n.public" class="text-accent shrink-0" title="Public: anyone can see the files in this folder"><ips-icon name="globe" :size="14"></ips-icon></span>
            <ips-check :on="isOn(n)" :label="'Select ' + n.name" @toggle="$emit('check', n)"></ips-check>
          </div>
        </div>

        <!-- list -->
        <div v-else-if="view === 'list'" class="rounded-[10px] border border-line bg-surface overflow-hidden">
          <div class="grid grid-cols-[40px_minmax(0,2.2fr)_minmax(0,2fr)_70px_90px_150px] items-center h-9 pr-3 bg-paper border-b border-line font-mono text-[11px] uppercase tracking-[0.06em] text-muted">
            <span class="grid place-items-center"><ips-check :on="allOn" label="Select all" @toggle="$emit('check-all')"></ips-check></span>
            <button v-for="c in COLS" :key="c[0]" type="button" :disabled="c[0] === 'title'" @click="$emit('sort', c[0])"
              :class="['flex items-center gap-1 text-left uppercase', c[0] === 'title' ? 'cursor-default' : 'hover:text-ink cursor-pointer']">
              {{ c[1] }}<ips-icon v-if="sortKey === c[0]" :name="sortDir > 0 ? 'arrow-up' : 'arrow-down'" :size="11"></ips-icon>
            </button>
          </div>
          <div v-for="n in items" :key="n.path" :data-path="n.path" :data-drop="n.folder ? n.path : null" draggable="true" role="button" tabindex="0"
            :class="['grid grid-cols-[40px_minmax(0,2.2fr)_minmax(0,2fr)_70px_90px_150px] items-center min-h-[46px] pr-3 text-[13px] border-b border-sidebar last:border-0 cursor-pointer', isOn(n) ? 'bg-accent-soft' : 'hover:bg-paper']"
            @click.stop="$emit('pick', n, $event)" @dblclick="$emit('open', n)" @keydown.enter="$emit('open', n)" @contextmenu.prevent.stop="$emit('context', n, $event)">
            <span class="grid place-items-center"><ips-check :on="isOn(n)" :label="'Select ' + n.name" @toggle="$emit('check', n)"></ips-check></span>
            <span class="flex items-center gap-2.5 min-w-0 py-1.5">
              <ips-icon v-if="n.folder" name="folder" :size="16" class="text-accent"></ips-icon><ips-badge v-else :name="n.name" class="!w-[18px] !h-[22px]"></ips-badge>
              <span class="min-w-0"><span class="block truncate font-medium">{{ n.name }}</span><span v-if="searching" class="block truncate font-mono text-[11.5px] text-faint">{{ where(n) }}</span></span>
              <span v-if="n.public" class="text-accent shrink-0" title="Public: anyone can see the files in this folder"><ips-icon name="globe" :size="13"></ips-icon></span>
            </span>
            <span class="truncate text-muted pr-3">{{ n.folder ? '' : titles[n.path] || '' }}</span>
            <span class="font-mono text-[12px] text-muted">{{ n.folder ? 'folder' : U.extOf(n.name) || '—' }}</span>
            <span class="font-mono text-[12px] text-muted">{{ size(n) }}</span>
            <span class="font-mono text-[12px] text-muted">{{ U.fmtEpoch(n.modified) }}</span>
          </div>
        </div>

        <!-- thumbnails -->
        <div v-else class="grid gap-3.5 grid-cols-[repeat(auto-fill,minmax(190px,1fr))]">
          <div v-for="n in items" :key="n.path" :data-path="n.path" :data-drop="n.folder ? n.path : null" draggable="true" role="button" tabindex="0"
            :class="['relative rounded-[10px] border overflow-hidden cursor-pointer bg-surface', isOn(n) ? 'border-accent ring-[3px] ring-accent-ring' : 'border-line hover:border-line-strong']"
            @click.stop="$emit('pick', n, $event)" @dblclick="$emit('open', n)" @keydown.enter="$emit('open', n)" @contextmenu.prevent.stop="$emit('context', n, $event)">
            <div class="relative grid place-items-center aspect-[4/3] bg-sidebar border-b border-line" :style="isImage(n) ? STRIPES : null">
              <ips-icon v-if="n.folder" name="folder" :size="44" class="text-accent"></ips-icon>
              <span v-if="n.public" class="absolute top-2 right-2 text-accent" title="Public: anyone can see the files in this folder"><ips-icon name="globe" :size="16"></ips-icon></span>
              <img v-else-if="isImage(n)" :src="thumb(n)" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover" onerror="this.style.display='none'">
              <ips-badge v-else :name="n.name" large></ips-badge>
            </div>
            <span class="absolute top-2 right-2"><ips-check :on="isOn(n)" :label="'Select ' + n.name" @toggle="$emit('check', n)"></ips-check></span>
            <div class="px-3 py-2.5"><div class="truncate text-[13px] font-medium">{{ n.name }}</div><div class="truncate text-[12px] text-muted">{{ meta(n) }}</div></div>
          </div>
        </div>
      </div>`,
  };

  IPS.components = Object.assign(IPS.components || {}, { "ips-badge": Badge, "ips-check": Check, "ips-crumbs": Crumbs, "ips-tree-node": TreeNode, "ips-items": Items });
})();
