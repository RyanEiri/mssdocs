/* IPS admin UI, users (admin only): the list, and the add / edit / remove dialogs. Same server contract as the
   classic page: form posts with the CSRF header; a blank password on edit keeps the current one; the server refuses
   removing yourself or the last admin and says why. */
(function () {
  "use strict";
  const { ref, reactive, onMounted, nextTick } = Vue;
  const IPS = window.IPS, CFG = IPS.cfg, S = IPS.S, fmtDate = IPS.util.fmtDate;

  const Users = {
    name: "ips-users",
    setup() {
      const users = ref([]), loading = ref(false), dlg = ref(null), saving = ref(false), target = ref(null);
      const form = reactive({ id: null, username: "", email: "", password: "", cpassword: "", admin: false });
      const errors = reactive({});
      const first = ref(null);
      const clearErrors = () => Object.keys(errors).forEach((k) => delete errors[k]);

      function load() {
        loading.value = true;
        IPS.getJSON("json/json-user_list.php")
          .then((d) => { users.value = (d.users || []).slice().sort((a, b) => a.username.localeCompare(b.username)); })
          .catch(() => IPS.toast("Users couldn’t be loaded.", { icon: "alert-circle" }))
          .finally(() => (loading.value = false));
      }
      function openUser(u) {
        Object.assign(form, { id: u ? u.id : null, username: u ? u.username : "", email: u ? u.email || "" : "", password: "", cpassword: "", admin: u ? !!u.admin : false });
        clearErrors();
        dlg.value = "user";
        nextTick(() => first.value && first.value.focus());
      }
      function askRemove(u) { target.value = u; dlg.value = "remove"; }
      function close() { if (!saving.value) dlg.value = null; }

      function save() {
        clearErrors();
        if (form.password !== form.cpassword) { errors.cpassword = "Passwords don’t match."; return; }
        const payload = { username: form.username, email: form.email, password: form.password, cpassword: form.cpassword, admin: String(!!form.admin) };
        const self = form.id != null && users.value.some((u) => u.id === form.id && u.username === CFG.user);
        const signOut = self && (form.password || form.username !== CFG.user);
        saving.value = true;
        IPS.postForm(form.id != null ? "json/json-change_user.php" : "json/json-add_user.php", form.id != null ? Object.assign({ id: form.id }, payload) : payload)
          .then((d) => {
            if (d && d.success) {
              dlg.value = null;
              if (signOut) { IPS.toast("Saved. Sign in again with your new details."); setTimeout(() => (location.href = CFG.base + "login.php"), 1600); return; }
              IPS.toast(form.id != null ? "Changes saved." : "User added.");
              load();
              return;
            }
            const e = (d && d.errors) || {};
            ["username", "email", "password", "cpassword"].forEach((k) => { if (e[k]) errors[k] = e[k]; });
            if (e.duplicate) errors.username = e.duplicate;
            if (e.database) errors.general = e.database;
            if (e.id) errors.general = e.id;
            if (!Object.keys(errors).length) errors.general = "Couldn’t save. Try again.";
          })
          .catch(() => (errors.general = "Couldn’t reach the server. Try again."))
          .finally(() => (saving.value = false));
      }
      function remove() {
        const u = target.value;
        saving.value = true;
        IPS.postForm("json/json-remove_user.php", { id: u.id })
          .then((d) => {
            if (d && d.success) { dlg.value = null; IPS.toast(u.username + " removed."); load(); return; }
            const e = (d && d.errors) || {};
            IPS.toast(e.id || "Couldn’t remove " + u.username + ".", { icon: "alert-circle" });
            dlg.value = null;
          })
          .catch(() => IPS.toast("Couldn’t reach the server.", { icon: "alert-circle" }))
          .finally(() => (saving.value = false));
      }
      onMounted(load);
      return { cfg: CFG, S, users, loading, dlg, saving, target, form, errors, first, openUser, askRemove, close, save, remove, fmtDate };
    },
    template: `
      <div class="min-h-screen flex flex-col bg-paper">
        <ips-app-bar active="users"></ips-app-bar>
        <main class="flex-1 mx-auto w-full max-w-5xl px-6 pt-10 pb-16">
          <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
              <h1 class="font-display text-[30px] font-medium tracking-[-0.015em] leading-tight">Users</h1>
              <p class="mt-1 text-[13.5px] text-muted">Everyone who can sign in. Admins can also manage users and remove files.</p>
            </div>
            <button type="button" @click="openUser(null)" :class="S.btnPrimary"><ips-icon name="plus" :size="15"></ips-icon>Add user</button>
          </div>

          <div :class="S.card" class="mt-7 overflow-x-auto">
            <table class="w-full text-left text-[13.5px] border-collapse">
              <thead>
                <tr class="border-b border-line bg-paper">
                  <th :class="S.eyebrow" class="px-5 py-3 font-medium">Username</th>
                  <th :class="S.eyebrow" class="px-5 py-3 font-medium">Email</th>
                  <th :class="S.eyebrow" class="px-5 py-3 font-medium">Role</th>
                  <th :class="S.eyebrow" class="px-5 py-3 font-medium hidden md:table-cell">Added</th>
                  <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
              </thead>
              <tbody v-if="loading && !users.length" class="divide-y divide-line">
                <tr v-for="n in 3" :key="n"><td v-for="c in 4" :key="c" class="px-5 py-4"><div class="h-3.5 w-2/3 rounded bg-sidebar animate-pulse"></div></td><td></td></tr>
              </tbody>
              <tbody v-else class="divide-y divide-line">
                <tr v-for="u in users" :key="u.id" class="hover:bg-paper">
                  <td class="px-5 py-3 font-medium whitespace-nowrap">{{ u.username }}<span v-if="u.username === cfg.user" class="ml-1.5 text-[12.5px] font-normal text-muted">(you)</span></td>
                  <td class="px-5 py-3 text-muted">{{ u.email || '—' }}</td>
                  <td class="px-5 py-3">
                    <span v-if="u.admin" class="inline-flex items-center h-6 px-2 rounded bg-accent-soft text-accent-hover text-[12px] font-semibold">Admin</span>
                    <span v-else class="inline-flex items-center h-6 px-2 rounded bg-sidebar text-muted text-[12px] font-semibold">Standard</span>
                  </td>
                  <td class="px-5 py-3 text-muted tabular-nums whitespace-nowrap hidden md:table-cell">{{ fmtDate(u.date) }}</td>
                  <td class="px-3 py-2 text-right whitespace-nowrap">
                    <button type="button" @click="openUser(u)" :class="S.btnGhost">Edit</button>
                    <button type="button" @click="askRemove(u)" :disabled="u.username === cfg.user" :title="u.username === cfg.user ? 'You can’t remove yourself' : null" :class="S.btnGhost" class="hover:!text-danger">Remove</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="!loading && !users.length" class="px-5 py-10 text-center text-[13.5px] text-muted">No users found.</p>
          </div>
        </main>

        <ips-modal v-if="dlg === 'user'" :title="form.id ? 'Edit user' : 'Add user'" :width="460" :busy="saving" @close="close">
          <form id="user-form" @submit.prevent="save" novalidate class="grid gap-4">
            <label class="grid gap-1.5"><span :class="S.label">Username</span>
              <input ref="first" v-model.trim="form.username" autocomplete="off" autocapitalize="off" spellcheck="false" :class="S.input">
              <span v-if="errors.username" :class="S.err">{{ errors.username }}</span></label>
            <label class="grid gap-1.5"><span :class="S.label">Email</span>
              <input v-model.trim="form.email" type="email" autocomplete="off" :class="S.input">
              <span v-if="errors.email" :class="S.err">{{ errors.email }}</span></label>
            <div class="grid sm:grid-cols-2 gap-4">
              <label class="grid gap-1.5 content-start"><span :class="S.label">{{ form.id ? 'New password' : 'Password' }}</span>
                <input v-model="form.password" type="password" autocomplete="new-password" :class="S.input">
                <span v-if="errors.password" :class="S.err">{{ errors.password }}</span></label>
              <label class="grid gap-1.5 content-start"><span :class="S.label">Confirm</span>
                <input v-model="form.cpassword" type="password" autocomplete="new-password" :class="S.input">
                <span v-if="errors.cpassword" :class="S.err">{{ errors.cpassword }}</span></label>
            </div>
            <p class="-mt-1 text-[12.5px] text-muted">{{ form.id ? 'Leave blank to keep the current password.' : 'At least 8 characters, with a number, a capital and a lowercase letter.' }}</p>
            <label class="flex items-start gap-3 cursor-pointer">
              <input v-model="form.admin" type="checkbox" class="mt-0.5 w-4 h-4 accent-accent cursor-pointer">
              <span><span class="block text-[13.5px] font-medium">Admin</span><span class="block text-[12.5px] text-muted">Can manage users and remove files.</span></span>
            </label>
            <p v-if="errors.general" role="alert" class="rounded-lg bg-danger-soft px-3 py-2 text-[12.5px] text-danger">{{ errors.general }}</p>
          </form>
          <template #footer>
            <button type="button" @click="close" :class="S.btnSecondary">Cancel</button>
            <button type="submit" form="user-form" :disabled="saving" :class="S.btnPrimary">{{ saving ? 'Saving…' : (form.id ? 'Save changes' : 'Add user') }}</button>
          </template>
        </ips-modal>

        <ips-modal v-if="dlg === 'remove'" :title="'Remove ' + (target && target.username) + '?'" subtitle="They lose access to the admin tools right away. This can’t be undone." :width="440" :busy="saving" @close="close">
          <template #footer>
            <button type="button" @click="close" :class="S.btnSecondary">Cancel</button>
            <button type="button" @click="remove" :disabled="saving" :class="S.btnDanger">{{ saving ? 'Removing…' : 'Remove user' }}</button>
          </template>
        </ips-modal>
        <ips-toasts></ips-toasts>
      </div>`,
  };
  IPS.views = Object.assign(IPS.views || {}, { users: Users });
})();
