/**
 * @file
 * Init Select2 Multi Checkboxes widget.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.facets = Drupal.facets || {};

  /**
   * Define custom selection adapter for Select2.
   */
  $.fn.select2.amd.define("Select2MultiCheckboxesSelectionAdapter", [
    "select2/utils",
    "select2/selection/multiple",
    "select2/selection/placeholder",
    "select2/selection/eventRelay",
    "select2/selection/single"
  ],
  function(Utils, MultipleSelection, Placeholder, EventRelay, SingleSelection) {
    let adapter = Utils.Decorate(MultipleSelection, Placeholder);
    adapter = Utils.Decorate(adapter, EventRelay);

    adapter.prototype.render = function() {
      let $selection = SingleSelection.prototype.render.call(this);
      return $selection;
    };

    adapter.prototype.update = function(data) {
      this.clear();

      let $rendered = this.$selection.find('.select2-selection__rendered');
      let formatted = this.options.get("placeholder") || "";

      $rendered.empty().append(formatted);
      $rendered.prop('title', formatted);
    };

    return adapter;
  });

  /**
   * Define custom dropdown adapter for Select2.
   */
  $.fn.select2.amd.define("Select2MultiCheckboxesDropdownAdapter", [
    "select2/utils",
    "select2/dropdown",
    "select2/dropdown/attachBody",
    "select2/dropdown/search"
  ],
  function(Utils, Dropdown, AttachBody, Search) {
    let dropdownWithSearch = Utils.Decorate(Dropdown, Search);
    dropdownWithSearch.prototype.render = function() {
      var $rendered = Dropdown.prototype.render.call(this);
      let placeholder = this.options.get("placeholderForSearch") || "";
      var $search = $(
        '<span class="select2-search select2-search--dropdown">' +
        '<input class="select2-search__field" placeholder="' + placeholder + '" type="search"' +
        ' tabindex="-1" autocomplete="off" autocorrect="off" autocapitalize="off"' +
        ' spellcheck="false" role="textbox" />' +
        '</span>'
      );

      this.$searchContainer = $search;
      this.$search = $search.find('input');

      $rendered.prepend($search);
      return $rendered;
    };

    let adapter = Utils.Decorate(dropdownWithSearch, AttachBody);

    return adapter;
  });

  /**
   * Override Select2 config on initialization and provide facets behaviour.
   */
  Drupal.facets.initSelect2MultiCheckboxes = function (context, settings) {
    $('.js-facets-select2-multi-checkboxes.js-facets-widget', context).on('select2-init', function (e) {
      var config = $(e.target).data('select2-config');

      config.selectionAdapter = $.fn.select2.amd.require("Select2MultiCheckboxesSelectionAdapter");
      if (config.search) {
        config.dropdownAdapter = $.fn.select2.amd.require("Select2MultiCheckboxesDropdownAdapter");
      }

      config.templateResult = function(result) {
        var checkbox = '<input type="checkbox">';
        if (result.selected) {
          checkbox = '<input type="checkbox" checked>'
        }
        if (result.loading !== undefined)
          return result.text;
        return $('<div>').append(checkbox).append(result.text).addClass("select2-results__option__checkbox-wrapper");
      };

      $(e.target).data('select2-config', config);
    });

    $('.js-facets-select2-multi-checkboxes.js-facets-widget')
      .once('js-facets-select2-widget-on-selection-change')
      .each(function () {
        var $select2_widget = $(this);

        $select2_widget.on('select2:select select2:unselect', function (item) {
          $(this).trigger('facets_filter', [item.params.data.id]);
        });

        $select2_widget.on('facets_filtering.select2', function () {
          $select2_widget.prop('disabled', true);
        });
      });
  };

  /**
   * Initialize Select2 Multi Checkboxes widget to be used for facets.
   */
  Drupal.behaviors.facetsSelect2MultiCheckboxesWidget = {
    attach: function (context, settings) {
      Drupal.facets.initSelect2MultiCheckboxes(context, settings);
    }
  };

})(jQuery, Drupal);
