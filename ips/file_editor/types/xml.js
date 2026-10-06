/* The XML type's live status in the editor page: whether the text is well-formed XML (the server makes the final decision when it is saved). */
(function () {
  'use strict';
  window.IPS_FE_TYPES = window.IPS_FE_TYPES || {};
  window.IPS_FE_TYPES.xml = {
    check: function (text) {
      var ok = !new DOMParser().parseFromString(text, 'application/xml').getElementsByTagName('parsererror').length;
      return { ok: ok, text: ok ? 'Well-formed' : 'Not well-formed' };
    }
  };
}());
