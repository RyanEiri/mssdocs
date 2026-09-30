/* IPS admin UI, core: configuration, server calls, formatting helpers, the icon component and the toast store.
   Every UI script shares one namespace, window.IPS (plain scripts, no bundler). Needs Vue (global build) and
   window.IPS_CONFIG from html/app-foot.php. */
(function () {
  "use strict";
  const { reactive, h } = Vue;

  const CFG = Object.assign(
    { view: "home", user: "", admin: false, csrf: "", base: "./", site: "/", name: "", logo: null, modules: [], loginError: false },
    window.IPS_CONFIG || {}
  );

  // ---- server calls -------------------------------------------------------------------------------------------
  // All state-changing requests carry the per-user CSRF token; the endpoints refuse them without it.
  function parse(r) {
    return r.text().then((t) => {
      // A PHP notice printed ahead of the JSON must not break the page: take the JSON from its first brace.
      const i = t.search(/[{[]/);
      try { return JSON.parse(i > 0 ? t.slice(i) : t); } catch (e) { throw new Error("bad response (" + r.status + ")"); }
    });
  }
  function getJSON(path, params) {
    const q = params ? "?" + new URLSearchParams(params) : "";
    return fetch(CFG.base + path + q, { credentials: "same-origin", headers: { Accept: "application/json" } }).then(parse);
  }
  function postForm(path, data) {
    return fetch(CFG.base + path, {
      method: "POST", credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json", "X-CSRF": CFG.csrf },
      body: new URLSearchParams(flatten(data)),
    }).then(parse);
  }
  function postJSON(path, data) {
    return fetch(CFG.base + path, {
      method: "POST", credentials: "same-origin",
      headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF": CFG.csrf },
      body: JSON.stringify(data),
    }).then(parse);
  }
  // {files: [{dirname: "a"}]} -> {"files[0][dirname]": "a"}: the nesting PHP's $_POST expects from a form post.
  function flatten(obj, prefix, out) {
    out = out || {};
    Object.keys(obj).forEach((k) => {
      const key = prefix ? prefix + "[" + k + "]" : k, v = obj[k];
      if (v !== null && typeof v === "object") flatten(v, key, out); else out[key] = v == null ? "" : String(v);
    });
    return out;
  }

  // ---- formatting ---------------------------------------------------------------------------------------------
  const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  function bytesToSize(n) {
    n = Number(n) || 0;
    if (n < 1024) return n + " B";
    const u = ["KB", "MB", "GB", "TB"];
    let i = -1;
    do { n /= 1024; i++; } while (n >= 1024 && i < u.length - 1);
    return (n < 10 ? n.toFixed(1) : Math.round(n)) + " " + u[i];
  }
  function parseTime(t) { if (!t) return null; const d = new Date(String(t).replace(" ", "T")); return isNaN(d) ? null : d; }
  function fmtDate(t) { const d = parseTime(t); return d ? d.getDate() + " " + MONTHS[d.getMonth()] + " " + d.getFullYear() : "—"; }
  const plural = (n, one, many) => n + " " + (n === 1 ? one : many || one + "s");

  // ---- file types ---------------------------------------------------------------------------------------------
  const TYPE_EXT = {
    image: "jpg jpeg png gif webp tif tiff bmp",
    doc: "pdf doc docx txt rtf odt xls xlsx",
    xml: "xml html xhtml json",
    audio: "mp3 wav m4a wma flac mp4 mov avi mkv",
    archive: "zip rar gz 7z",
  };
  const EXT_TYPE = {};
  Object.keys(TYPE_EXT).forEach((t) => TYPE_EXT[t].split(" ").forEach((e) => (EXT_TYPE[e] = t)));
  const extOf = (name) => { const m = /\.([^.\/]+)$/.exec(String(name || "")); return m ? m[1].toLowerCase() : ""; };
  const typeOf = (name) => EXT_TYPE[extOf(name)] || "other";

  // ---- icons --------------------------------------------------------------------------------------------------
  // Feather icons from ips-icons.js, drawn as inline SVG that takes the surrounding text colour.
  const Icon = {
    name: "ips-icon",
    props: { name: { type: String, required: true }, size: { type: [Number, String], default: 16 }, stroke: { type: [Number, String], default: 2 } },
    render() {
      return h("svg", {
        xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 24 24", width: this.size, height: this.size, fill: "none",
        stroke: "currentColor", "stroke-width": this.stroke, "stroke-linecap": "round", "stroke-linejoin": "round",
        "aria-hidden": "true", class: "shrink-0", innerHTML: (window.IPS_ICONS || {})[this.name] || "",
      });
    },
  };

  // ---- toasts -------------------------------------------------------------------------------------------------
  // At most 3 at once, each gone after 6 s; an optional undo callback adds an Undo link.
  const toasts = reactive([]);
  let toastId = 0;
  function toast(msg, opts) {
    opts = opts || {};
    const t = { id: ++toastId, msg, icon: opts.icon || "check-circle", undo: opts.undo || null };
    toasts.push(t);
    while (toasts.length > 3) toasts.shift();
    setTimeout(() => dismissToast(t.id), opts.ms || 6000);
    return t.id;
  }
  function dismissToast(id) { const i = toasts.findIndex((t) => t.id === id); if (i >= 0) toasts.splice(i, 1); }

  window.IPS = Object.assign(window.IPS || {}, {
    cfg: CFG, getJSON, postForm, postJSON,
    util: { bytesToSize, fmtDate, parseTime, plural, extOf, typeOf, TYPE_EXT },
    Icon, toasts, toast, dismissToast,
    homePanels: [],
    registerHomePanel(panel) { window.IPS.homePanels.push(panel); },
  });
})();
