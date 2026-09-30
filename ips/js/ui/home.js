/* IPS admin UI, home: a card for each module this site has, then any panels the site registers with
   IPS.registerHomePanel({ id, title, component }) from js/site-home.js (icelandicveterans' records search is one). */
(function () {
  "use strict";
  const IPS = window.IPS, CFG = IPS.cfg, S = IPS.S;

  const Home = {
    name: "ips-home",
    setup() {
      const cards = [{ id: "files", title: "Files", text: "Browse, upload and organise the file store.", icon: "folder", href: CFG.base + "browser.php" }];
      if (CFG.admin) cards.push({ id: "users", title: "Users", text: "Add, edit and remove the people who can sign in.", icon: "users", href: CFG.base + "users.php" });
      (CFG.modules || []).forEach((m) => cards.push(m));
      return { cfg: CFG, S, cards, panels: IPS.homePanels };
    },
    template: `
      <div class="min-h-screen flex flex-col bg-paper">
        <ips-app-bar active="home"></ips-app-bar>
        <main class="flex-1 mx-auto w-full max-w-5xl px-6 pt-10 pb-16">
          <h1 class="font-display text-[30px] font-medium tracking-[-0.015em] leading-tight">Welcome, {{ cfg.user }}</h1>
          <p class="mt-1 text-[13.5px] text-muted">{{ cfg.name }}</p>
          <ul class="mt-7 grid gap-2.5 grid-cols-[repeat(auto-fill,minmax(260px,1fr))]">
            <li v-for="c in cards" :key="c.id">
              <a :href="c.href" :class="S.card" class="flex items-start gap-3.5 p-4 no-underline text-ink hover:border-line-strong transition-colors h-full">
                <span class="grid place-items-center w-10 h-10 rounded-[9px] bg-accent-soft text-accent shrink-0"><ips-icon :name="c.icon" :size="20"></ips-icon></span>
                <span class="min-w-0">
                  <span class="block font-display text-[19px] font-medium leading-tight">{{ c.title }}</span>
                  <span class="block mt-1 text-[13px] text-muted">{{ c.text }}</span>
                </span>
              </a>
            </li>
          </ul>
          <section v-for="p in panels" :key="p.id" class="mt-10">
            <h2 v-if="p.title" class="font-display text-[21px] font-medium tracking-[-0.01em]">{{ p.title }}</h2>
            <component :is="p.component"></component>
          </section>
        </main>
        <ips-toasts></ips-toasts>
      </div>`,
  };
  IPS.views = Object.assign(IPS.views || {}, { home: Home });
})();
