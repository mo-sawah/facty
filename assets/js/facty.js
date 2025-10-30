jQuery(document).ready(function ($) {
  "use strict";

  // Cache for storing results per post ID to handle autoload
  var factCheckCache = {};
  var activeRequests = {};
  var progressPollers = {};

  // Initialize the fact checker (called on page load and autoload)
  function initFactChecker() {
    // Set CSS custom properties for colors and apply theme
    if (typeof factChecker !== "undefined" && factChecker.colors) {
      var root = document.documentElement;
      root.style.setProperty("--fc-primary", factChecker.colors.primary);
      root.style.setProperty("--fc-success", factChecker.colors.success);
      root.style.setProperty("--fc-warning", factChecker.colors.warning);
      root.style.setProperty("--fc-background", factChecker.colors.background);

      // Apply theme mode to all fact check containers
      $(".fact-check-container").removeClass("theme-dark theme-light");
      if (factChecker.theme_mode === "dark") {
        $(".fact-check-container").addClass("theme-dark");
      } else {
        $(".fact-check-container").addClass("theme-light");
      }
    }

    // Initialize each fact check container
    $(".fact-check-container").each(function () {
      var container = $(this);
      var postId = container.data("post-id");
      var userStatus = container.data("user-status");
      var resultsContainer = container.find("#fact-check-results");
      var progressContainer = container.find("#fact-check-progress");
      var button = container.find(".check-button");
      var emailForm = container.find("#email-capture-form");
      var signupForm = container.find("#signup-form");

      // Reset button state
      button.removeClass("loading");
      button.html(
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg><span>Check Facts</span>'
      );

      // Hide all forms and progress initially
      emailForm.hide();
      signupForm.hide();
      progressContainer.hide();

      // Clear results unless they belong to current post
      if (postId && factCheckCache[postId]) {
        displayResults(factCheckCache[postId], resultsContainer);
        resultsContainer.show();
      } else {
        resultsContainer.hide().empty();
      }
    });

    // Setup form handlers
    setupFormHandlers();
  }

  // Initialize on page load
  initFactChecker();

  // Re-initialize when new content is loaded (autoload compatibility)
  $(document).on("DOMNodeInserted", function (e) {
    if (
      $(e.target).find(".fact-check-container").length > 0 ||
      $(e.target).hasClass("fact-check-container")
    ) {
      setTimeout(initFactChecker, 100);
    }
  });

  // Listen for common autoload events
  $(document).on("autoload:complete page:change content:loaded", function () {
    setTimeout(initFactChecker, 100);
  });

  // Setup form event handlers
  function setupFormHandlers() {
    // Email form submission
    $(document)
      .off("submit", ".email-form")
      .on("submit", ".email-form", function (e) {
        e.preventDefault();
        handleEmailSubmission($(this));
      });

    // Signup form submission
    $(document)
      .off("submit", ".signup-form-inner")
      .on("submit", ".signup-form-inner", function (e) {
        e.preventDefault();
        handleSignupSubmission($(this));
      });
  }

  function handleEmailSubmission(form) {
    var container = form.closest(".fact-check-container");
    var email = form.find("#visitor-email").val();
    var acceptTerms = form.find("#accept-terms").is(":checked");
    var submitBtn = form.find(".submit-btn");

    // Clear previous errors
    form.find(".error-message").remove();
    form.find(".input-error").removeClass("input-error");

    // Validation
    if (!email || !isValidEmail(email)) {
      showFormError(
        form.find("#visitor-email"),
        "Please enter a valid email address"
      );
      return;
    }

    if (!acceptTerms) {
      showFormError(
        form.find("#accept-terms").parent(),
        "Please accept the terms and conditions"
      );
      return;
    }

    // Show loading
    submitBtn
      .addClass("loading")
      .html(
        '<div class="form-loading-spinner"></div><span>Submitting...</span>'
      );

    $.ajax({
      url: factChecker.ajaxUrl,
      type: "POST",
      data: {
        action: "facty_email_submit",
        email: email,
        nonce: factChecker.nonce,
      },
      success: function (response) {
        if (response.success) {
          showSuccessMessage(container, "Email saved! Starting fact check...");
          setTimeout(function () {
            form.closest(".email-capture-form").hide();
            startFactCheck(container);
          }, 1500);
        } else {
          showFormError(
            form,
            response.data || "Submission failed. Please try again."
          );
        }
      },
      error: function () {
        showFormError(form, "Connection error. Please try again.");
      },
      complete: function () {
        submitBtn
          .removeClass("loading")
          .html(
            '<span>Get Report</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"></path><path d="M22 2L15 22L11 13L2 9L22 2Z"></path></svg>'
          );
      },
    });
  }

  function handleSignupSubmission(form) {
    var container = form.closest(".fact-check-container");
    var name = form.find("#signup-name").val();
    var email = form.find("#signup-email").val();
    var password = form.find("#signup-password").val();
    var acceptTerms = form.find("#signup-terms").is(":checked");
    var submitBtn = form.find(".signup-btn");

    // Clear previous errors
    form.find(".error-message").remove();
    form.find(".input-error").removeClass("input-error");

    // Validation
    if (!name.trim()) {
      showFormError(form.find("#signup-name"), "Please enter your name");
      return;
    }

    if (!email || !isValidEmail(email)) {
      showFormError(
        form.find("#signup-email"),
        "Please enter a valid email address"
      );
      return;
    }

    if (!password || password.length < 6) {
      showFormError(
        form.find("#signup-password"),
        "Password must be at least 6 characters"
      );
      return;
    }

    if (!acceptTerms) {
      showFormError(
        form.find("#signup-terms").parent(),
        "Please accept the terms and conditions"
      );
      return;
    }

    // Show loading
    submitBtn
      .addClass("loading")
      .html(
        '<div class="form-loading-spinner"></div><span>Creating Account...</span>'
      );

    $.ajax({
      url: factChecker.ajaxUrl,
      type: "POST",
      data: {
        action: "facty_signup",
        name: name,
        email: email,
        password: password,
        nonce: factChecker.nonce,
      },
      success: function (response) {
        if (response.success) {
          showSuccessMessage(
            container,
            "Account created! Starting fact check..."
          );
          setTimeout(function () {
            form.closest(".signup-form").hide();
            startFactCheck(container);
          }, 1500);
        } else {
          showFormError(
            form,
            response.data || "Signup failed. Please try again."
          );
        }
      },
      error: function () {
        showFormError(form, "Connection error. Please try again.");
      },
      complete: function () {
        submitBtn
          .removeClass("loading")
          .html(
            '<span>Create Account & Continue</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"></path><path d="M22 2L15 22L11 13L2 9L22 2Z"></path></svg>'
          );
      },
    });
  }

  // Handle click events
  $(document).on("click", ".check-button", function (e) {
    e.preventDefault();
    var container = $(this).closest(".fact-check-container");
    checkUserAccessAndProceed(container);
  });

  // Global fact checker function
  window.factCheckerStart = function (element) {
    var container = element
      ? $(element).closest(".fact-check-container")
      : $(".fact-check-container").first();
    checkUserAccessAndProceed(container);
  };

  // Make this function global so it can be called from the HTML onclick
  window.checkUserAccessAndProceed = function (container) {
    var userStatus = container.data("user-status");
    var emailForm = container.find("#email-capture-form");
    var signupForm = container.find("#signup-form");

    // If user is logged in or registered, proceed directly
    if (
      userStatus &&
      (userStatus.type === "logged_in" || userStatus.type === "registered")
    ) {
      startFactCheck(container);
      return;
    }

    // If user has exceeded limit, show signup form
    if (
      userStatus &&
      userStatus.type === "free" &&
      userStatus.remaining === 0
    ) {
      emailForm.hide();
      signupForm.show();
      return;
    }

    // If user has email and can still use, proceed directly
    if (userStatus && userStatus.type === "free" && userStatus.remaining > 0) {
      startFactCheck(container);
      return;
    }

    // If require email is disabled, proceed directly
    if (typeof factChecker !== "undefined" && !factChecker.require_email) {
      startFactCheck(container);
      return;
    }

    // Otherwise, show email capture form
    signupForm.hide();
    emailForm.show();
  };

  function startFactCheck(container) {
    var button = container.find(".check-button");
    var resultsContainer = container.find("#fact-check-results");
    var progressContainer = container.find("#fact-check-progress");
    var postId = container.data("post-id");
    var emailForm = container.find("#email-capture-form");
    var signupForm = container.find("#signup-form");

    if (!postId) {
      showError(
        "Unable to identify article. Please refresh the page.",
        resultsContainer
      );
      return;
    }

    if (button.hasClass("loading") || activeRequests[postId]) {
      return;
    }

    // Hide forms
    emailForm.hide();
    signupForm.hide();

    // Check if we have cached results for this specific post
    if (factCheckCache[postId]) {
      displayResults(factCheckCache[postId], resultsContainer);
      resultsContainer.show();
      return;
    }

    // Show loading state
    button.addClass("loading");
    button.html('<div class="loading-spinner"></div><span>Starting...</span>');
    resultsContainer.hide();

    // Show progress container
    progressContainer.show();
    resetProgress(progressContainer);

    // Cancel any existing request for this post
    if (activeRequests[postId]) {
      activeRequests[postId].abort();
    }

    // Make AJAX request to start background processing
    activeRequests[postId] = $.ajax({
      url: factChecker.ajaxUrl,
      type: "POST",
      data: {
        action: "facty_check_article",
        post_id: postId,
        nonce: factChecker.nonce,
      },
      success: function (response) {
        if (response.success && response.data.task_id) {
          // Start polling for progress
          pollProgress(postId, response.data.task_id, container);
        } else {
          showError(
            response.data || "Failed to start analysis. Please try again.",
            resultsContainer
          );
          progressContainer.hide();
        }
      },
      error: function (xhr, status, error) {
        if (status === "abort") {
          return; // Request was cancelled
        }

        var errorMessage = "Failed to start analysis. Please try again.";
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        }
        showError(errorMessage, resultsContainer);
        progressContainer.hide();
      },
      complete: function () {
        // Clear active request
        delete activeRequests[postId];
      },
    });
  }

  function resetProgress(progressContainer) {
    progressContainer.find(".progress-fill").css("width", "0%");
    progressContainer.find(".progress-percentage").text("0%");
    progressContainer.find(".progress-title-text").text("Starting...");
    progressContainer
      .find(".progress-step")
      .removeClass("active complete error");
    progressContainer.find(".step-status").text("Waiting...");
  }

  function pollProgress(postId, taskId, container) {
    var button = container.find(".check-button");
    var resultsContainer = container.find("#fact-check-results");
    var progressContainer = container.find("#fact-check-progress");

    // Clear any existing poller
    if (progressPollers[postId]) {
      clearInterval(progressPollers[postId]);
    }

    // Poll every 2 seconds
    progressPollers[postId] = setInterval(function () {
      $.ajax({
        url: factChecker.ajaxUrl,
        type: "POST",
        data: {
          action: "facty_check_progress",
          task_id: taskId,
          nonce: factChecker.nonce,
        },
        success: function (response) {
          if (response.success && response.data) {
            updateProgress(progressContainer, response.data);

            // Check if complete
            if (response.data.status === "complete") {
              clearInterval(progressPollers[postId]);
              delete progressPollers[postId];

              // Display results
              if (response.data.result) {
                factCheckCache[postId] = response.data.result;
                displayResults(response.data.result, resultsContainer);
                resultsContainer.show();
                progressContainer.fadeOut();
              }

              // Reset button
              button.removeClass("loading");
              button.html(
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg><span>Re-check</span>'
              );
            } else if (response.data.status === "error") {
              clearInterval(progressPollers[postId]);
              delete progressPollers[postId];

              showError(
                response.data.error || "Analysis failed. Please try again.",
                resultsContainer
              );
              progressContainer.hide();

              // Reset button
              button.removeClass("loading");
              button.html(
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg><span>Retry</span>'
              );
            }
          }
        },
        error: function () {
          // Continue polling even on error
          console.log("Progress poll error, will retry...");
        },
      });
    }, 2000); // Poll every 2 seconds

    // Safety timeout: stop polling after 10 minutes
    setTimeout(function () {
      if (progressPollers[postId]) {
        clearInterval(progressPollers[postId]);
        delete progressPollers[postId];
        showError("Analysis timed out. Please try again.", resultsContainer);
        progressContainer.hide();
        button.removeClass("loading");
        button.html(
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg><span>Retry</span>'
        );
      }
    }, 600000); // 10 minutes
  }

  // IMPROVED: updateProgress with checkmarks
  function updateProgress(progressContainer, data) {
    var progress = data.progress || 0;
    var stage = data.stage || "starting";
    var message = data.message || "Processing...";

    // Update progress bar
    progressContainer.find(".progress-fill").css("width", progress + "%");
    progressContainer
      .find(".progress-percentage")
      .text(Math.round(progress) + "%");

    // Stage mapping
    var stages = {
      starting: { step: "starting", percent: 5 },
      extracting: { step: "extracting", percent: 20 },
      analyzing: { step: "analyzing", percent: 40 },
      searching: { step: "searching", percent: 60 },
      verifying: { step: "verifying", percent: 75 },
      generating: { step: "generating", percent: 90 },
    };

    var currentStage = stages[stage] || {
      step: "analyzing",
      percent: progress,
    };

    // Mark steps as complete or active with CHECKMARKS
    progressContainer.find(".progress-step").each(function () {
      var $step = $(this);
      var stepStage = $step.data("stage");
      var stepIcon = $step.find(".step-icon");

      if (!stepStage) return;

      var stageInfo = stages[stepStage];
      if (!stageInfo) return;

      // Clear all states
      $step.removeClass("active complete error");

      if (stageInfo.percent < currentStage.percent) {
        // Step is complete - show checkmark
        $step.addClass("complete");
        stepIcon.html("✓");
        $step.find(".step-status").text("Complete");
      } else if (stepStage === stage) {
        // Current step - show as active
        $step.addClass("active");
        $step.find(".step-status").text(message);
      } else {
        // Future step - reset icon
        var stepNumber = $step.index() + 1;
        stepIcon.text(stepNumber);
        $step.find(".step-status").text("Waiting...");
      }
    });

    if (stage === "complete" || progress >= 100) {
      progressContainer.find(".progress-step").addClass("complete");
      progressContainer.find(".step-icon").html("✓");
      progressContainer.find(".step-status").text("Complete");
    }

    if (data.status === "error") {
      progressContainer.find(".progress-step.active").addClass("error");
    }
  }

  // IMPROVED: displayResults with user-focused layout
  function displayResults(data, container) {
    var now = new Date();
    var timeString =
      now.toLocaleDateString("en-US", {
        month: "numeric",
        day: "numeric",
        year: "numeric",
      }) +
      " • " +
      now.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      });

    var statusClass = "status-good";
    var statusText = "✓ Verified";
    var scoreColor = "var(--fc-success, #059669)";

    if (data.score < 50) {
      statusClass = "status-error";
      statusText = "✗ Concerns Found";
      scoreColor = "#ef4444";
    } else if (data.score < 75) {
      statusClass = "status-warning";
      statusText = "⚠ Review Needed";
      scoreColor = "var(--fc-warning, #f59e0b)";
    }

    var html = '<div class="score-section">';
    html += '<div class="score-display">';
    html +=
      '<div class="score-number" style="color: ' +
      scoreColor +
      ';">' +
      data.score +
      "</div>";
    html += '<div class="score-label">Score</div>';
    html += "</div>";
    html += '<div class="score-description">';
    html +=
      '<div class="score-title ' +
      statusClass +
      '">' +
      statusText +
      ' <span style="font-weight: 400; opacity: 0.7;">' +
      (data.status || "") +
      "</span></div>";
    html +=
      '<div class="score-subtitle">' +
      escapeHtml(data.description || "") +
      "</div>";
    html += "</div>";
    html += "</div>";

    // USER-FOCUSED Issues section
    if (data.issues && data.issues.length > 0) {
      html += '<div class="issues-section">';
      html +=
        '<div class="issues-title">⚠️ Issues Found (' +
        data.issues.length +
        ")</div>";
      data.issues.forEach(function (issue) {
        var severityClass = "severity-" + (issue.severity || "medium");
        html += '<div class="issue-item ' + severityClass + '">';

        // Header with type and severity
        html += '<div class="issue-header">';
        html +=
          '<span class="issue-type">' +
          escapeHtml(issue.type || "Issue") +
          "</span>";
        html +=
          '<span class="issue-severity ' +
          severityClass +
          '">' +
          (issue.severity || "medium") +
          " priority</span>";
        html += "</div>";

        // What article says
        if (issue.what_article_says || issue.claim) {
          html +=
            '<div class="issue-claim"><strong>📰 Article says:</strong><br>"' +
            escapeHtml(issue.what_article_says || issue.claim) +
            '"</div>';
        }

        // The problem
        if (issue.the_problem || issue.description) {
          html +=
            '<div class="issue-problem"><strong>❌ The problem:</strong><br>' +
            escapeHtml(issue.the_problem || issue.description) +
            "</div>";
        }

        // Actual facts
        if (issue.actual_facts || issue.correct_information) {
          html +=
            '<div class="issue-facts"><strong>✅ Actual facts:</strong><br>' +
            escapeHtml(issue.actual_facts || issue.correct_information) +
            "</div>";
        }

        // Why it matters
        if (issue.why_it_matters || issue.reader_impact) {
          html +=
            '<div class="issue-impact"><strong>💡 Why this matters:</strong><br>' +
            escapeHtml(issue.why_it_matters || issue.reader_impact) +
            "</div>";
        }

        html += "</div>";
      });
      html += "</div>";
    }

    // NEW: Verified Facts section
    if (data.verified_facts && data.verified_facts.length > 0) {
      html += '<div class="verified-section">';
      html +=
        '<div class="verified-title">✅ Verified Claims (' +
        data.verified_facts.length +
        ")</div>";
      html += '<div class="verified-list">';
      data.verified_facts.forEach(function (fact) {
        html += '<div class="verified-item">';
        html += '<span class="verified-icon">✓</span>';
        html += '<div class="verified-content">';
        html +=
          '<div class="verified-claim">' + escapeHtml(fact.claim) + "</div>";
        if (fact.confidence) {
          html +=
            '<div class="verified-confidence">Confidence: <strong>' +
            fact.confidence +
            "</strong></div>";
        }
        html += "</div>";
        html += "</div>";
      });
      html += "</div>";
      html += "</div>";
    }

    // Sources section
    if (data.sources && data.sources.length > 0) {
      html += '<div class="sources-section">';
      html +=
        '<div class="sources-title">🔗 Sources Checked (' +
        data.sources.length +
        ")</div>";
      html += '<div class="sources-list">';
      data.sources.forEach(function (source) {
        var credibilityClass =
          "credibility-" + (source.credibility || "medium");
        html += '<div class="source-item ' + credibilityClass + '">';
        html +=
          '<a href="' +
          escapeHtml(source.url || "#") +
          '" target="_blank" rel="nofollow" class="source-link">' +
          escapeHtml(source.title || source.url || "Source") +
          "</a>";
        if (source.credibility) {
          html +=
            '<span class="source-credibility">' +
            source.credibility +
            "</span>";
        }
        html += "</div>";
      });
      html += "</div>";
      html += "</div>";
    }

    // CTA Buttons
    var currentArticleUrl = window.location.href;
    var detailedAnalysisUrl =
      "https://news.disinformationcommission/search/?prefill_url=" +
      encodeURIComponent(currentArticleUrl);

    html += '<div class="fact-check-cta-buttons">';
    html +=
      '<a href="' +
      detailedAnalysisUrl +
      '" class="cta-button cta-primary" target="_blank">Get Detailed Analysis</a>';
    html +=
      '<a href="https://webmon.disinformationcommission.com/" class="cta-button cta-secondary" target="_blank">Web Monitor</a>';
    html +=
      '<a href="https://disinformationcommission.com/tools" class="cta-button cta-secondary" target="_blank">More Tools</a>';
    html += "</div>";

    html += '<div class="voicing-info">';
    html += '<small style="color: #94a3b8;">Analyzed: ' + timeString;
    if (data.mode) {
      html += " • Deep Research Mode";
    }
    html += "</small>";
    html += "</div>";

    container.html(html);
  }

  function showError(message, container) {
    container.html(
      '<div class="error-section">' +
        '<div class="error-icon">⚠</div>' +
        '<div class="error-title">Analysis Failed</div>' +
        '<div class="error-message">' +
        escapeHtml(message) +
        "</div>" +
        "</div>"
    );
    container.show();
  }

  function escapeHtml(text) {
    if (!text) return "";
    var map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  // Download report functionality
  window.downloadReport = function () {
    var container = $(".fact-check-container");
    var postTitle = $("h1").first().text() || document.title;
    var results = container.find("#fact-check-results");

    if (!results.is(":visible")) {
      alert("No report to download. Please run a fact check first.");
      return;
    }

    var scoreNumber = results.find(".score-number").text();
    var scoreTitle = results
      .find(".score-title")
      .text()
      .replace(/[✓⚠✗]/g, "")
      .trim();
    var scoreDescription = results.find(".score-subtitle").text();

    var reportContent = "FACT CHECK REPORT\n";
    reportContent += "===================\n\n";
    reportContent += "Article: " + postTitle + "\n";
    reportContent += "URL: " + window.location.href + "\n";
    reportContent += "Date: " + new Date().toLocaleDateString() + "\n";
    reportContent += "Time: " + new Date().toLocaleTimeString() + "\n\n";
    reportContent += "ANALYSIS RESULTS:\n";
    reportContent += "-----------------\n";
    reportContent += "Score: " + scoreNumber + "/100\n";
    reportContent += "Status: " + scoreTitle + "\n";
    reportContent += "Description: " + scoreDescription + "\n\n";

    var issues = results.find(".issue-item");
    if (issues.length > 0) {
      reportContent += "ISSUES IDENTIFIED:\n";
      reportContent += "==================\n\n";

      issues.each(function (index) {
        var type = $(this).find(".issue-type").text();
        var description = $(this).find(".issue-description").text();
        var suggestion = $(this).find(".issue-suggestion").text();

        reportContent += index + 1 + ". " + type + "\n";
        reportContent += "   Problem: " + description + "\n";
        reportContent += "   " + suggestion + "\n\n";
      });
    }

    var sources = results.find(".source-link");
    if (sources.length > 0) {
      reportContent += "WEB SOURCES VERIFIED:\n";
      reportContent += "=====================\n\n";

      sources.each(function (index) {
        reportContent += index + 1 + ". " + $(this).text() + "\n";
        reportContent += "   URL: " + $(this).attr("href") + "\n\n";
      });
    }

    reportContent += "\n";
    reportContent += "METHODOLOGY:\n";
    reportContent += "============\n";
    reportContent +=
      "This fact-check was performed using AI analysis combined with real-time web search.\n";
    reportContent +=
      "The system searched current web sources to verify factual claims in the article.\n";
    reportContent +=
      "All sources listed above were accessed during the verification process.\n\n";

    var pluginTitle =
      typeof factChecker !== "undefined" && factChecker.plugin_title
        ? factChecker.plugin_title
        : "Facty";
    reportContent += "Generated by " + pluginTitle + " Plugin\n";
    reportContent += "Plugin by Mohamed Sawah - https://sawahsolutions.com";

    // Create and download file
    var blob = new Blob([reportContent], { type: "text/plain" });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = url;
    a.download =
      "fact-check-report-" +
      slugify(postTitle) +
      "-" +
      new Date().getTime() +
      ".txt";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  };

  // Share results functionality
  window.shareResults = function () {
    var container = $(".fact-check-container");
    var postTitle = $("h1").first().text() || document.title;
    var results = container.find("#fact-check-results");

    if (!results.is(":visible")) {
      alert("No results to share. Please run a fact check first.");
      return;
    }

    var scoreNumber = results.find(".score-number").text();
    var scoreTitle = results
      .find(".score-title")
      .text()
      .replace(/[✓⚠✗]/g, "")
      .trim();

    var shareText =
      'Fact Check Results for "' +
      postTitle +
      '"\n\nScore: ' +
      scoreNumber +
      "/100 - " +
      scoreTitle +
      "\n\nVerified using AI with real-time web search.\n\n" +
      window.location.href;

    if (navigator.share) {
      // Use native sharing if available
      navigator
        .share({
          title: "Fact Check: " + postTitle,
          text: shareText,
          url: window.location.href,
        })
        .catch(function (err) {
          console.log("Error sharing:", err);
        });
    } else {
      // Fallback: copy to clipboard
      if (navigator.clipboard) {
        navigator.clipboard.writeText(shareText).then(
          function () {
            alert("Fact check results copied to clipboard!");
          },
          function () {
            fallbackCopyTextToClipboard(shareText);
          }
        );
      } else {
        fallbackCopyTextToClipboard(shareText);
      }
    }
  };

  function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      var successful = document.execCommand("copy");
      if (successful) {
        alert("Fact check results copied to clipboard!");
      } else {
        alert("Could not copy results. Please select and copy manually.");
      }
    } catch (err) {
      alert("Could not copy results. Please select and copy manually.");
    }

    document.body.removeChild(textArea);
  }

  function slugify(text) {
    return text
      .toString()
      .toLowerCase()
      .replace(/\s+/g, "-") // Replace spaces with -
      .replace(/[^\w\-]+/g, "") // Remove all non-word chars
      .replace(/\-\-+/g, "-") // Replace multiple - with single -
      .replace(/^-+/, "") // Trim - from start of text
      .replace(/-+$/, "") // Trim - from end of text
      .substring(0, 50); // Limit length
  }

  // Keyboard accessibility
  $(document).on("keydown", ".fact-check-container", function (e) {
    if (e.key === "Enter" || e.key === " ") {
      if (e.target.classList.contains("check-button")) {
        e.preventDefault();
        checkUserAccessAndProceed($(e.target).closest(".fact-check-container"));
      }
    }
  });

  // Clean up on page unload
  $(window).on("beforeunload", function () {
    // Cancel any ongoing requests
    for (var postId in activeRequests) {
      if (activeRequests[postId] && activeRequests[postId].abort) {
        activeRequests[postId].abort();
      }
    }
    // Stop all progress pollers
    for (var postId in progressPollers) {
      clearInterval(progressPollers[postId]);
    }
  });
});
