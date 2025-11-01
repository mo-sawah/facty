jQuery(document).ready(function ($) {
  "use strict";

  // Initialize color pickers
  if (typeof $.fn.wpColorPicker !== "undefined") {
    $(".facty-color-picker").wpColorPicker();
  }

  // API Test Button Handler
  $("#test-api-btn").on("click", function (e) {
    e.preventDefault();

    var $button = $(this);
    var $result = $("#api-test-result");
    var apiKey = $('input[name="facty_options[api_key]"]').val();
    var model = $('select[name="facty_options[model]"]').val();

    // Validation
    if (!apiKey || apiKey.trim() === "") {
      $result
        .removeClass("success loading")
        .addClass("error")
        .html(
          "<strong>Error:</strong> Please enter an API key before testing."
        )
        .slideDown();
      return;
    }

    // Show loading state
    $button.addClass("loading").prop("disabled", true).text("Testing...");
    $result
      .removeClass("success error")
      .addClass("loading")
      .html("🔄 Testing API connection...")
      .slideDown();

    // Make AJAX request
    $.ajax({
      url: factyAdmin.ajaxUrl,
      type: "POST",
      data: {
        action: "test_facty_api",
        api_key: apiKey,
        model: model,
        nonce: factyAdmin.nonce,
      },
      timeout: 30000, // 30 second timeout
      success: function (response) {
        if (response.success) {
          $result
            .removeClass("error loading")
            .addClass("success")
            .html("<strong>✓ Success:</strong> " + response.data);
        } else {
          $result
            .removeClass("success loading")
            .addClass("error")
            .html("<strong>✗ Error:</strong> " + response.data);
        }
      },
      error: function (xhr, status, error) {
        var errorMsg = "Connection failed. ";
        if (status === "timeout") {
          errorMsg +=
            "Request timed out. Please check your API key and try again.";
        } else {
          errorMsg += "Please check your API key and network connection.";
        }
        $result
          .removeClass("success loading")
          .addClass("error")
          .html("<strong>✗ Error:</strong> " + errorMsg);
      },
      complete: function () {
        $button.removeClass("loading").prop("disabled", false).text("Test API Connection");
      },
    });
  });

  // Form validation - only for Facty settings page
  $('form[action="options.php"]').on("submit", function (e) {
    // Only validate if we're on the Facty settings page
    var $apiKeyField = $('input[name="facty_options[api_key]"]');
    if ($apiKeyField.length === 0) {
      return true; // Not on Facty settings page, allow submission
    }
    
    var apiKey = $apiKeyField.val();
    var mode = $('select[name="facty_options[fact_check_mode]"]').val();
    var firecrawlKey = $('input[name="facty_options[firecrawl_api_key]"]').val();

    // Check if Firecrawl mode is selected without Firecrawl key
    if (mode === "firecrawl" && (!firecrawlKey || firecrawlKey.trim() === "")) {
      e.preventDefault();
      alert(
        "Firecrawl mode requires a Firecrawl API key. Please enter your Firecrawl API key or switch to OpenRouter mode."
      );
      return false;
    }

    // Check if no API key is provided
    if (!apiKey || apiKey.trim() === "") {
      e.preventDefault();
      alert(
        "OpenRouter API key is required. Please enter your API key before saving."
      );
      return false;
    }

    return true;
  });

  // Mode selection handler - show/hide relevant fields
  $('select[name="facty_options[fact_check_mode]"]').on("change", function () {
    var mode = $(this).val();
    var $firecrawlSettings = $(this)
      .closest("tr")
      .nextAll("tr")
      .slice(0, 2);

    if (mode === "firecrawl") {
      $firecrawlSettings.slideDown();
    } else {
      $firecrawlSettings.slideUp();
    }
  });

  // Trigger on page load to set initial state
  $('select[name="facty_options[fact_check_mode]"]').trigger("change");

  // Confirm cache clear
  $('button[name="clear_cache"]').on("click", function (e) {
    if (
      !confirm(
        "Are you sure you want to clear all cached fact-check results? This cannot be undone."
      )
    ) {
      e.preventDefault();
      return false;
    }
  });

  // Auto-save indicator
  var $form = $("form");
  var formChanged = false;

  $form.find("input, select, textarea").on("change", function () {
    formChanged = true;
  });

  $(window).on("beforeunload", function (e) {
    if (formChanged) {
      var confirmationMessage =
        "You have unsaved changes. Are you sure you want to leave?";
      e.returnValue = confirmationMessage;
      return confirmationMessage;
    }
  });

  $form.on("submit", function () {
    formChanged = false;
  });

  // Tooltips for help text
  $(".description").each(function () {
    var $this = $(this);
    if ($this.text().length > 100) {
      $this.addClass("long-description");
    }
  });

  // Copy email/IP on click
  $(document).on("click", "td code", function () {
    var text = $(this).text();
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        // Show temporary "Copied!" message
        var $copied = $('<span class="copied-msg">✓ Copied!</span>');
        $copied.insertAfter(this).fadeIn().delay(2000).fadeOut(function () {
          $(this).remove();
        });
      });
    }
  });

  // Add hover effect to table rows
  $(".wp-list-table tbody tr").hover(
    function () {
      $(this).css("background-color", "#f9fafb");
    },
    function () {
      $(this).css("background-color", "");
    }
  );

  // Stats counter animation (if on users or cache page)
  function animateValue(element, start, end, duration) {
    var range = end - start;
    var current = start;
    var increment = end > start ? 1 : -1;
    var stepTime = Math.abs(Math.floor(duration / range));
    var timer = setInterval(function () {
      current += increment;
      element.textContent = current;
      if (current == end) {
        clearInterval(timer);
      }
    }, stepTime);
  }

  $(".stat-number").each(function () {
    var $this = $(this);
    var finalValue = parseInt($this.text());
    if (!isNaN(finalValue)) {
      $this.text("0");
      animateValue(this, 0, finalValue, 1000);
    }
  });

  // Real-time API key validation
  $('input[name="facty_options[api_key]"]').on("input", function () {
    var $this = $(this);
    var value = $this.val();
    var $badge = $this.next(".facty-status-badge");

    if (value.length > 10) {
      if ($badge.length === 0) {
        $badge = $('<span class="facty-status-badge facty-status-success">✓ Key configured</span>');
        $this.after($badge);
      }
      $badge.fadeIn();
    } else {
      $badge.fadeOut();
    }
  });

  // Same for Firecrawl key
  $('input[name="facty_options[firecrawl_api_key]"]').on("input", function () {
    var $this = $(this);
    var value = $this.val();
    var $badge = $this.next(".facty-status-badge");

    if (value.length > 10) {
      if ($badge.length === 0) {
        $badge = $('<span class="facty-status-badge facty-status-success">✓ Key configured</span>');
        $this.after($badge);
      }
      $badge.fadeIn();
    } else {
      $badge.fadeOut();
    }
  });

  // Usage limit slider visual feedback
  $('input[name="facty_options[free_limit]"]').on("input", function () {
    var value = $(this).val();
    var $description = $(this).next(".description");
    if (value > 10) {
      $description.html(
        "<strong>Warning:</strong> Higher limits may increase API costs. Current limit: <strong>" +
          value +
          "</strong> checks per visitor."
      );
    } else {
      $description.html(
        "Number of free fact checks allowed per visitor. Current: <strong>" +
          value +
          "</strong>"
      );
    }
  });

  // Add "Changed" indicator next to fields
  $("input, select, textarea").on("change", function () {
    var $indicator = $(this).siblings(".field-changed-indicator");
    if ($indicator.length === 0) {
      $(this).after(
        '<span class="field-changed-indicator" style="color: #f59e0b; margin-left: 8px; font-size: 12px;">● Changed</span>'
      );
    }
  });

  $("form").on("submit", function () {
    $(".field-changed-indicator").remove();
  });

  // Keyboard shortcuts
  $(document).on("keydown", function (e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === "s") {
      e.preventDefault();
      $("form").submit();
    }
  });

  // Add helpful tooltips
  $('[data-tooltip]').each(function () {
    var $this = $(this);
    var tooltipText = $this.data("tooltip");
    $this.attr("title", tooltipText);
  });
});
