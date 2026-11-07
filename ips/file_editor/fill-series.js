/* Excel-style series for the fill handle of the CSV grid (Jspreadsheet CE only repeats the selected cells: 1, 2 gives 1, 2, 1, 2).

   fillSeries(source, count)  what the next `count` values are, given the source cells' values in the direction of the fill:
     1, 2            -> 3, 4, 5         an even numeric step continues
     1               -> 1, 1, 1         one number is copied (as in Excel)
     Item 1, Item 2  -> Item 3, Item 4  the last run of digits counts up, with its zero padding
     FH-B01-0009     -> FH-B01-0010     ... and one such cell counts up by 1 (as in Excel)
     2026-01-01, 02  -> 2026-01-03      ISO dates continue by the same number of days (one date goes on by a day)
     anything else   -> the selected cells, repeated

   FillSeries.install(getWorksheet)  returns the onbeforechange option of a Jspreadsheet CE spreadsheet: while the corner square is being dragged, each cell the drag
   fills gets the series value instead of the grid's own repeat, in any direction, so a fill is one change and one Ctrl+Z. Pasting, typing and undo are untouched
   (only a drag that started on the corner square counts). The pure logic is also usable from node (module.exports) and is tested there. */
(function (root) {
  'use strict';

  var NUMBER = /^-?\d+(\.\d+)?$/;
  var ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;

  function text(v) { return v === null || v === undefined ? '' : String(v); }

  function fillSeries(source, count) {
    var n = source.length, out = [], i;
    var s = source.map(text);
    function repeat() { out = []; for (i = 0; i < count; i++) { out.push(source[i % n]); } return out; }
    if (!n || count < 1) { return []; }

    // numbers: an even step continues (one number is copied)
    if (n >= 2 && s.every(function (t) { return NUMBER.test(t.trim()); })) {
      var nums = s.map(Number), step = nums[1] - nums[0];
      if (nums.every(function (v, k) { return k === 0 || Math.abs(v - nums[k - 1] - step) < 1e-9; })) {
        for (i = 1; i <= count; i++) { out.push(+(nums[n - 1] + step * i).toFixed(10)); }
        return out;
      }
      return repeat();
    }

    // ISO dates: the same number of days again (one date goes on by a day, as in Excel; a date is not a run of digits to count)
    var days = s.map(function (t) { return ISO_DATE.test(t) ? Date.UTC(+t.slice(0, 4), +t.slice(5, 7) - 1, +t.slice(8, 10)) : NaN; });
    if (days.every(function (d) { return !isNaN(d); })) {
      var gap = n >= 2 ? days[1] - days[0] : 86400000;
      if (days.every(function (d, k) { return k === 0 || d - days[k - 1] === gap; })) {
        for (i = 1; i <= count; i++) { out.push(new Date(days[n - 1] + gap * i).toISOString().slice(0, 10)); }
        return out;
      }
    }
    // text ending in digits: "Item 1", "FH-B01-0001" (the last run of digits counts)
    var parts = s.map(function (t) { return /^([\s\S]*?)(\d+)$/.exec(t); });
    var allNumbers = s.every(function (t) { return NUMBER.test(t.trim()); });
    if (!allNumbers && parts.every(Boolean) && parts.every(function (m) { return m[1] === parts[0][1]; })) {
      var digits = parts.map(function (m) { return parseInt(m[2], 10); });
      var by = n >= 2 ? digits[1] - digits[0] : 1;
      if (digits.every(function (v, k) { return k === 0 || v - digits[k - 1] === by; })) {
        var last = parts[n - 1][2], padded = last.charAt(0) === '0' && last.length > 1;
        var ok = true, made = [];
        for (i = 1; i <= count; i++) {
          var v = digits[n - 1] + by * i;
          if (v < 0) { ok = false; break; }
          made.push(parts[0][1] + (padded ? String(v).padStart(last.length, '0') : String(v)));
        }
        if (ok) { return made; }
      }
    }

    return repeat();
  }

  // The value a drag on the corner square gives the cell (x, y): the series that continues the cells that were selected when the drag began, or undefined when the
  // cell is not one the drag extends (inside the selection, or off to one side of it). `sel` is [x1, y1, x2, y2]; `read(x, y)` gives a cell's value.
  function valueAt(sel, x, y, read) {
    var x1 = Math.min(sel[0], sel[2]), x2 = Math.max(sel[0], sel[2]), y1 = Math.min(sel[1], sel[3]), y2 = Math.max(sel[1], sel[3]);
    var inColumns = x >= x1 && x <= x2, inRows = y >= y1 && y <= y2;
    var vertical, forward, lane, from, to, distance, source = [], k;
    if (inColumns && !inRows) { vertical = true; forward = y > y2; lane = x; from = y1; to = y2; distance = forward ? y - y2 : y1 - y; }
    else if (inRows && !inColumns) { vertical = false; forward = x > x2; lane = y; from = x1; to = x2; distance = forward ? x - x2 : x1 - x; }
    else { return undefined; }
    for (k = 0; k <= to - from; k++) {
      var pos = forward ? from + k : to - k;             // the source is read towards the fill, so going up or left it is read backwards
      source.push(vertical ? read(lane, pos) : read(pos, lane));
    }
    var values = fillSeries(source, distance);
    return values[distance - 1];
  }

  // Hook a Jspreadsheet CE worksheet: returns an onbeforechange handler that gives each cell the drag fills the series value instead of the grid's own repeat, so the
  // whole fill is one change (one Ctrl+Z). getWorksheet() gives the worksheet (it is not known when the options are built). Only a drag that began on the corner
  // square counts: typing, pasting and undo are never touched.
  function install(getWorksheet) {
    var drag = null;
    if (typeof document !== 'undefined') {
      document.addEventListener('mousedown', function (e) {
        var ws = getWorksheet();
        if (ws && e.target && e.target.classList && e.target.classList.contains('jss_corner') && ws.selectedCell) {
          drag = { sel: ws.selectedCell.map(Number) };
        }
      }, true);
      document.addEventListener('mouseup', function () { setTimeout(function () { drag = null; }, 0); }, true);
    }
    return function (worksheet, cell, x, y, value) {
      if (!drag) { return value; }
      var made = valueAt(drag.sel, +x, +y, function (cx, cy) { return worksheet.getValueFromCoords(cx, cy); });
      return made === undefined ? value : made;
    };
  }

  var api = { fillSeries: fillSeries, valueAt: valueAt, install: install };
  if (typeof module !== 'undefined' && module.exports) { module.exports = api; }
  root.FillSeries = api;
}(typeof window !== 'undefined' ? window : this));
