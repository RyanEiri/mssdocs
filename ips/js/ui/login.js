/* IPS admin UI, sign-in. A plain form post to login.php (which sets the login cookie and sends the browser on), so it
   works the same with or without the rest of the script. */
(function () {
  "use strict";
  const { onMounted, ref } = Vue;
  const IPS = window.IPS, CFG = IPS.cfg, S = IPS.S;

  const Login = {
    name: "ips-login",
    setup() {
      const username = ref(null);
      onMounted(() => username.value && username.value.focus());
      return { cfg: CFG, S, username };
    },
    template: `
      <main class="min-h-screen grid place-items-center px-5 bg-paper">
        <div class="w-full max-w-[380px]">
          <div class="flex items-center justify-center gap-3 mb-7">
            <img v-if="cfg.logo" :src="cfg.logo" alt="" class="w-9 h-9 rounded-lg">
            <span class="font-display text-[26px] font-medium tracking-[-0.015em]">{{ cfg.name }}</span>
          </div>
          <form method="post" :action="cfg.base + 'login.php'" :class="S.card" class="p-6 grid gap-4 shadow-tree-active">
            <h1 :class="S.eyebrow">Sign in</h1>
            <label class="grid gap-1.5"><span :class="S.label">Username</span>
              <input ref="username" name="username" required autocomplete="username" autocapitalize="off" spellcheck="false" :class="S.input"></label>
            <label class="grid gap-1.5"><span :class="S.label">Password</span>
              <input name="password" type="password" required autocomplete="current-password" :class="S.input"></label>
            <p v-if="cfg.loginError" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[12.5px] text-danger">That username and password didn’t match. Try again.</p>
            <button type="submit" :class="S.btnPrimary" class="w-full">Sign in</button>
          </form>
        </div>
      </main>`,
  };
  IPS.views = Object.assign(IPS.views || {}, { login: Login });
})();
