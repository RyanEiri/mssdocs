/* The XML type's live status in the editor page: whether the text is well-formed XML (the server makes the final decision when it is saved). */
(function () {
  'use strict';
  window.IPS_FE_TYPES = window.IPS_FE_TYPES || {};
  window.IPS_FE_TYPES.xml = {
    check: function (text) {
      var errors = new DOMParser().parseFromString(text, 'application/xml').getElementsByTagName('parsererror');
      var ok = !errors.length;
      // A named character entity (&ae; ...) needs a DTD the browser does not have here: it is not a fault of the text, and the server (which knows Menota's list) decides on saving.
      if (!ok && /entity/i.test(errors[0].textContent)) {
        return { ok: true, text: 'Well-formed (named entities are checked when saved)' };
      }
      return { ok: ok, text: ok ? 'Well-formed' : 'Not well-formed' };
    }
  };
}());
