/* IPS file browser, image viewer: a full-window lightbox over the images in the folder being viewed (replaces the
   blueimp gallery of the classic page). Left/Right (or the arrows) step through them, Esc closes, the rotate buttons turn
   the picture for looking at only (the file itself is not changed). */
(function () {
  "use strict";
  const { reactive, computed, watch, onMounted, onBeforeUnmount } = Vue;
  const IPS = window.IPS, S = IPS.S, U = IPS.util, B = IPS.browser, st = B.store;

  const viewer = reactive({ path: null, turn: 0, failed: false });
  const images = computed(() => B.visible.value.filter((n) => B.typeOf(n) === "image"));
  const index = computed(() => images.value.findIndex((n) => n.path === viewer.path));
  const open = (node) => { viewer.path = node.path; viewer.turn = 0; viewer.failed = false; };
  const close = () => { viewer.path = null; };
  const step = (d) => {
    const list = images.value;
    if (list.length < 2 || index.value < 0) return;
    open(list[(index.value + d + list.length) % list.length]);
  };

  // Folders open, images view, anything else opens by its URL in a new tab.
  function openNode(n) {
    if (n.folder) B.go(n.path);
    else if (B.typeOf(n) === "image") open(n);
    else window.open(B.fileUrl(n), "_blank", "noopener");
  }

  const Viewer = {
    name: "ips-viewer",
    setup() {
      const node = computed(() => (viewer.path ? B.nodes.value[viewer.path] : null));
      const title = computed(() => (node.value && st.titles[node.value.path]) || (node.value ? node.value.name : ""));
      const onKey = (e) => {
        if (!viewer.path) return;
        if (e.key === "Escape") close();
        else if (e.key === "ArrowRight") step(1);
        else if (e.key === "ArrowLeft") step(-1);
        else return;
        e.preventDefault(); e.stopPropagation();
      };
      onMounted(() => document.addEventListener("keydown", onKey, true));
      onBeforeUnmount(() => document.removeEventListener("keydown", onKey, true));
      // a file removed or moved away while the viewer is up closes it
      watch(node, (n) => { if (viewer.path && !n) close(); });
      return { S, viewer, node, title, images, index, close, step, B, U };
    },
    template: `
      <div v-if="node" class="fixed inset-0 z-[45] flex flex-col bg-[#1b1916] text-white select-none" role="dialog" aria-modal="true" :aria-label="'Viewing ' + node.name" data-viewer="root">
        <div class="flex items-center gap-3 h-12 px-4 shrink-0 bg-[#2c2925]">
          <div class="min-w-0 flex-1"><span class="font-medium truncate" data-viewer="title">{{ title }}</span><span v-if="title !== node.name" class="ml-2 text-[12px] text-white/60 font-mono">{{ node.name }}</span></div>
          <span class="text-[12.5px] text-white/70" data-viewer="count">{{ index + 1 }} of {{ images.length }}</span>
          <button type="button" @click="viewer.turn -= 90" aria-label="Rotate left" class="p-2 rounded-md hover:bg-[#34302b] cursor-pointer" data-viewer="rotate-left"><ips-icon name="rotate-ccw" :size="16"></ips-icon></button>
          <button type="button" @click="viewer.turn += 90" aria-label="Rotate right" class="p-2 rounded-md hover:bg-[#34302b] cursor-pointer" data-viewer="rotate-right"><ips-icon name="rotate-cw" :size="16"></ips-icon></button>
          <a :href="B.fileUrl(node)" target="_blank" rel="noopener" aria-label="Open the file" class="p-2 rounded-md hover:bg-[#34302b] text-white"><ips-icon name="external-link" :size="16"></ips-icon></a>
          <button type="button" @click="close" aria-label="Close" class="p-2 rounded-md hover:bg-[#34302b] cursor-pointer" data-viewer="close"><ips-icon name="x" :size="16"></ips-icon></button>
        </div>
        <div class="relative flex-1 min-h-0 grid place-items-center p-6" @click.self="close">
          <button v-if="images.length > 1" type="button" @click="step(-1)" aria-label="Previous image" class="absolute left-3 top-1/2 -translate-y-1/2 p-3 rounded-full bg-[#2c2925] hover:bg-[#34302b] cursor-pointer" data-viewer="prev"><ips-icon name="chevron-left" :size="20"></ips-icon></button>
          <img v-if="!viewer.failed" :key="node.path" :src="B.fileUrl(node)" :alt="title" @error="viewer.failed = true" draggable="false"
            class="max-w-full max-h-full object-contain transition-transform duration-200" :style="{ transform: 'rotate(' + viewer.turn + 'deg)' }" data-viewer="image">
          <p v-else class="text-white/70">This image couldn’t be loaded.</p>
          <button v-if="images.length > 1" type="button" @click="step(1)" aria-label="Next image" class="absolute right-3 top-1/2 -translate-y-1/2 p-3 rounded-full bg-[#2c2925] hover:bg-[#34302b] cursor-pointer" data-viewer="next"><ips-icon name="chevron-right" :size="20"></ips-icon></button>
        </div>
      </div>`,
  };

  Object.assign(IPS.browser, { viewer, openNode, closeViewer: close });
  IPS.components = Object.assign(IPS.components || {}, { "ips-viewer": Viewer });
})();
