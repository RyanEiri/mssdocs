/* IPS admin UI, shell: the app bar (with "View site", a way back to the site's public pages at cfg.site), modal and toast components and the shared Tailwind class sets, so every view
   uses the same buttons, inputs and cards. Layout and tokens follow the "Reading room" design handoff. */
(function () {
  "use strict";
  const { onMounted, onBeforeUnmount } = Vue;
  const IPS = window.IPS, CFG = IPS.cfg;

  // Class sets. Tailwind only emits classes it can see as whole strings in these sources (tools/build-ui-css.sh).
  const S = {
    btn: "inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg text-[13px] font-medium border transition-colors disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer",
    btnPrimary: "inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg text-[13px] font-medium border border-accent bg-accent text-white hover:bg-accent-hover hover:border-accent-hover transition-colors disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer",
    btnSecondary: "inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg text-[13px] font-medium border border-line-strong bg-surface text-ink hover:bg-hover transition-colors disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer",
    btnDanger: "inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg text-[13px] font-medium border border-danger bg-danger text-white hover:opacity-90 transition-colors disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer",
    btnGhost: "inline-flex items-center gap-1.5 h-8 px-2.5 rounded-md text-[13px] font-medium text-muted hover:bg-hover hover:text-ink transition-colors disabled:opacity-45 disabled:cursor-not-allowed cursor-pointer",
    input: "h-9 w-full rounded-lg border border-line bg-surface px-3 text-[13.5px] text-ink outline-none focus:border-accent focus:ring-[3px] focus:ring-accent-ring disabled:bg-paper disabled:text-muted",
    label: "text-[12.5px] font-medium text-ink",
    eyebrow: "font-mono text-[10.5px] font-medium uppercase tracking-[0.08em] text-muted",
    err: "text-[12.5px] text-danger",
    card: "rounded-[10px] border border-line bg-surface",
  };

  const AppBar = {
    name: "ips-app-bar",
    props: { active: { type: String, default: "home" } },
    setup() {
      const links = [
        { id: "home", label: "Home", href: CFG.base + "index.php" },
        { id: "files", label: "Files", href: CFG.base + "browser.php" },
      ];
      if (CFG.admin) links.push({ id: "users", label: "Users", href: CFG.base + "users.php" });
      return { cfg: CFG, S, links, signOut: CFG.base + "index.php?header=cookieDel", initial: (CFG.user || "?").charAt(0).toUpperCase() };
    },
    template: `
      <header class="flex items-center gap-7 h-14 px-5 shrink-0 bg-surface border-b border-line">
        <a :href="cfg.base + 'index.php'" class="flex items-center gap-2.5 font-display text-[19px] font-medium tracking-[-0.01em] text-ink no-underline">
          <img v-if="cfg.logo" :src="cfg.logo" alt="" class="w-7 h-7 rounded-md">
          <span>{{ cfg.name }}</span>
        </a>
        <nav class="flex items-center gap-1" aria-label="Main">
          <a v-for="l in links" :key="l.id" :href="l.href" :aria-current="l.id === active ? 'page' : null"
            :class="['px-2.5 py-1.5 rounded-md text-[13px] no-underline transition-colors', l.id === active ? 'bg-sidebar font-medium text-ink' : 'text-muted hover:bg-hover']">{{ l.label }}</a>
        </nav>
        <div class="ml-auto flex items-center gap-2.5 text-[13px]">
          <a :href="cfg.site" class="inline-flex items-center gap-1.5 text-muted hover:text-ink no-underline"><ips-icon name="globe" :size="14"></ips-icon>View site</a>
          <span class="grid place-items-center w-[26px] h-[26px] rounded-full bg-accent-soft text-accent-hover text-[12px] font-semibold" aria-hidden="true">{{ initial }}</span>
          <span class="text-ink">{{ cfg.user }}</span>
          <span v-if="cfg.admin" class="font-mono text-[10px] uppercase tracking-[0.06em] border border-line rounded px-1.5 py-px text-muted">Admin</span>
          <a :href="signOut" class="ml-1.5 inline-flex items-center gap-1.5 text-muted hover:text-ink no-underline"><ips-icon name="log-out" :size="14"></ips-icon>Sign out</a>
        </div>
      </header>`,
  };

  // A dialog: overlay, card, title, body slot and a footer slot. Esc, the overlay and the close button emit "close".
  const Modal = {
    name: "ips-modal",
    props: { title: String, subtitle: String, width: { type: Number, default: 480 }, busy: Boolean },
    emits: ["close"],
    setup(props, { emit }) {
      const onKey = (e) => { if (e.key === "Escape" && !props.busy) { e.stopPropagation(); emit("close"); } };
      onMounted(() => document.addEventListener("keydown", onKey, true));
      onBeforeUnmount(() => document.removeEventListener("keydown", onKey, true));
      return { S, close: () => { if (!props.busy) emit("close"); } };
    },
    template: `
      <div class="fixed inset-0 z-40 flex p-4 overflow-y-auto bg-[rgba(42,38,34,0.32)]" @mousedown.self="close">
        <div role="dialog" aria-modal="true" :aria-label="title" class="m-auto w-full rounded-[14px] bg-surface shadow-modal animate-rise overflow-hidden" :style="{ maxWidth: width + 'px' }">
          <div class="px-6 pt-5 pb-1 flex items-start gap-3">
            <div class="min-w-0 flex-1">
              <h2 class="font-display text-[23px] font-medium tracking-[-0.01em] leading-tight">{{ title }}</h2>
              <p v-if="subtitle" class="mt-1 text-[13px] text-muted break-words">{{ subtitle }}</p>
            </div>
            <button type="button" @click="close" aria-label="Close" :class="S.btnGhost" class="!px-1.5"><ips-icon name="x" :size="16"></ips-icon></button>
          </div>
          <div class="px-6 py-4"><slot></slot></div>
          <div v-if="$slots.footer" class="px-6 py-3.5 bg-paper border-t border-line flex justify-end gap-2"><slot name="footer"></slot></div>
        </div>
      </div>`,
  };

  const Toasts = {
    name: "ips-toasts",
    setup() { return { toasts: IPS.toasts, dismiss: IPS.dismissToast }; },
    template: `
      <div class="fixed bottom-12 left-1/2 -translate-x-1/2 z-50 flex flex-col items-center gap-2 pointer-events-none" aria-live="polite">
        <div v-for="t in toasts" :key="t.id" role="status" class="pointer-events-auto flex items-center gap-2.5 min-h-[42px] pl-3.5 pr-2 rounded-[10px] bg-ink text-paper text-[13px] shadow-toast animate-rise">
          <ips-icon :name="t.icon" :size="15"></ips-icon>
          <span>{{ t.msg }}</span>
          <button v-if="t.undo" type="button" @click="t.undo(); dismiss(t.id)" class="ml-1 font-semibold text-on-ink-link hover:underline cursor-pointer">Undo</button>
          <button type="button" @click="dismiss(t.id)" aria-label="Dismiss" class="p-1.5 rounded-md hover:bg-ink-hover cursor-pointer"><ips-icon name="x" :size="13"></ips-icon></button>
        </div>
      </div>`,
  };

  IPS.S = S;
  IPS.components = Object.assign(IPS.components || {}, { "ips-icon": IPS.Icon, "ips-app-bar": AppBar, "ips-modal": Modal, "ips-toasts": Toasts });
})();
