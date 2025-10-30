<?php
/**
 * Facty Editor Page - Functional Text Fact-Checker
 * Allows editors/journalists to paste text and fact-check it
 * Access: yoursite.com/fact-check-editor
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in (optional - remove this if you want public access)
// if (!is_user_logged_in()) {
//     wp_redirect(wp_login_url(home_url('/fact-check-editor')));
//     exit;
// }

// Get options
$options = get_option('facty_options', array());

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fact-Check Editor | Facty</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            background: #f0f0f1;
            color: #1d2327;
            line-height: 1.6;
        }
        
        .editor-header {
            background: #fff;
            border-bottom: 1px solid #dcdcde;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .editor-logo {
            font-size: 24px;
            font-weight: 600;
            color: #1d2327;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .editor-logo svg {
            width: 32px;
            height: 32px;
            fill: #3b82f6;
        }
        
        .editor-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .editor-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .editor-intro {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        
        .editor-intro h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1d2327;
        }
        
        .editor-intro p {
            font-size: 15px;
            color: #50575e;
        }
        
        .editor-workspace {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            overflow: hidden;
        }
        
        .editor-tabs {
            display: flex;
            border-bottom: 1px solid #dcdcde;
            background: #f6f7f7;
        }
        
        .editor-tab {
            padding: 15px 25px;
            font-size: 14px;
            font-weight: 500;
            color: #50575e;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .editor-tab.active {
            color: #2271b1;
            border-bottom-color: #2271b1;
            background: #fff;
        }
        
        .editor-content {
            padding: 30px;
        }
        
        .text-editor-area {
            margin-bottom: 25px;
        }
        
        .text-editor-area label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 10px;
        }
        
        .text-editor-area textarea {
            width: 100%;
            min-height: 400px;
            padding: 20px;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            font-size: 16px;
            line-height: 1.7;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            resize: vertical;
            transition: border-color 0.2s;
        }
        
        .text-editor-area textarea:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .char-count {
            font-size: 13px;
            color: #646970;
            margin-top: 8px;
        }
        
        .editor-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .fact-check-btn {
            padding: 12px 28px;
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .fact-check-btn:hover {
            background: #135e96;
        }
        
        .fact-check-btn:disabled {
            background: #8c8f94;
            cursor: not-allowed;
        }
        
        .fact-check-btn svg {
            width: 18px;
            height: 18px;
        }
        
        .clear-btn {
            padding: 12px 24px;
            background: #f0f0f1;
            color: #2c3338;
            border: 1px solid #8c8f94;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .clear-btn:hover {
            background: #fff;
        }
        
        .sample-text-btn {
            padding: 12px 24px;
            background: transparent;
            color: #2271b1;
            border: 1px solid #2271b1;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .sample-text-btn:hover {
            background: rgba(34, 113, 177, 0.05);
        }
        
        .results-area {
            display: none;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f1;
        }
        
        .results-area.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .editor-content {
                padding: 20px;
            }
            
            .editor-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .fact-check-btn,
            .clear-btn,
            .sample-text-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    
    <?php
    // Enqueue fact-checker CSS and JS
    wp_enqueue_style('facty-style', FACTY_PLUGIN_URL . 'assets/css/facty.css', array(), FACTY_VERSION);
    wp_enqueue_script('jquery');
    wp_enqueue_script('facty-script', FACTY_PLUGIN_URL . 'assets/js/facty.js', array('jquery'), FACTY_VERSION, true);
    
    wp_localize_script('facty-script', 'factChecker', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('facty_nonce'),
        'theme_mode' => isset($options['theme_mode']) ? $options['theme_mode'] : 'light',
        'require_email' => false,
        'free_limit' => 999,
        'terms_url' => isset($options['terms_url']) ? $options['terms_url'] : '',
        'privacy_url' => isset($options['privacy_url']) ? $options['privacy_url'] : '',
        'fact_check_mode' => isset($options['fact_check_mode']) ? $options['fact_check_mode'] : 'perplexity',
        'colors' => array(
            'primary' => isset($options['primary_color']) ? $options['primary_color'] : '#3b82f6',
            'success' => isset($options['success_color']) ? $options['success_color'] : '#059669',
            'warning' => isset($options['warning_color']) ? $options['warning_color'] : '#f59e0b',
            'background' => isset($options['background_color']) ? $options['background_color'] : '#f8fafc'
        )
    ));
    
    wp_print_styles();
    wp_print_scripts();
    ?>
</head>
<body>
    <div class="editor-header">
        <div class="editor-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            Facty - Fact-Check Editor
        </div>
        <div class="editor-badge">EDITOR TOOL</div>
    </div>
    
    <div class="editor-container">
        <div class="editor-intro">
            <h1>✍️ Fact-Check Editor</h1>
            <p>Paste or write any text below and click "Check Facts" to verify accuracy using AI analysis with real-time web search.</p>
        </div>
        
        <div class="editor-workspace">
            <div class="editor-tabs">
                <button class="editor-tab active">Write & Check</button>
            </div>
            
            <div class="editor-content">
                <div class="text-editor-area">
                    <label for="fact-check-text">Your Text</label>
                    <textarea 
                        id="fact-check-text" 
                        placeholder="Paste or write your article, news story, or any text you want to fact-check...

Example: 
'The government announced a $500 billion infrastructure plan today. Transportation Secretary Jennifer Martinez said the plan will create 2 million jobs. The program includes $200 billion for highways and $150 billion for public transit.'"
                    ></textarea>
                    <div class="char-count">
                        <span id="char-count">0</span> characters | <span id="word-count">0</span> words
                    </div>
                </div>
                
                <div class="editor-actions">
                    <button class="fact-check-btn" id="start-fact-check" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                        </svg>
                        Check Facts
                    </button>
                    <button class="clear-btn" id="clear-text">Clear</button>
                    <button class="sample-text-btn" id="load-sample">Load Sample Text</button>
                </div>
                
                <div class="results-area" id="results-area">
                    <div class="fact-check-container" data-post-id="editor-custom" data-user-status='{"logged_in":false,"usage_count":0,"can_use":true,"email":"","is_registered":false}'>
                        <div class="fact-check-box">
                            <div id="fact-check-progress" class="fact-check-progress" style="display: none;">
                                <div class="progress-header">
                                    <div class="progress-title">
                                        <span class="progress-icon">⚡</span>
                                        <span>Analyzing Text...</span>
                                    </div>
                                    <div class="progress-percentage">0%</div>
                                </div>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%;"></div>
                                </div>
                                
                                <div class="progress-steps">
                                    <div class="progress-step" data-stage="starting">
                                        <div class="step-icon">1</div>
                                        <div class="step-content">
                                            <div class="step-label">Initializing</div>
                                            <div class="step-status">Preparing fact check...</div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-stage="analyzing">
                                        <div class="step-icon">2</div>
                                        <div class="step-content">
                                            <div class="step-label">AI Analysis</div>
                                            <div class="step-status">Analyzing with AI...</div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-stage="searching">
                                        <div class="step-icon">3</div>
                                        <div class="step-content">
                                            <div class="step-label">Source Search</div>
                                            <div class="step-status">Searching sources...</div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-stage="verifying">
                                        <div class="step-icon">4</div>
                                        <div class="step-content">
                                            <div class="step-label">Verification</div>
                                            <div class="step-status">Verifying facts...</div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-stage="generating">
                                        <div class="step-icon">5</div>
                                        <div class="step-content">
                                            <div class="step-label">Report Generation</div>
                                            <div class="step-status">Generating report...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="fact-check-results" class="results-container" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php wp_print_footer_scripts(); ?>
    
    <script>
    jQuery(document).ready(function($) {
        var textarea = $('#fact-check-text');
        var checkBtn = $('#start-fact-check');
        var clearBtn = $('#clear-text');
        var sampleBtn = $('#load-sample');
        var resultsArea = $('#results-area');
        var charCount = $('#char-count');
        var wordCount = $('#word-count');
        
        // Update character and word count
        function updateCounts() {
            var text = textarea.val();
            var chars = text.length;
            var words = text.trim() ? text.trim().split(/\s+/).length : 0;
            
            charCount.text(chars);
            wordCount.text(words);
            
            // Enable/disable check button
            checkBtn.prop('disabled', chars < 50);
        }
        
        textarea.on('input', updateCounts);
        
        // Clear button
        clearBtn.on('click', function() {
            if (confirm('Clear all text?')) {
                textarea.val('');
                updateCounts();
                resultsArea.removeClass('active');
                $('#fact-check-results').hide().empty();
                $('#fact-check-progress').hide();
            }
        });
        
        // Sample text button
        sampleBtn.on('click', function() {
            var sampleText = 'The government announced a $500 billion infrastructure plan today aimed at modernizing transportation systems. Transportation Secretary Jennifer Martinez said the plan will create over 2 million jobs in construction and engineering.\n\nThe proposal includes $200 billion for highway improvements, $150 billion for public transit expansion, and $100 billion for bridge and tunnel repairs across the country.\n\nEconomists have expressed mixed reactions to the plan. Some argue it will boost economic growth, while others worry about its impact on the national deficit, which currently stands at approximately $1.5 trillion.\n\nEnvironmental groups praised the inclusion of $50 billion dedicated to electric vehicle charging infrastructure and renewable energy integration.';
            
            textarea.val(sampleText);
            updateCounts();
            textarea.focus();
        });
        
        // Start fact check
        checkBtn.on('click', function() {
            var text = textarea.val().trim();
            
            if (text.length < 50) {
                alert('Please enter at least 50 characters to fact-check.');
                return;
            }
            
            resultsArea.addClass('active');
            $('#fact-check-results').hide().empty();
            $('#fact-check-progress').show();
            
            checkBtn.prop('disabled', true).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg> Checking...');
            
            // Start fact check
            $.ajax({
                url: factChecker.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'facty_check_custom_text',
                    text: text,
                    nonce: factChecker.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Start polling for progress
                        pollProgress(response.data.task_id);
                    } else {
                        showError(response.data);
                    }
                },
                error: function() {
                    showError('Connection error. Please try again.');
                }
            });
        });
        
        // Poll for progress
        function pollProgress(taskId) {
            var pollInterval = setInterval(function() {
                $.ajax({
                    url: factChecker.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'facty_check_progress',
                        task_id: taskId,
                        nonce: factChecker.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            
                            if (data.status === 'complete') {
                                clearInterval(pollInterval);
                                showResults(data.result);
                            } else if (data.status === 'error') {
                                clearInterval(pollInterval);
                                showError(data.message);
                            } else {
                                updateProgress(data.progress, data.stage, data.message);
                            }
                        }
                    }
                });
            }, 2000);
        }
        
        // Update progress
        function updateProgress(percent, stage, message) {
            $('.progress-fill').css('width', percent + '%');
            $('.progress-percentage').text(percent + '%');
            
            $('.progress-step').removeClass('active completed');
            $('.progress-step[data-stage="' + stage + '"]').addClass('active');
            $('.progress-step[data-stage="' + stage + '"]').prevAll().addClass('completed');
        }
        
        // Show results
        function showResults(result) {
            $('#fact-check-progress').hide();
            
            // Use the existing displayResults function from facty.js
            if (typeof window.displayResults === 'function') {
                window.displayResults(result, $('#fact-check-results'));
            } else {
                // Fallback simple display
                var html = '<div class="score-section">';
                html += '<div class="score-number">' + result.score + '</div>';
                html += '<div class="score-title">' + result.status + '</div>';
                html += '<div class="score-subtitle">' + result.description + '</div>';
                html += '</div>';
                $('#fact-check-results').html(html);
            }
            
            $('#fact-check-results').show();
            
            checkBtn.prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg> Check Facts');
        }
        
        // Show error
        function showError(message) {
            $('#fact-check-progress').hide();
            $('#fact-check-results').html(
                '<div class="error-section">' +
                '<div class="error-icon">⚠</div>' +
                '<div class="error-title">Analysis Failed</div>' +
                '<div class="error-message">' + message + '</div>' +
                '</div>'
            ).show();
            
            checkBtn.prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg> Check Facts');
        }
    });
    </script>
</body>
</html>