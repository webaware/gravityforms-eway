"use strict";

// gravityforms-eway: Client Side Encryption
(function ($) {
  /**
  * if form has Client Side Encryption key, hook its submit action for maybe encrypting
  * @param {jQuery.Event} event
  * @param {Number} form_id int ID of Gravity Forms form
  */
  $(document).on("gform_post_render", function (event, form_id) {
    $("#gform_" + form_id + "[data-gfeway-encrypt-key]").on("submit", maybeEncryptForm);
  });
  /**
  * use ES6 String.prototype.repeat() if available, providing fallback for IE11
  * @param {String} character
  * @param {Number}
  * @return {String}
  */

  var repeatString = function () {
    if (typeof String.prototype.repeat === "function") {
      return function (character, length) {
        return character.repeat(length);
      };
    }

    return function (character, length) {
      var s = character;

      for (var i = 1; i < length; i++) {
        s += character;
      }

      return s;
    };
  }();
  /**
  * check form for conditions to encrypt sensitive fields
  * @param {jQuery.Event} event
  */


  function maybeEncryptForm(event) {
    var frm = $(this);
    var form_id = extractFormId(this.id); // don't encrypt if sending to the Recurring Payment XML API

    if (frm.find(".gfeway-recurring-active").length) {
      return true;
    }

    var key = frm.data("gfeway-encrypt-key");

    function maybeEncryptField(field_selector) {
      var field = frm.find(field_selector);

      if (field.length) {
        var value = field.val().trim();
        var length = value.length;
        var target = field.data("gfeway-encrypt-name");

        if (target === "EWAY_CARDNUMBER" && !cardnumberValid(value)) {
          throw {
            name: "Credit Card Error",
            message: gfeway_ecrypt_strings.card_number_invalid,
            field: field
          };
        }

        if (length) {
          var encrypted = eCrypt.encryptValue(field.val(), key);
          $("<input type='hidden'>").attr("name", target).val(encrypted).appendTo(frm);
          field.val("").prop("placeholder", repeatString(gfeway_ecrypt_strings.ecrypt_mask, length));
        }
      }
    }

    function extractFormId(form_element_id) {
      var parts = form_element_id.split("_");
      return parts.length > 1 ? parts[1] : "";
    }

    try {
      maybeEncryptField("input[data-gfeway-encrypt-name='EWAY_CARDNUMBER']");
      maybeEncryptField("input[data-gfeway-encrypt-name='EWAY_CARDCVN']");
    } catch (e) {
      event.preventDefault();
      window["gf_submitting_" + form_id] = false;
      $("#gform_ajax_spinner_" + form_id).remove();
      e.field.focus();
      window.alert(e.message);
    }

    return true;
  }
  /**
  * basic card number validation using Luhn algorithm
  * @param {String} card_number
  * @return bool
  */


  function cardnumberValid(card_number) {
    var checksum = 0;
    var multiplier = 1; // process each character, starting at the right

    for (var i = card_number.length - 1; i >= 0; i--) {
      var digit = card_number.charAt(i) * multiplier;
      multiplier = multiplier === 1 ? 2 : 1; // digit can't be greater than 9

      if (digit >= 10) {
        checksum++;
        digit -= 10;
      }

      checksum += digit;
    }

    return checksum % 10 === 0;
  }
})(jQuery);
