/* The editor page of the file editor module (file_editor/edit.php): CKEditor in Source mode with its CodeMirror view, or in its visual (WYSIWYG) editor for a type
   whose view is 'wysiwyg' (or a plain text box when CKEditor is not installed), a status strip (lines, and the file type's own live check if its module has one: window.IPS_FE_TYPES[kind].check(text) -> {ok, text}), an "Unsaved
   changes" flag, Ctrl/Cmd+S, a toast, Save and a two-click Delete.
   Configuration comes from window.IPS_FE_EDIT ({csrf, kind, label, mode, view, writable, delete, ckeditor}). The text is saved exactly as it is in the editor
   (in the visual editor, as CKEditor writes the page out).
   An XML document can also be checked against the Menota schema (cfg.validate, from schema.php): the Validate button, and after every save, post the text to validate.php and list each
   problem with its line (a click on the line goes there); it is advice and never stops a save.
   For the artefact pane (artefact-pane.js) it exports window.IPS_FE_FIT (size the page again after the pane is shown, hidden or resized) and, while the Source view is
   open, sends 'ipsfe:cursor' on window ({text, index}: the text and the cursor's offset, a quarter of a second after the cursor stops). */
(function () {
  'use strict';
  var cfg = window.IPS_FE_EDIT;
  if (!cfg) { return; }
  var $ = function (id) { return document.getElementById(id); };
  var form = $('fe-form'), pane = $('fe-pane'), strip = $('fe-strip'), text = $('editor1');
  var editor = null, cm = null, dirty = false, toastTimer = null, checkTimer = null;

  var visual = cfg.view === 'wysiwyg';
  // the CodeMirror plugin's buttons (they show in Source mode)
  var codeTools = ['searchCode', 'CommentSelectedRange', 'UncommentSelectedRange', 'AutoComplete'];
  if (cfg.ckeditor && window.CKEDITOR) {
    CKEDITOR.disableAutoInline = true;
    // every option is set here (customConfig off), so the module does not depend on a config.js someone has edited
    var options = {
      customConfig: '', readOnly: !cfg.writable, extraPlugins: 'codemirror', entities: true, width: '100%',
      // no request to CKEditor's servers for its version notice, and none of its cloud or spell-check services (they would send the page's text away)
      versionCheck: false, removePlugins: 'scayt,wsc,exportpdf,easyimage,cloudservices,uploadimage,uploadfile',
      codemirror: {
        theme: 'default', lineNumbers: true, lineWrapping: true, matchBrackets: true, autoCloseTags: true, autoCloseBrackets: true, enableSearchTools: true,
        enableCodeFolding: false, enableCodeFormatting: false, autoFormatOnStart: false, autoFormatOnModeChange: false, autoFormatOnUncomment: false,
        mode: cfg.mode || 'htmlmixed', showSearchButton: true, showTrailingSpace: true, highlightMatches: true, showFormatButton: false, showCommentButton: true,
        showUncommentButton: true, showAutoCompleteButton: true, styleActiveLine: true, useBeautifyOnStart: false
      }
    };
    if (visual) {
      // The visual editor renders the page, so it never holds script or the places script hides; the server opens a file that has any in Source instead
      // (ips_fe_view in lib.php), so nothing is removed unseen. Everything else HTML allows is kept as it is.
      options.startupMode = 'wysiwyg';
      options.allowedContent = { $1: { elements: CKEDITOR.dtd, attributes: true, styles: true, classes: true } };
      options.disallowedContent = 'script; style; link; meta; base; iframe; frame; object; embed; applet; form; input; button; textarea; select; *[on*]';
      options.toolbar = [['Source'], ['Undo', 'Redo'], ['Format'], ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
        ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'], ['Link', 'Unlink', 'Anchor'], ['Image', 'Table', 'HorizontalRule', 'SpecialChar'],
        codeTools, ['Maximize']];
    } else {
      // Source only, with no button into the visual editor: it would rewrite (and render) XML or a page that holds script
      options.startupMode = 'source';
      options.allowedContent = true;
      options.toolbar = [['Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo', '-'].concat(codeTools, ['-', 'SpecialChar', 'Maximize'])];
    }
    editor = CKEDITOR.replace('editor1', options);
  }

  function inVisual() { return !!editor && editor.mode === 'wysiwyg'; }
  function value() { return inVisual() ? editor.getData() : cm ? cm.getValue() : text.value; }

  // ---- size: the pane fills the window under the bar ------------------------------------------------------------------------
  function fit() {
    var top = pane.getBoundingClientRect().top + window.pageYOffset;
    var height = Math.max(560, window.innerHeight - top - 24);
    pane.style.height = height + 'px';
    var artefact = $('fe-artefact');
    if (artefact) { artefact.style.height = height + 'px'; }
    var problems = $('fe-val'), taken = strip.offsetHeight + (problems && !problems.hidden ? problems.offsetHeight : 0); // the strip and the validation list sit above the editor
    if (!editor) { text.style.height = Math.max(300, height - taken) + 'px'; return; }
    try {
      // CKEditor sizes its contents area (where the CodeMirror view sits) from the height it is given there, and the toolbar and status bar come on top of it
      var space = function (name) { var el = editor.ui.space(name); return el ? el.$.offsetHeight : 0; };
      var inner = Math.max(200, height - taken - space('top') - space('bottom') - 2);
      editor.resize('100%', inner, true);
      if (cm && !inVisual()) { cm.setSize(null, inner); cm.refresh(); }
    } catch (e) { /* the editor is still starting */ }
  }
  window.addEventListener('resize', fit);
  window.IPS_FE_FIT = fit;
  if (editor) { editor.on('instanceReady', fit); }
  fit();

  // ---- status strip and the unsaved flag --------------------------------------------------------------------------------------
  function lines() { $('line-count').textContent = cm && !inVisual() ? cm.lineCount() : value().split('\n').length; }
  function check() {
    var type = (window.IPS_FE_TYPES || {})[cfg.kind];
    if (!type || typeof type.check !== 'function') { return; }
    var result = type.check(value());
    $('wf-dot').classList.toggle('bad', !result.ok);
    $('wf-text').textContent = result.text;
  }
  function touched() { if (!dirty) { dirty = true; $('dirty-flag').hidden = false; } }
  function changed() { touched(); lines(); clearTimeout(checkTimer); checkTimer = setTimeout(check, 400); if (typeof markStale === 'function') { markStale(); } }
  // where the cursor is, for the artefact pane: sent once it has stopped moving, and only in the Source view (the visual editor has no offset in the text)
  var cursorTimer = null;
  function cursorSoon() {
    clearTimeout(cursorTimer);
    cursorTimer = setTimeout(function () {
      if (!cm || inVisual()) { return; }
      window.dispatchEvent(new CustomEvent('ipsfe:cursor', { detail: { text: cm.getValue(), index: cm.indexFromPos(cm.getCursor()) } }));
    }, 250);
  }
  // CodeMirror exists while CKEditor is in Source mode (a new one each time the mode is switched), as window.codemirror_<CKEditor's id>
  function attach() {
    var next = editor ? window['codemirror_' + editor.id] : null;
    if (!next || next === cm) { return; }
    cm = next;
    lines(); check();
    cm.on('change', function (instance, change) { if (change.origin !== 'setValue') { changed(); } else { lines(); } });
    cm.on('cursorActivity', cursorSoon);
    fit();
  }
  if (editor) {
    setInterval(attach, 500);
    // the visual editor's own edits (Source mode reports through CodeMirror); listened to only once the file is loaded, so loading it is not a change
    editor.on('instanceReady', function () {
      editor.on('change', function () { if (inVisual()) { changed(); } });
      lines(); check();
    });
    editor.on('mode', function () { lines(); check(); fit(); });
  } else {
    text.addEventListener('input', changed);
  }
  lines(); check();

  // ---- toast ---------------------------------------------------------------------------------------------------------------
  function toast(message, isError) {
    var t = $('fe-toast');
    t.textContent = message; t.className = isError ? 'err' : ''; t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.hidden = true; }, isError ? 6000 : 3500);
  }
  function reply(r) { return r.json().catch(function () { return { success: false, error: 'The server sent an unexpected reply (' + r.status + ').' }; }); }

  // ---- validation: the Menota schema's verdict, by line ---------------------------------------------------------------------------
  var valBtn = $('fe-val-btn'), valStatus = $('fe-val-status'), valPanel = $('fe-val'), validated = false, stale = false;
  function gotoLine(n) {
    var line = Math.max(0, n - 1);
    if (cm && !inVisual()) { cm.focus(); cm.setCursor({ line: line, ch: 0 }); cm.scrollIntoView({ line: line, ch: 0 }, 120); return; }
    var pos = 0, from = text.value;
    for (var i = 0; i < line; i++) { var nl = from.indexOf('\n', pos); if (nl < 0) { break; } pos = nl + 1; }
    text.focus(); text.setSelectionRange(pos, pos);
  }
  function showPanel(on) { if (valPanel) { valPanel.hidden = !on; fit(); } }
  function markStale() { if (valStatus && validated && !stale) { stale = true; valStatus.className = 'fe-val-status stale'; valStatus.textContent = 'Changed since it was validated'; } }
  function render(d, manual) {
    validated = true; stale = false;
    var title = $('fe-val-title'), list = $('fe-val-list');
    list.innerHTML = '';
    valStatus.hidden = false;
    if (!d.available) { valStatus.className = 'fe-val-status'; valStatus.textContent = 'Validation is not available here'; showPanel(false); return; }
    var label = d.schema ? d.schema.label : 'schema';
    valStatus.title = label;
    if (d.stage === 'size' || d.stage === 'busy' || d.stage === 'engine') {
      // it could not be checked (the one line says why)
      valStatus.className = 'fe-val-status bad'; valStatus.textContent = 'Could not validate';
      title.textContent = d.errors.length ? d.errors[0].message : 'The text could not be validated.';
      showPanel(!!manual);
      return;
    }
    if (d.valid) {
      valStatus.className = 'fe-val-status ok'; valStatus.textContent = 'Valid';
      title.textContent = 'Valid against the ' + label + '.';
      showPanel(!!manual);
      return;
    }
    var n = d.errors.length;
    valStatus.className = 'fe-val-status bad';
    valStatus.textContent = n + (d.truncated ? '+' : '') + (n === 1 ? ' problem' : ' problems');
    title.textContent = (d.stage === 'parse' ? 'Not well-formed XML: ' : d.stage === 'size' ? '' : 'Does not follow the ' + label + ': ') + valStatus.textContent + (d.truncated ? ' (the first ' + n + ' are listed)' : '') + '.';
    d.errors.forEach(function (e) {
      var li = document.createElement('li'), go = document.createElement('button'), msg = document.createElement('span');
      go.type = 'button'; go.className = 'fe-val-line'; go.textContent = e.line ? 'line ' + e.line : 'document';
      if (e.line) { go.addEventListener('click', function () { gotoLine(e.line); }); } else { go.disabled = true; }
      msg.textContent = ' ' + e.message;
      li.appendChild(go); li.appendChild(msg); list.appendChild(li);
    });
    showPanel(manual || !valPanel.hidden);
  }
  function runValidation(manual) {
    valBtn.disabled = true; valStatus.hidden = false; valStatus.className = 'fe-val-status'; valStatus.textContent = 'Validating…';
    return fetch('validate.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ text: value() }) })
      .then(reply)
      .then(function (d) { if (d.success === false) { throw new Error(d.error || 'refused'); } render(d, manual); })
      .catch(function (e) { valStatus.className = 'fe-val-status bad'; valStatus.textContent = 'Could not validate'; if (manual) { toast('Not validated: ' + e.message, true); } })
      .then(function () { valBtn.disabled = false; });
  }
  if (cfg.validate && valBtn) {
    valBtn.addEventListener('click', function () { runValidation(true); });
    valStatus.addEventListener('click', function () { if (valPanel.hidden && validated) { showPanel(true); } else { showPanel(false); } });
    $('fe-val-close').addEventListener('click', function () { showPanel(false); });
  }

  // ---- save without leaving the page ------------------------------------------------------------------------------------------
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = $('save-btn');
    if (btn.disabled) { return; }
    btn.disabled = true; btn.textContent = 'Saving…';
    if (editor) { editor.updateElement(); }
    fetch(form.action, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams(new FormData(form)) })
      .then(reply)
      .then(function (d) {
        if (d.success) { dirty = false; $('dirty-flag').hidden = true; }
        toast(d.success ? 'Saved.' : (d.error || 'Not saved.'), !d.success);
        if (d.success && cfg.validate && valBtn) { runValidation(false); }
      })
      .catch(function () { toast('Not saved: the server could not be reached.', true); })
      .then(function () { btn.disabled = !cfg.writable; btn.textContent = 'Save'; });
  });

  // ---- Delete: the first click asks, the second moves the file to .deleted/ ------------------------------------------------------
  var del = $('delete-btn');
  if (del && cfg.delete) {
    var armed = null;
    del.addEventListener('click', function () {
      if (armed === null) {
        del.textContent = 'Click again to delete';
        armed = setTimeout(function () { armed = null; del.textContent = 'Delete'; }, 4000);
        return;
      }
      clearTimeout(armed); armed = null;
      del.disabled = true; del.textContent = 'Deleting…';
      fetch('delete.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ file: form.elements.file.value }) })
        .then(reply)
        .then(function (d) {
          if (d.success) { dirty = false; window.location.href = './'; return; }
          toast(d.error || 'Not deleted.', true); del.disabled = false; del.textContent = 'Delete';
        })
        .catch(function () { toast('Not deleted: the server could not be reached.', true); del.disabled = false; del.textContent = 'Delete'; });
    });
  }

  // Ctrl/Cmd+S saves
  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && !e.shiftKey && !e.altKey && String(e.key).toLowerCase() === 's') {
      e.preventDefault();
      if (cfg.writable) { form.requestSubmit(); }
    }
  });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
}());
