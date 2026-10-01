/* IPS file browser, the view: app bar, folder tree, header, toolbar, the items and the status bar, following the
   "Reading room" design handoff. State lives in browser-store.js; the item views are in browser-parts.js. */
(function () {
  "use strict";
  const { onMounted, onBeforeUnmount } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store;

  const FILTERS = [["all", "All"], ["folder", "Folders"], ["image", "Images"], ["doc", "Documents"], ["xml", "XML records"], ["audio", "Audio & video"], ["archive", "Archives"]];
  const SORTS = [["name", "Name"], ["modified", "Modified"], ["size", "Size"], ["type", "Type"]];
  const VIEWS = [["grid", "grid", "Cards"], ["list", "list", "List"], ["thumbs", "image", "Thumbnails"]];

  const Browser = {
    name: "ips-browser",
    setup() {
      const title = Vue.computed(() => (B.searching.value ? "Search results" : st.cwd === B.ROOT ? "All files" : B.path.nameOf(st.cwd)));
      const subtitle = Vue.computed(() => {
        if (B.searching.value) return U.plural(B.matches.value.length, "match", "matches") + " for “" + st.query.trim() + "”";
        const c = B.stats(st.cwd), n = B.inFolder.value.length;
        return n ? U.plural(n, "item") + " · " + U.bytesToSize(c.size) : "Empty folder";
      });
      const summary = Vue.computed(() => {
        const n = B.visible.value.length;
        return U.plural(n, "item") + " · " + st.selection.length + " selected";
      });
      const empty = Vue.computed(() => {
        if (B.visible.value.length) return null;
        if (B.searching.value) return "search";
        if (st.filter !== "all" && B.inFolder.value.length) return "filter";
        return "folder";
      });
      function pick(n, ev) { B.select(n.path, ev); }
      function open(n) { if (n.folder) B.go(n.path); }
      function onKey(e) {
        if (/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) && e.key !== "Escape") return;
        if (e.key === "Escape") {
          if (B.searching.value) st.query = "";
          else B.clearSelection();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "a") {
          e.preventDefault();
          B.selectAll();
        }
      }
      onMounted(() => { document.addEventListener("keydown", onKey); B.load(); });
      onBeforeUnmount(() => document.removeEventListener("keydown", onKey));
      return { S, B, st, FILTERS, SORTS, VIEWS, title, subtitle, summary, empty, pick, open, plural: U.plural };
    },
    template: `
      <div class="h-screen flex flex-col overflow-hidden bg-paper text-ink select-none">
        <ips-app-bar active="files"></ips-app-bar>
        <div class="flex-1 min-h-0 grid" :style="{ gridTemplateColumns: st.panelOpen ? '248px minmax(0, 1fr) 352px' : '248px minmax(0, 1fr)' }">
          <aside class="flex flex-col min-h-0 bg-sidebar border-r border-line" aria-label="Folders">
            <div :class="S.eyebrow" class="px-4 pt-3.5 pb-1.5 !tracking-[0.08em]">Folders</div>
            <ul role="tree" class="flex-1 overflow-y-auto px-2 pb-4"><ips-tree-node v-if="st.ready" :path="B.ROOT" @go="B.go"></ips-tree-node></ul>
          </aside>

          <section class="flex flex-col min-w-0 min-h-0" @click="B.clearSelection()">
            <div class="px-6 pt-[18px]">
              <ips-crumbs :crumbs="B.crumbs.value" @go="B.go"></ips-crumbs>
              <div class="mt-2 flex flex-wrap items-end gap-x-4 gap-y-2">
                <div class="min-w-0 flex-1">
                  <h1 class="font-display text-[30px] font-medium tracking-[-0.015em] leading-[1.1] truncate">{{ title }}</h1>
                  <p class="mt-1 text-[13px] text-muted">{{ subtitle }}
                    <button v-if="B.searching.value" type="button" @click.stop="st.query = ''" class="ml-2 text-[13px] font-medium text-accent hover:underline cursor-pointer">Clear search</button></p>
                </div>
                <label class="relative block w-[280px]" @click.stop>
                  <ips-icon name="search" :size="15" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"></ips-icon>
                  <input v-model="st.query" type="search" placeholder="Search all files and folders" aria-label="Search all files and folders" autocomplete="off"
                    @keydown.esc.stop="st.query = ''" :class="S.input" class="!pl-9 !pr-8">
                  <button v-if="st.query" type="button" aria-label="Clear search" @click="st.query = ''" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted hover:text-ink cursor-pointer"><ips-icon name="x" :size="14"></ips-icon></button>
                </label>
                <button type="button" @click.stop="B.togglePanel()" :aria-pressed="st.panelOpen" :aria-label="st.panelOpen ? 'Hide details' : 'Show details'" title="Details panel"
                  :class="['grid place-items-center w-9 h-9 rounded-lg border border-line cursor-pointer', st.panelOpen ? 'bg-sidebar' : 'bg-surface hover:bg-hover']"><ips-icon name="sidebar" :size="16" class="-scale-x-100"></ips-icon></button>
              </div>
            </div>

            <div class="mt-3.5 px-6 pb-3.5 min-h-[40px] flex items-center gap-3 border-b border-line" @click.stop>
              <div v-if="st.selection.length" class="flex items-center gap-1 h-9 pl-3.5 pr-1.5 rounded-lg bg-ink text-paper text-[13px] shrink-0 whitespace-nowrap">
                <span class="font-medium mr-1.5">{{ st.selection.length }} selected</span>
                <button type="button" @click="B.clearSelection()" class="grid place-items-center h-[26px] px-2 rounded-md hover:bg-ink-hover cursor-pointer" aria-label="Clear selection"><ips-icon name="x" :size="14"></ips-icon></button>
              </div>
              <div class="flex items-center gap-1.5 min-w-0 overflow-x-auto py-0.5" role="group" aria-label="Filter by type">
                <button v-for="f in FILTERS" :key="f[0]" type="button" @click="st.filter = f[0]" :aria-pressed="st.filter === f[0]"
                  :class="['h-7 px-[11px] rounded-[14px] border text-[12.5px] font-medium cursor-pointer transition-colors shrink-0', st.filter === f[0] ? 'bg-ink border-ink text-paper' : 'bg-surface border-line hover:border-line-strong']">{{ f[1] }}</button>
              </div>
              <div class="ml-auto flex items-center gap-2 shrink-0">
                <label class="flex items-center gap-2 text-[12.5px] text-muted">Sort
                  <select v-model="st.sortKey" class="h-[30px] rounded-[7px] border border-line bg-surface px-2 text-[12.5px] text-ink outline-none focus:border-accent">
                    <option v-for="s in SORTS" :key="s[0]" :value="s[0]">{{ s[1] }}</option>
                  </select></label>
                <button type="button" @click="st.sortDir = -st.sortDir" :aria-label="st.sortDir > 0 ? 'Ascending' : 'Descending'" class="grid place-items-center w-[30px] h-[30px] rounded-[7px] border border-line bg-surface hover:bg-hover cursor-pointer"><ips-icon :name="st.sortDir > 0 ? 'arrow-up' : 'arrow-down'" :size="14"></ips-icon></button>
                <div class="flex p-0.5 rounded-lg border border-line bg-surface" role="group" aria-label="View">
                  <button v-for="v in VIEWS" :key="v[0]" type="button" :title="v[2]" :aria-label="v[2]" :aria-pressed="st.view === v[0]" @click="B.setView(v[0])"
                    :class="['grid place-items-center w-[30px] h-[26px] rounded-md cursor-pointer', st.view === v[0] ? 'bg-accent-soft text-accent-hover' : 'text-muted hover:text-ink']"><ips-icon :name="v[1]" :size="15"></ips-icon></button>
                </div>
              </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto px-6 pt-[18px] pb-6">
              <p v-if="st.error" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[13px] text-danger">{{ st.error }}</p>
              <div v-else-if="!st.ready" class="grid gap-2.5 grid-cols-[repeat(auto-fill,minmax(232px,1fr))]"><div v-for="n in 6" :key="n" class="h-[68px] rounded-[10px] bg-sidebar animate-pulse"></div></div>
              <div v-else-if="empty" class="mx-auto max-w-[420px] pt-[72px] text-center">
                <span class="mx-auto grid place-items-center w-16 h-16 rounded-2xl bg-sidebar text-muted"><ips-icon :name="empty === 'search' ? 'search' : empty === 'filter' ? 'filter' : 'inbox'" :size="26"></ips-icon></span>
                <h2 class="mt-4 font-display text-[22px] font-medium">{{ empty === 'search' ? 'No matches for “' + st.query.trim() + '”' : empty === 'filter' ? 'Nothing of that type here' : 'This folder is empty' }}</h2>
                <p class="mt-1.5 text-[13.5px] text-muted">{{ empty === 'folder' ? 'Nothing has been added to this folder yet.' : empty === 'search' ? 'Names are searched in every folder. Try a shorter word.' : 'Try another type, or show everything in this folder.' }}</p>
                <button v-if="empty === 'search'" type="button" @click="st.query = ''" :class="S.btnSecondary" class="mt-4">Clear search</button>
                <button v-if="empty === 'filter'" type="button" @click="st.filter = 'all'" :class="S.btnSecondary" class="mt-4">Show everything</button>
              </div>
              <ips-items v-else :items="B.visible.value" :view="st.view" :selection="st.selection" :titles="st.titles" :sort-key="st.sortKey" :sort-dir="st.sortDir" :searching="B.searching.value"
                @pick="pick" @open="open" @check="(n) => B.toggle(n.path)" @sort="B.setSort" @check-all="st.selection.length === B.visible.value.length ? B.clearSelection() : B.selectAll()"></ips-items>
            </div>

            <footer class="flex items-center gap-4 h-[34px] px-6 border-t border-line text-[12px] text-muted shrink-0" @click.stop>
              <span>{{ summary }}</span>
              <span class="ml-auto hidden lg:inline">⌘/Ctrl-click to add to the selection · Shift-click for a range</span>
            </footer>
          </section>
          <ips-panel v-if="st.panelOpen" @close="B.togglePanel()"></ips-panel>
        </div>
        <ips-toasts></ips-toasts>
      </div>`,
  };
  IPS.views = Object.assign(IPS.views || {}, { browser: Browser });
})();
