/* The CSV grid page of the file editor (grid.php): a CSV file as a spreadsheet (Jspreadsheet CE) with Excel-style copy and paste and a fill handle that continues
   series (fill-series.js). Configuration comes from window.IPS_FE_GRID ({csrf, file, writable, delete, text, list}).

   The first row of the file is the sheet's column names and stays as it is: columns cannot be added, removed, renamed or sorted, so a RAD or AtoM file keeps its layout.
   Rows can be added, removed and edited. Every cell is plain text (nothing is ever evaluated as a formula) and is put on the page as text, never as markup.
   Saving writes the sheet back as CSV (RFC 4180: a cell is quoted only when it holds a comma, a quote or a line break), with the file's own line endings, its byte order
   mark if it had one, and its closing line break; blank rows at the end are dropped. A file opened and saved with no change is not marked as changed. */
(function () {
  'use strict';
  var cfg = window.IPS_FE_GRID;
  if (!cfg || typeof jspreadsheet === 'undefined') { return; }
  var $ = function (id) { return document.getElementById(id); };

  // ---- CSV in and out -----------------------------------------------------------------------------------------------------------
  function parseCsv(input) {
    var bom = input.charCodeAt(0) === 0xFEFF, text = bom ? input.slice(1) : input;
    var rows = [], row = [], cell = '', quoted = false, i, c;
    for (i = 0; i < text.length; i++) {
      c = text.charAt(i);
      if (quoted) {
        if (c === '"') { if (text.charAt(i + 1) === '"') { cell += '"'; i++; } else { quoted = false; } } else { cell += c; }
      } else if (c === '"' && cell === '') { quoted = true; }
      else if (c === ',') { row.push(cell); cell = ''; }
      else if (c === '\n' || (c === '\r' && text.charAt(i + 1) !== '\n')) { row.push(cell); rows.push(row); row = []; cell = ''; }
      else if (c !== '\r') { cell += c; }
    }
    if (cell !== '' || row.length) { row.push(cell); rows.push(row); }
    var crlf = (text.match(/\r\n/g) || []).length, lf = (text.match(/\n/g) || []).length - crlf;
    return { rows: rows, bom: bom, eol: crlf > lf ? '\r\n' : '\n', finalBreak: /\n$/.test(text) || text === '' };
  }

  function quote(value) {
    var s = String(value === null || value === undefined ? '' : value);
    return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
  }

  function toCsv(rows, format) {
    var last = rows.length;
    while (last > 1 && rows[last - 1].every(function (v) { return v === '' || v === null || v === undefined; })) { last--; }
    var lines = rows.slice(0, last).map(function (r) { return r.map(quote).join(','); });
    return (format.bom ? '﻿' : '') + lines.join(format.eol) + (format.finalBreak ? format.eol : '');
  }

  // ---- the sheet ----------------------------------------------------------------------------------------------------------------
  var parsed = parseCsv(cfg.text);
  var width = parsed.rows.reduce(function (m, r) { return Math.max(m, r.length); }, 1);
  function padded(r) { var out = r.slice(); while (out.length < width) { out.push(''); } return out; }
  var header = padded(parsed.rows[0] || ['']);
  var data = parsed.rows.slice(1).map(padded);
  if (!data.length) { data.push(new Array(width).fill('')); }

  var sheet = null;
  var onFill = FillSeries.install(function () { return sheet; });
  var saved = null; // the text of the file as the sheet would write it now: what "unchanged" means

  function rowsNow() { return [header].concat(sheet.getData().map(function (r) { return r.map(function (v) { return v === null || v === undefined ? '' : String(v); }); })); }
  function current() { return toCsv(rowsNow(), parsed); }

  var dirtyTimer = null;
  function checkDirty() {
    clearTimeout(dirtyTimer);
    dirtyTimer = setTimeout(function () {
      var dirty = current() !== saved;
      $('dirty-flag').hidden = !dirty;
      $('grid-size').textContent = sheet.getData().length + ' rows × ' + width + ' columns';
    }, 60);
  }
  function isDirty() { return current() !== saved; }

  var pane = $('fe-grid');
  function fitHeight() {
    var top = pane.getBoundingClientRect().top;
    return Math.max(320, Math.floor(window.innerHeight - top - 40)) + 'px';
  }

  var spreadsheet = jspreadsheet(pane, {
    worksheets: [{
      data: data,
      columns: header.map(function (title) { return { type: 'text', title: title === '' ? ' ' : title, width: 170, align: 'left' }; }),
      minDimensions: [width, 1],
      tableOverflow: true,
      tableHeight: fitHeight(),
      freezeColumns: 0,
      lazyLoading: true,
      editable: !!cfg.writable,
      columnSorting: false,
      columnDrag: false,
      rowDrag: false,
      allowInsertColumn: false,
      allowManualInsertColumn: false,
      allowDeleteColumn: false,
      allowRenameColumn: false,
      allowComments: false,
      allowInsertRow: true,
      allowManualInsertRow: true,
      allowDeleteRow: true,
      selectionCopy: true,
      wordWrap: false,
      defaultColWidth: 170
    }],
    // Settings the library reads from the spreadsheet, not the worksheet (one placed in the worksheet is silently ignored): a cell that starts with = is text,
    // markup in a cell is text, nothing is cast or counted up by the library (the fill is fill-series.js), no download of its own (Save is ours) and no About entry.
    parseFormulas: false,
    parseHTML: false,
    autoCasting: false,
    autoIncrement: false,
    allowExport: false,
    about: false,
    toolbar: false,
    onbeforechange: onFill,
    onafterchanges: checkDirty,
    oninsertrow: checkDirty,
    ondeleterow: checkDirty,
    onundo: checkDirty,
    onredo: checkDirty,
    onpaste: checkDirty
  });
  sheet = Array.isArray(spreadsheet) ? spreadsheet[0] : spreadsheet.worksheets ? spreadsheet.worksheets[0] : spreadsheet;
  window.IPS_FE_SHEET = sheet; // for the browser tests
  saved = current();
  window.IPS_FE_GRID_READY = true;
  $('grid-size').textContent = sheet.getData().length + ' rows × ' + width + ' columns';
  window.addEventListener('resize', function () { pane.style.height = ''; });

  // ---- toast, save, delete ------------------------------------------------------------------------------------------------------
  var toastTimer = null;
  function toast(message, isError) {
    var t = $('fe-toast');
    t.textContent = message; t.className = isError ? 'err' : ''; t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.hidden = true; }, isError ? 6000 : 3500);
  }
  function reply(r) { return r.json().catch(function () { return { success: false, error: 'The server answered with an error (' + r.status + ').' }; }); }

  var saveBtn = $('save-btn');
  function save() {
    if (saveBtn.disabled) { return; }
    var text = current();
    saveBtn.disabled = true; saveBtn.textContent = 'Saving…';
    fetch('save.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ file: cfg.file, editor1: text }) })
      .then(reply)
      .then(function (d) {
        if (d.success) { saved = text; $('dirty-flag').hidden = true; window.dispatchEvent(new CustomEvent('ipsfe:saved')); }
        toast(d.success ? 'Saved.' : (d.error || 'Not saved.'), !d.success);
      })
      .catch(function () { toast('Not saved: the server could not be reached.', true); })
      .then(function () { saveBtn.disabled = !cfg.writable; saveBtn.textContent = 'Save'; });
  }
  saveBtn.addEventListener('click', save);

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
      fetch('delete.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF': cfg.csrf }, body: new URLSearchParams({ file: cfg.file }) })
        .then(reply)
        .then(function (d) {
          if (d.success) { saved = current(); window.location.href = cfg.list; return; }
          toast(d.error || 'Not deleted.', true); del.disabled = false; del.textContent = 'Delete';
        })
        .catch(function () { toast('Not deleted: the server could not be reached.', true); del.disabled = false; del.textContent = 'Delete'; });
    });
  }

  // Ctrl/Cmd+S saves. The library has a Ctrl+S of its own (a file download): this listener runs first (capture phase) and keeps the key from reaching it.
  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && !e.shiftKey && !e.altKey && String(e.key).toLowerCase() === 's') {
      e.preventDefault();
      e.stopImmediatePropagation();
      if (cfg.writable) { save(); }
    }
  }, true);
  window.addEventListener('beforeunload', function (e) { if (isDirty()) { e.preventDefault(); e.returnValue = ''; } });
}());
