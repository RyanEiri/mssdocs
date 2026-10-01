/* IPS admin UI, entry point: mounts the view the page asked for (IPS_CONFIG.view) on #app. */
(function () {
  "use strict";
  const IPS = window.IPS;
  const View = IPS.views[IPS.cfg.view];
  if (!View) { document.getElementById("app").textContent = "Unknown view: " + IPS.cfg.view; return; }
  const app = Vue.createApp(View);
  Object.keys(IPS.components).forEach((name) => app.component(name, IPS.components[name]));
  app.mount("#app");
})();
