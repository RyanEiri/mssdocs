/* The editor page of the file editor module (file_editor/edit.php): CKEditor in Source mode with its CodeMirror view (or a plain text box when CKEditor is not
   installed), a status strip (lines, whether XML is well-formed), an "Unsaved changes" flag, Ctrl/Cmd+S, a toast, Save and a two-click Delete.
   Configuration comes from window.IPS_FE_EDIT ({csrf, kind, writable, delete, ckeditor}). The text is saved exactly as it is in the editor. */
(function () {
  'use strict';
  var cfg = window.IPS_FE_EDIT;
  if (!cfg) { return; }
  var $ = function (id) { return document.getElementById(id); };
  var form = $('fe-form'), pane = $('fe-pane'), strip = $('fe-strip'), text = $('editor1');
  var editor = null, cm = null, dirty = false, toastTimer = null, checkTimer = null;

  if (cfg.ckeditor && window.CKEDITOR) {
    CKEDITOR.disableAutoInline = true;
    // every option is set here (customConfig off), so the module does not depend on a config.js someone has edited
    editor = CKEDITOR.replace('editor1', {
      customConfig: '', readOnly: !cfg.writable, startupMode: 'source', extraPlugins: 'codemirror', allowedContent: true, entities: true, width: '100%',
      // one row: Source, clipboard and undo, the CodeMirror plugin's search / comment / completion buttons (they show in Source mode), a few more
      toolbar: [['Source', '-', 'Cut', 'Copy', 'Paste', '-', 'Undo', 'Redo', '-', 'searchCode', 'autoFormat', 'CommentSelectedRange', 'UncommentSelectedRange', 'AutoComplete', '-', 'RemoveFormat', 'Outdent', 'Indent', '-', 'SpecialChar', 'Maximize']],
      codemirror: {
        theme: 'default', lineNumbers: true, lineWrapping: true, matchBrackets: true, autoCloseTags: true, autoCloseBrackets: true, enableSearchTools: true,
        enableCodeFolding: false, enableCodeFormatting: false, autoFormatOnStart: false, autoFormatOnModeChange: false, autoFormatOnUncomment: false,
        mode: 'htmlmixed', showSearchButton: true, showTrailingSpace: true, highlightMatches: true, showFormatButton: false, showCommentButton: true,
        showUncommentButton: true, showAutoCompleteButton: true, styleActiveLine: true, useBeautifyOnStart: false
      }
    });
  }

  function value() { return cm ? cm.getValue() : text.value; }

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
      if (cm) { cm.setSize(null, inner); cm.refresh(); }
    } catch (e) { /* the editor is still starting */ }
  }
  window.addEventListener('resize', fit);
  if (editor) { editor.on('instanceReady', fit); }
  fit();

  // ---- status strip and the unsaved flag --------------------------------------------------------------------------------------
  function lines() { $('line-count').textContent = cm ? cm.lineCount() : value().split('\n').length; }
  function check() {
    if (cfg.kind !== 'xml') { return; }
    var ok = !new DOMParser().parseFromString(value(), 'application/xml').getElementsByTagName('parsererror').length;
    $('wf-dot').classList.toggle('bad', !ok);
    $('wf-text').textContent = ok ? 'Well-formed' : 'Not well-formed';
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
  if (editor) { setInterval(attach, 500); } else { text.addEventListener('input', changed); }
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
