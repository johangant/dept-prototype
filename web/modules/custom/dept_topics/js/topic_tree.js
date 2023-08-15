/**
 * @file
 * Topic tree JS.
 */

(function($, Drupal, drupalSettings) {
  Drupal.behaviors.topicTree = {
    attach: function(context, settings) {
      let select_field = "#" + drupalSettings["topic_tree.field"];

      $('#topic-tree-wrapper')
        .on('changed.jstree', function (e, data) {
            $('input[name="selected_topics"]').val(data.instance.get_selected());
        })
        .on("ready.jstree", function(e, data) {
          // Check all tree elements matching the selected options.
          $(select_field + " input:checked").each(function () {
            data.instance.select_node($(this).val());
          });
        })
        .on("select_node.jstree", function(e, data) {
          // Deselect all parents.
          for (const [key, value] of Object.entries(data.node.parents)) {
            data.instance.deselect_node(value);
          }

          // Deselect all children.
          for (const [key, value] of Object.entries(data.node.children_d)) {
            data.instance.deselect_node(value);
          }

          $('#topic-tree-count span').text(data.instance.get_selected().length);

          if (data.instance.get_selected().length > 3) {
            data.instance.deselect_node(data.node);
            alert('Topic selection limit reached.')
          }
        })
        .jstree({
          core: {
            data: {
              url: function(node) {
                return Drupal.url(
                  "admin/topics/topic_tree/json/" + drupalSettings["topic_tree.department"]
                );
              },
              data: function(node) {
                return {
                  id: node.id,
                  text: node.text,
                  parent: node.parent
                };
              }
            },
          },
          checkbox: {
            three_state: false
          },
          plugins: ["changed", "checkbox", "conditionalselect", "search"],
          "search": {
            "case_sensitive": false,
            "show_only_matches": true,
          }
        });

      $('#topic-tree-search').keyup(function() {
        let search_text = $(this).val();
        $('#topic-tree-wrapper').jstree('search', search_text);
      });
    }
  }
})(jQuery, Drupal, drupalSettings);

(function($) {
  $.fn.topicTreeAjaxCallback = function(field, topics) {
    topics = topics.split(',');

    topics.forEach((topic) => {
      $('#' + field + " input[value='" + topic + "']").prop("checked", true);
    });
  };
})(jQuery);

