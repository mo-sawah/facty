<?php
/**
 * Facty Editor Page - Complete Fact-Checker for Editors
 * Allows editors/journalists to paste text and get comprehensive fact-check reports
 * Access: yoursite.com/fact-check-editor
 * 
 * FIXED: Complete results rendering with all sections
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get options
$options = get_option('facty_options', array());

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fact-Check Editor | Facty</title>
    <?php 
    // Enqueue jQuery only
    wp_enqueue_script('jquery');
    wp_head(); 
    ?>
    
    <script type="text/javascript">
    // Define factChecker for editor page
    var factChecker = <?php 
        $facty_options = get_option('facty_options', array());
        echo json_encode(array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facty_nonce'),
            'theme_mode' => isset($facty_options['theme_mode']) ? $facty_options['theme_mode'] : 'light',
            'require_email' => isset($facty_options['require_email']) ? $facty_options['require_email'] : true,
            'free_limit' => isset($facty_options['free_limit']) ? $facty_options['free_limit'] : 5,
            'terms_url' => isset($facty_options['terms_url']) ? $facty_options['terms_url'] : '',
            'privacy_url' => isset($facty_options['privacy_url']) ? $facty_options['privacy_url'] : '',
            'fact_check_mode' => isset($facty_options['fact_check_mode']) ? $facty_options['fact_check_mode'] : 'openrouter',
            'colors' => array(
                'primary' => isset($facty_options['primary_color']) ? $facty_options['primary_color'] : '#3b82f6',
                'success' => isset($facty_options['success_color']) ? $facty_options['success_color'] : '#059669',
                'warning' => isset($facty_options['warning_color']) ? $facty_options['warning_color'] : '#f59e0b',
                'background' => isset($facty_options['background_color']) ? $facty_options['background_color'] : '#f8fafc'
            )
        ));
    ?>;
    </script>
    
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
        
        .clear-btn, .sample-btn {
            padding: 12px 24px;
            background: #f0f0f1;
            color: #2c3338;
            border: 1px solid #8c8f94;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .clear-btn:hover, .sample-btn:hover {
            background: #dcdcde;
        }
        
        /* Progress Styles */
        #fact-check-progress {
            margin-top: 30px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .progress-title {
            font-size: 16px;
            font-weight: 600;
            color: #1d2327;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .progress-icon {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .progress-percentage {
            font-size: 16px;
            font-weight: 600;
            color: #2271b1;
        }
        
        .progress-bar {
            height: 8px;
            background: #f0f0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #135e96);
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .progress-steps {
            display: grid;
            gap: 12px;
        }
        
        .progress-step {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #f6f7f7;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .progress-step.active {
            background: #e0f2fe;
            border: 1px solid #0284c7;
        }
        
        .progress-step.completed {
            background: #d1fae5;
            border: 1px solid #10b981;
        }
        
        .step-icon {
            width: 32px;
            height: 32px;
            background: #8c8f94;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .progress-step.active .step-icon {
            background: #0284c7;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .progress-step.completed .step-icon {
            background: #10b981;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-label {
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 4px;
        }
        
        .step-status {
            font-size: 13px;
            color: #646970;
        }
        
        /* Results Display Styles */
        #fact-check-results {
            margin-top: 30px;
        }
        
        .results-display {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .score-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }
        
        .score-display {
            text-align: center;
            min-width: 120px;
        }
        
        .score-number {
            font-size: 56px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .score-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .score-description {
            flex: 1;
        }
        
        .score-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .score-subtitle {
            font-size: 15px;
            opacity: 0.95;
            line-height: 1.6;
        }
        
        .status-good { color: #d1fae5; }
        .status-warning { color: #fef3c7; }
        .status-error { color: #fee2e2; }
        
        /* Issues Section */
        .issues-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .issues-title {
            font-size: 18px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .issue-item {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin-bottom: 16px;
            border-radius: 6px;
        }
        
        .issue-item.severity-low {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }
        
        .issue-item.severity-medium {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .issue-type {
            font-weight: 600;
            color: #991b1b;
            font-size: 14px;
        }
        
        .issue-severity {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 4px;
            background: rgba(0,0,0,0.1);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .issue-claim,
        .issue-problem,
        .issue-facts,
        .issue-impact {
            margin-bottom: 12px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .issue-claim { color: #1e40af; }
        .issue-problem { color: #991b1b; }
        .issue-facts { color: #065f46; }
        .issue-impact { color: #4b5563; }
        
        /* Verified Section */
        .verified-section {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .verified-title {
            font-size: 18px;
            font-weight: 600;
            color: #16a34a;
            margin-bottom: 16px;
        }
        
        .verified-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .verified-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            background: #16a34a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .verified-claim {
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .verified-confidence {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Sources Section */
        .sources-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 25px;
        }
        
        .sources-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .source-item {
            padding: 12px 16px;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        
        .source-item:hover {
            background: #f3f4f6;
        }
        
        .source-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            flex: 1;
        }
        
        .source-link:hover {
            text-decoration: underline;
        }
        
        .source-credibility {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            background: #dbeafe;
            color: #1e40af;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .error-section {
            padding: 40px;
            text-align: center;
            background: #fef2f2;
            border-radius: 8px;
            border: 2px dashed #fca5a5;
        }
        
        .error-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 12px;
        }
        
        .error-message {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .editor-header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            
            .score-section {
                flex-direction: column;
                text-align: center;
            }
            
            .editor-actions {
                flex-wrap: wrap;
            }
            
            .fact-check-btn,
            .clear-btn,
            .sample-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="editor-header">
        <div class="editor-logo">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            Facty Editor
        </div>
        <div class="editor-badge">Professional Fact-Checking Tool</div>
    </div>
    
    <div class="editor-container">
        <div class="editor-intro">
            <h1>📝 Fact-Check Any Text</h1>
            <p>Paste your article, press release, or content below to get an instant, comprehensive fact-check report powered by AI.</p>
        </div>
        
        <div class="editor-workspace">
            <div class="editor-tabs">
                <button class="editor-tab active">Text Editor</button>
            </div>
            
            <div class="editor-content">
                <div class="text-editor-area">
                    <label for="fact-check-text">Enter or paste your text (minimum 50 characters):</label>
                    <textarea id="fact-check-text" placeholder="Paste your article, press release, or any text you want to fact-check..."></textarea>
                    <div class="char-count">
                        <span id="char-count">0</span> characters • <span id="word-count">0</span> words
                    </div>
                </div>
                
                <div class="editor-actions">
                    <button id="start-fact-check" class="fact-check-btn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                        </svg>
                        Check Facts
                    </button>
                    <button id="clear-text" class="clear-btn">Clear</button>
                    <button id="load-sample" class="sample-btn">Load Sample</button>
                </div>
                
                <div id="results-area">
                    <div id="fact-check-progress" style="display: none;">
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
                    
                    <div id="fact-check-results" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    
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
        
        // Show results with complete rendering
        function showResults(result) {
            $('#fact-check-progress').hide();
            
            var scoreColor = '#059669';
            var statusClass = 'status-good';
            var statusText = '✓ Verified';
            
            if (result.score < 50) {
                scoreColor = '#ef4444';
                statusClass = 'status-error';
                statusText = '✗ Concerns Found';
            } else if (result.score < 75) {
                scoreColor = '#f59e0b';
                statusClass = 'status-warning';
                statusText = '⚠ Review Needed';
            }
            
            var html = '<div class="results-display">';
            
            // Score section
            html += '<div class="score-section">';
            html += '<div class="score-display">';
            html += '<div class="score-number" style="color: ' + scoreColor + ';">' + result.score + '</div>';
            html += '<div class="score-label">Accuracy Score</div>';
            html += '</div>';
            html += '<div class="score-description">';
            html += '<div class="score-title ' + statusClass + '">' + statusText + ' <span style="font-weight: 400; opacity: 0.7;">' + (result.status || '') + '</span></div>';
            html += '<div class="score-subtitle">' + escapeHtml(result.description || '') + '</div>';
            html += '</div>';
            html += '</div>';
            
            // Issues section
            if (result.issues && result.issues.length > 0) {
                html += '<div class="issues-section">';
                html += '<div class="issues-title">⚠️ Issues Found (' + result.issues.length + ')</div>';
                result.issues.forEach(function(issue) {
                    var severityClass = 'severity-' + (issue.severity || 'medium');
                    html += '<div class="issue-item ' + severityClass + '">';
                    html += '<div class="issue-header">';
                    html += '<span class="issue-type">' + escapeHtml(issue.type || 'Issue') + '</span>';
                    html += '<span class="issue-severity">' + (issue.severity || 'medium') + ' priority</span>';
                    html += '</div>';
                    
                    if (issue.what_article_says || issue.claim) {
                        html += '<div class="issue-claim"><strong>📰 Text says:</strong><br>"' + escapeHtml(issue.what_article_says || issue.claim) + '"</div>';
                    }
                    if (issue.the_problem) {
                        html += '<div class="issue-problem"><strong>❌ The problem:</strong><br>' + escapeHtml(issue.the_problem) + '</div>';
                    }
                    if (issue.actual_facts) {
                        html += '<div class="issue-facts"><strong>✅ Actual facts:</strong><br>' + escapeHtml(issue.actual_facts) + '</div>';
                    }
                    if (issue.why_it_matters) {
                        html += '<div class="issue-impact"><strong>💡 Why this matters:</strong><br>' + escapeHtml(issue.why_it_matters) + '</div>';
                    }
                    
                    html += '</div>';
                });
                html += '</div>';
            }
            
            // Verified facts section
            if (result.verified_facts && result.verified_facts.length > 0) {
                html += '<div class="verified-section">';
                html += '<div class="verified-title">✅ Verified Claims (' + result.verified_facts.length + ')</div>';
                html += '<div class="verified-list">';
                result.verified_facts.forEach(function(fact) {
                    html += '<div class="verified-item">';
                    html += '<span class="verified-icon">✓</span>';
                    html += '<div class="verified-content">';
                    html += '<div class="verified-claim">' + escapeHtml(fact.claim) + '</div>';
                    if (fact.confidence) {
                        html += '<div class="verified-confidence">Confidence: <strong>' + fact.confidence + '</strong></div>';
                    }
                    html += '</div></div>';
                });
                html += '</div></div>';
            }
            
            // Sources section
            if (result.sources && result.sources.length > 0) {
                html += '<div class="sources-section">';
                html += '<div class="sources-title">🔗 Sources Checked (' + result.sources.length + ')</div>';
                html += '<div class="sources-list">';
                result.sources.forEach(function(source) {
                    var credibilityClass = 'credibility-' + (source.credibility || 'medium');
                    html += '<div class="source-item ' + credibilityClass + '">';
                    html += '<a href="' + escapeHtml(source.url || '#') + '" target="_blank" rel="nofollow" class="source-link">' + escapeHtml(source.title || source.url || 'Source') + '</a>';
                    if (source.credibility) {
                        html += '<span class="source-credibility">' + source.credibility + '</span>';
                    }
                    html += '</div>';
                });
                html += '</div></div>';
            }
            
            html += '</div>';
            
            $('#fact-check-results').html(html).show();
            
            checkBtn.prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg> Check Facts');
        }
        
        // Escape HTML helper
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Show error
        function showError(message) {
            $('#fact-check-progress').hide();
            $('#fact-check-results').html(
                '<div class="error-section">' +
                '<div class="error-icon">⚠</div>' +
                '<div class="error-title">Analysis Failed</div>' +
                '<div class="error-message">' + escapeHtml(message) + '</div>' +
                '</div>'
            ).show();
            
            checkBtn.prop('disabled', false).html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg> Check Facts');
        }
    });
    </script>
</body>
</html>