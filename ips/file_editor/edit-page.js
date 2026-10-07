/* The editor page of the file editor module (file_editor/edit.php): CKEditor in Source mode with its CodeMirror view, or in its visual (WYSIWYG) editor for a type
   whose view is 'wysiwyg' (or a plain text box when CKEditor is not installed), a status strip (lines, and the file type's own live check if its module has one: window.IPS_FE_TYPES[kind].check(text) -> {ok, text}), an "Unsaved
   changes" flag, Ctrl/Cmd+S, a toast, Save and a two-click Delete.
   Configuration comes from window.IPS_FE_EDIT ({csrf, kind, label, mode, view, writable, delete, ckeditor}). The text is saved exactly as it is in the editor
   (in the visual editor, as CKEditor writes the page out). */
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
    if (!editor) { text.style.height = Math.max(300, height - strip.offsetHeight) + 'px'; return; }
    try {
      // CKEditor sizes its contents area (where the CodeMirror view sits) from the height it is given there, and the toolbar and status bar come on top of it
      var space = function (name) { var el = editor.ui.space(name); return el ? el.$.offsetHeight : 0; };
      var inner = Math.max(200, height - strip.offsetHeight - space('top') - space('bottom') - 2);
      editor.resize('100%', inner, true);
      if (cm && !inVisual()) { cm.setSize(null, inner); cm.refresh(); }
    } catch (e) { /* the editor is still starting */ }
  }
  window.addEventListener('resize', fit);
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
  function changed() { touched(); lines(); clearTimeout(checkTimer); checkTimer = setTimeout(check, 400); }
  // CodeMirror exists while CKEditor is in Source mode (a new one each time the mode is switched), as window.codemirror_<CKEditor's id>
  function attach() {
    var next = editor ? window['codemirror_' + editor.id] : null;
    if (!next || next === cm) { return; }
    cm = next;
    lines(); check();
    cm.on('change', function (instance, change) { if (change.origin !== 'setValue') { changed(); } else { lines(); } });
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
