/*jshint esversion: 6 */
var tcpfFunction;
readyDOM(() => {

  jQuery(document).ready($ => {
    if (typeof tcpfData === 'undefined') {
      console.error("Core: tcpfData isn't set.");
      return;
    }

    console.log('tcpfData', tcpfData);

    tcpfFunction = {
      sendAjax: (e, args) => {
        console.log(args, 'arguments callback');

        let options = args.data;
        var response = {};

        if (!args.action) {
          response.message = tcpfData.translated.ajaxError;
          return response;
        }

        $.ajax({
          method: args.method,
          url: args.url,
          dataType: 'json',
          data: {
            action: args.action,
            nonce: tcpfData.ajax.nonce,
            options: options
          }
        }).always(data => {
          response = 'responseJSON' in data ? data.responseJSON : data;
          if (typeof tcpfFunction[args.callback] === 'function') {
            tcpfFunction[args.callback](response);
          }
          return response;
        });
      },
      setRangeValueFormSlider: (e, elm) => {
        let that;
        if (typeof elm !== 'undefined') {
          that = $(elm);
        } else if (typeof e.currentTarget !== 'undefined') {
          that = $(e.currentTarget);
        } else if (typeof this !== 'undefined') {
          that = $(this);
        } else {
          return false;
        }

        let value = that.val();
        let textValue = that.parent().next();
        if (that.attr('istime') !== undefined) {
          textValue.css('width', 'auto');
          textValue.text(toFormatDate(value));
        } else {
          textValue.text(value);
        }
      },
      setInputValueFormSelect: (e, elm) => {
        let that = e.currentTarget ? e.currentTarget : e;
        let label = $(that).find('option:selected').closest('optgroup').prop('label');
        $(elm).val(label);
      },
      showElementFormSelect: (e, args) => {
        console.log(args);
        let that = e.currentTarget ? e.currentTarget : e;
        let selector = args[1];
        let value = $(that).find('option:selected').val();

        if (value !== args[0]) {
          $(selector).hide();
        } else {
          $(selector).show();
        }

      },
      showModal: response => {
        let html = '';
        console.log('showModal', response);
        if (typeof response === 'object') {
          html = response.success ? response.message : tcpfFunction.printResponse(response);
        } else {
          html = response;
        }

        M.toast({
          html: html
        });
      },
      printResponse: response => {
        return JSON.stringify(response, null, '\t').replace(/\n/g, '<br>').replace(/\t/g, '&nbsp;&nbsp;&nbsp;');
      },
      addChipTooltip: chip => {
        var chipText = $(chip).clone().children().remove().end().text();
        $(chip).tooltip({
          html: chipText,
          position: 'top'
        });
        let trimmedChipText = chipText.substring(0, 30);
        trimmedChipText += chipText.length > 30 ? '...' : '';
        $(chip).html(trimmedChipText + '<i class="close material-icons">close</i>');
      },
      mergeObjectRecursive: (object, keyMatch, keyValue) => {
        let newData = [];
        $.map(object, element => {
          if (!$.isEmptyObject(element)) {
            // Get object with double key
            let doubleObject = newData.filter(v => {
              return v[keyMatch] == element[keyMatch];
            });

            if (doubleObject.length) {
              let existingIndex = newData.indexOf(doubleObject[0]);

              // If keyValue as array
              if (Array.isArray(newData[existingIndex][keyValue])) {
                newData[existingIndex][keyValue] = newData[existingIndex][keyValue].concat(element[keyValue]);
              } else {
                let currentValue = newData[existingIndex][keyValue];
                newData[existingIndex][keyValue] = [];
                newData[existingIndex][keyValue].push(currentValue, element[keyValue]);
              }
            } else {
              newData.push(element);
            }
          } else {
            newData = tcpfFunction.mergeObjectRecursive(element, keyMatch, keyValue);
          }
        });
        return newData;
      },
      toFormatDate: second => {
        var sec_num = parseInt(second, 10); // don't forget the second param
        var hours = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (hours < 10) {
          hours = "0" + hours;
        }
        if (minutes < 10) {
          minutes = "0" + minutes;
        }
        if (seconds < 10) {
          seconds = "0" + seconds;
        }
        return hours + 'h ' + minutes + 'm ' + seconds + 's';
      },
      initComponent: () => {

        // Materialize Tab
        $('.tabs').tabs({
          swipeable: false,
          duration: 200
        });

        // Remember selected tab
        $('.tabs').on('click', 'a', e => {
          let hash = e.currentTarget.hash.substr(1);
          Cookies.set('tab_setting', hash, {
            expires: 7,
            path: '/wp-admin'
          });
        });

        // Handle tab event click
        $('.tabs').find('li').map((i, element) => {
          let aCallback = $(element).data('onclick');
          if (typeof aCallback !== 'undefined') {
            $(element).removeData('onclick');
            let funcName = aCallback[0];
            let args = aCallback[1];

            if (typeof tcpfFunction[funcName] === 'function') {
              $(element).click(function (e) {
                console.log('Onclick form tabs', funcName, args);
                e.preventDefault();
                tcpfFunction[funcName](args);
              });
            }
          }
        });

        $('.tabs-content').find('*').map((i, element) => {
          // Handle content element event click
          let aCallback = $(element).data('onclick');
          if (typeof aCallback !== 'undefined') {
            $(element).removeData('onclick');
            let funcName = aCallback[0];
            let args = aCallback[1];
            if (typeof tcpfFunction[funcName] === 'function') {
              $(element).click(function (e) {
                //console.log('Onclick form content tab', funcName, args);
                e.preventDefault();
                tcpfFunction[funcName](e, args);
              });
            }
          }

          // Handle content element event change
          aCallback = $(element).data('onchange');
          if (typeof aCallback !== 'undefined') {
            $(element).removeData('onchange');
            let funcName = aCallback[0];
            let args = aCallback[1];

            if (typeof tcpfFunction[funcName] === 'function') {
              $(element).change(function (e) {
                //console.log('Onchange form section', funcName, args);
                tcpfFunction[funcName](e, args);
              });
            }
          }

          // Handle content table event callback, each table corresponds to a wp section
          aCallback = $(element).data('onload');
          if (typeof aCallback !== 'undefined') {
            $(element).removeData('onload');
            let funcName = aCallback[0];
            let args = aCallback[1];

            if (typeof tcpfFunction[funcName] === 'function') {
              //console.log('Onload form section', funcName, args);
              tcpfFunction[funcName](element, args);
            }
          }
        });

        // Materialize Range
        $('input[type=range]').map(tcpfFunction.setRangeValueFormSlider);
        $('input[type=range]').on('change mousemove', tcpfFunction.setRangeValueFormSlider);

        // WP Color picker
        if ($('input[choose-color]').length > 0) {
          $('input[choose-color]').iris();
        }

        // Materialize Modal
        $('.modal').modal();

        // Materialize Tooltip
        $('.tooltip').tooltip();

        // Materialize Select
        $('select').formSelect();

        // Materialize Collapsible
        $('.collapsible').collapsible();

        // Materialize Switch
        $('.lever').on('click', function (e) {
          var curInput = $(e.currentTarget).siblings('input')[0];
          // if input checked is switch to off 

          console.log(curInput);
          $(curInput).val(curInput.checked ? 0 : 1);
        });

        // Materialize Chip
        var optionsMaterializeChip = {
          onChipAdd: function (e) {
            // Update input value
            let result = e[0].M_Chips.chipsData.map(chip => chip.tag);
            let inputTarget = $('input[name="' + e[0].id + '"]');
            inputTarget.val(JSON.stringify(result));

            // Add tooltip
            tcpfFunction.addChipTooltip(chip);
          },
          onChipDelete: function (e) {
            // Update input value
            let result = e[0].M_Chips.chipsData.map(chip => chip.tag);
            let inputTarget = $('input[name="' + e[0].id + '"]');
            inputTarget.val(JSON.stringify(result));
          }
        };

        for (var id in tcpfData.settings) {
          if (tcpfData.settings[id].type === 'chips') {
            optionsMaterializeChip = Object.assign({}, optionsMaterializeChip, tcpfData.settings[id]);
            let instance = $('#' + id).chips(optionsMaterializeChip);

            // Add tooltip
            if (typeof instance[0] !== 'undefined') {
              instance[0].M_Chips.$chips.map(tcpfFunction.addChipTooltip);
            }
          }
        }

        // Textarea format
        $('textarea').each(function () {
          let format = this.attributes.format.value;
          if (format === 'jsonp') {
            let value = $(this).val();
            try {
              let json = JSON.parse(value);
              let jsonp = JSON.stringify(json, undefined, 2);
              $(this).val(jsonp);
            } catch (error) {
              return error;
            }
          }
        });

        // Media Uploader
        var mediaUploader, curImage;
        $('img[choose-image]').click(function (e) {
          e.preventDefault();
          curImage = $(this);
          console.log(curImage);

          //  If the uploader object has already been created, reopen the dialog
          if (mediaUploader) {
            mediaUploader.open();
            return;
          }
          //  Extend the wp.media object
          mediaUploader = wp.media({
            title: tcpfData.translated.chooseImage,
            button: {
              text: tcpfData.translated.chooseImage
            },
            multiple: false
          });
          //  When a file is selected, grab the URL and set it as the text field's value
          mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            curImage.attr("src", attachment.url);
            $('#' + curImage.attr("data")).attr("value", attachment.url);
          });
          //  Open the uploader dialog
          mediaUploader.open();
        });

        //  Can add button get token from server by js instead of callback in settings_field //
        tcpfFunction.initSubmit();
      },
      initSubmit: () => {
        // Submit Ajax
        $('#optionsForm').submit(e => {

          e.preventDefault();

          // Get all options
          var options = $(e.currentTarget).serializeArray();

          let aOptionName = [];
          $.map(options, el => {
            el.value = el.value.replace(/(\r\n\t|\n|\r\t)/gm, "").trim();
            if ($.inArray(el.name, aOptionName) === -1) {
              aOptionName.push(el.name);
            }
          });

          // Include checkbox not checked
          $('select:not(:checked)').each(function () {
            if ($.inArray(this.name, aOptionName) === -1 && this.name !== '') {
              aOptions.push({
                name: this.name,
                value: ''
              });
            }
          });

          $('input[type="checkbox"]:not(:checked)').each(function () {
            if ($.inArray(this.name, aOptionName) === -1 && this.name !== '') {
              aOptions.push({
                name: this.name,
                value: '0'
              });
            }
          });

          // Filter value setting
          options = tcpfFunction.mergeObjectRecursive(options, 'name', 'value');

          if (options.length === 0) {
            let toastContent = $('<span>' + tcpfData.translated.noChanged + '</span>');
            M.toast({
              html: toastContent
            });
            return;
          }

          let postData = {
            'method': 'POST',
            'url': tcpfData.ajax.url,
            'action': 'save_options', // don't use camelCase, only use snake_case
            'callback': 'showModal',
            'data': options
          };

          tcpfFunction.sendAjax(e, postData);
        });
      }
    };

    // Init Component Element
    setTimeout(function () {
      tcpfFunction.initComponent();
    }, 500);
  });
}, false);

function readyDOM(callback) {
  // in case the document is already rendered
  if (document.readyState != 'loading') callback();
  // modern browsers
  else if (document.addEventListener) document.addEventListener('DOMContentLoaded', callback);
  // IE <= 8
  else document.attachEvent('onreadystatechange', function () {
    if (document.readyState == 'complete') callback();
  });
}