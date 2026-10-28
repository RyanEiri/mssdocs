/* The artefact pane of the file editor (file_editor/edit.php): the object a document describes, as page images (one at a time, with zoom) or a PDF in a frame, beside
   the editor. The sources come from window.IPS_FE_ARTEFACT ({sources: [{id, kind, title, pages: [{url, label, name}], pdf: {url, name} | null}], kind}), found on the
   server by artefact.php: the Projects module's links, the TEI's own facsimile, a folder <id>-pages, or <id>.pdf. A document in several projects has several sources
   and a switcher; the pane shows the first.
   Pages are picked from a list (labels such as "fol. 12r", else the file's name), by the arrows, or with the keys on the page: left and right turn it, + and - zoom.
   Follow cursor: edit-page.js sends 'ipsfe:cursor' ({text, index}) when the cursor in the Source view stops; the nearest <pb n="..."> or <locus from="..."> before it
   names a page, and the page whose label (or file name) is the same is shown ("fol. 12r", "12r" and "0012r" are the same label; a PDF with no page list takes a
   number). The width, whether the pane is shown, the zoom and Follow cursor are remembered in this browser. */
(function () {
  'use strict';
  var cfg = window.IPS_FE_ARTEFACT;
  var $ = function (id) { return document.getElementById(id); };
  var art = $('fe-artefact');
  if (!cfg || !art || !cfg.sources || !cfg.sources.length) { return; }
  var img = $('fe-art-img'), view = $('fe-art-view'), frame = $('fe-art-frame'), error = $('fe-art-error'), picker = $('fe-art-page'), pdfLink = $('fe-art-pdf'),
      sourcePick = $('fe-art-source'), follow = $('fe-art-follow'), toggle = $('fe-art-toggle'), prev = $('fe-art-prev'), next = $('fe-art-next');
  var store = {
    get: function (k, d) { try { var v = localStorage.getItem('ips-fe-art-' + k); return v === null ? d : v; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem('ips-fe-art-' + k, v); } catch (e) { /* private window */ } }
  };
  var WIDTHS = [30, 40, 50, 60, 70], width = parseInt(store.get('width', '45'), 10) || 45;
  var LEVELS = ['fit', 125, 150, 200, 300], zoom = store.get('zoom', 'fit');
  zoom = zoom === 'fit' ? 'fit' : (LEVELS.indexOf(parseInt(zoom, 10)) > 0 ? parseInt(zoom, 10) : 'fit');
  var source = cfg.sources[0], index = 0;

  function fit() { if (window.IPS_FE_FIT) { window.IPS_FE_FIT(); } }

  // ---- layout -----------------------------------------------------------------------------------------------------------
  function shown() { return store.get('visible', '1') === '1'; }
  function layout() {
    var on = shown();
    art.style.display = on ? '' : 'none';
    art.style.flex = '0 0 ' + width + '%';
    toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
    fit();
    if (on) { show(index); }
  }
  toggle.addEventListener('click', function () { store.set('visible', shown() ? '0' : '1'); layout(); });
  $('fe-art-hide').addEventListener('click', function () { store.set('visible', '0'); layout(); });
  $('fe-art-wider').addEventListener('click', function () {
    width = WIDTHS.filter(function (w) { return w > width; })[0] || WIDTHS[WIDTHS.length - 1];
    store.set('width', width); layout();
  });
  $('fe-art-narrower').addEventListener('click', function () {
    var smaller = WIDTHS.filter(function (w) { return w < width; });
    width = smaller.length ? smaller[smaller.length - 1] : WIDTHS[0];
    store.set('width', width); layout();
  });

  // ---- labels ------------------------------------------------------------------------------------------------------------
  // "fol. 12r", "f. 12r", "12r" and "0012r" are one label; so are "p. 3" and "3".
  function norm(s) {
    return String(s || '').toLowerCase().replace(/^(fol(?:io)?|f|pp?|page|bl)\.?\s*(?=\d)/, '').replace(/\s+/g, '').replace(/^0+(?=\d)/, '');
  }
  function stem(name) { return String(name || '').replace(/\.[^.]*$/, ''); }
  function nameOf(page) { return page.label || stem(page.name); }
  function indexOfLabel(label) {
    var want = norm(label), pages = source.pages;
    if (!want) { return -1; }
    for (var i = 0; i < pages.length; i++) {
      if (norm(pages[i].label) === want || norm(stem(pages[i].name)) === want) { return i; }
    }
    return -1;
  }
  // the label named by the nearest <pb n> or <locus from> before the cursor
  function labelAt(text, at) {
    var head = String(text).slice(0, at + 1), best = null, last = -1, m;
    [/<pb\b[^>]*?\sn\s*=\s*"([^"]*)"/g, /<locus\b[^>]*?\sfrom\s*=\s*"([^"]*)"/g].forEach(function (re) {
      while ((m = re.exec(head))) { if (m.index > last) { last = m.index; best = m[1]; } }
    });
    return best;
  }

  // ---- pages -------------------------------------------------------------------------------------------------------------
  function imageMode() { return source.pages.length > 0; }
  function show(i) {
    if (!shown()) { return; }
    var pages = source.pages;
    if (imageMode()) {
      index = Math.min(pages.length - 1, Math.max(0, i));
      picker.value = String(index);
      frame.hidden = true; view.hidden = false;
      error.hidden = true;
      img.hidden = false; img.style.visibility = 'hidden';
      img.src = pages[index].url;
      view.scrollTop = 0;
      // the pages either side are fetched in the background, so turning them is quick
      [index + 1, index - 1].forEach(function (n) { if (n >= 0 && n < pages.length) { new Image().src = pages[n].url; } });
      if (source.pdf) { pdfLink.href = source.pdf.url + '#page=' + (index + 1); }
    } else if (source.pdf) {
      index = Math.max(0, i);
      view.hidden = true; frame.hidden = false;
      var url = source.pdf.url + '#page=' + (index + 1) + '&view=FitH&zoom=page-width';
      if (!frame.getAttribute('src')) { frame.setAttribute('src', url); } else { try { frame.contentWindow.location.replace(url); } catch (e) { frame.setAttribute('src', url); } }
    }
    prev.disabled = index <= 0;
    next.disabled = !imageMode() || index >= pages.length - 1;
  }

  function applyZoom() {
    img.style.width = zoom === 'fit' ? '100%' : zoom + '%';
    var button = $('fe-art-zoom-fit');
    button.textContent = zoom === 'fit' ? 'Fit' : zoom + '%';
  }
  function stepZoom(d) {
    var i = Math.min(LEVELS.length - 1, Math.max(0, LEVELS.indexOf(zoom) + d));
    zoom = LEVELS[i]; store.set('zoom', zoom); applyZoom();
  }

  // ---- one source at a time ----------------------------------------------------------------------------------------------
  function useSource(s) {
    source = s; index = 0;
    picker.innerHTML = '';
    source.pages.forEach(function (page, i) {
      var option = document.createElement('option');
      option.value = String(i);
      option.textContent = (i + 1) + '. ' + nameOf(page);
      picker.appendChild(option);
    });
    var images = imageMode();
    picker.hidden = !images;
    $('fe-art-zoom-group').hidden = !images;
    next.hidden = !images;
    if (images) { frame.removeAttribute('src'); }
    pdfLink.hidden = !(source.pdf && images);
    if (source.pdf) { pdfLink.href = source.pdf.url; }
    $('fe-art-follow-label').hidden = false;
    show(0);
  }
  if (cfg.sources.length > 1) {
    cfg.sources.forEach(function (s, i) {
      var option = document.createElement('option');
      option.value = String(i);
      option.textContent = s.title;
      sourcePick.appendChild(option);
    });
    sourcePick.hidden = false;
    sourcePick.addEventListener('change', function () { useSource(cfg.sources[parseInt(sourcePick.value, 10)]); });
  }

  // ---- controls ----------------------------------------------------------------------------------------------------------
  prev.addEventListener('click', function () { show(index - 1); });
  next.addEventListener('click', function () { show(index + 1); });
  picker.addEventListener('change', function () { show(parseInt(picker.value, 10)); });
  $('fe-art-zoom-in').addEventListener('click', function () { stepZoom(1); });
  $('fe-art-zoom-out').addEventListener('click', function () { stepZoom(-1); });
  $('fe-art-zoom-fit').addEventListener('click', function () { zoom = 'fit'; store.set('zoom', 'fit'); applyZoom(); });
  img.addEventListener('load', function () { img.style.visibility = 'visible'; error.hidden = true; });
  img.addEventListener('error', function () { img.hidden = true; error.hidden = false; });
  view.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { e.preventDefault(); show(index + 1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); show(index - 1); }
    else if (e.key === '+' || e.key === '=') { e.preventDefault(); stepZoom(1); }
    else if (e.key === '-') { e.preventDefault(); stepZoom(-1); }
  });
  follow.checked = store.get('follow', '1') === '1';
  follow.addEventListener('change', function () { store.set('follow', follow.checked ? '1' : '0'); });
  window.addEventListener('ipsfe:cursor', function (e) {
    if (!follow.checked || !shown() || !e.detail) { return; }
    var label = labelAt(e.detail.text, e.detail.index);
    if (label === null) { return; }
    if (imageMode()) {
      var found = indexOfLabel(label);
      if (found >= 0 && found !== index) { show(found); }
    } else if (source.pdf && /^[1-9]\d*$/.test(norm(label)) && parseInt(norm(label), 10) - 1 !== index) {
      show(parseInt(norm(label), 10) - 1);
    }
  });

  applyZoom();
  useSource(source);
  layout();
}());
