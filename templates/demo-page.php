<?php
/**
 * Facty Demo Page - Sales/Preview Tool
 * Shows potential clients how the fact-checker works
 * Access: yoursite.com/fact-check-demo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get options
$options = get_option('facty_options', array());

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fact-Check System Demo | Facty</title>
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
        
        .demo-header {
            background: #fff;
            border-bottom: 1px solid #dcdcde;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .demo-logo {
            font-size: 24px;
            font-weight: 600;
            color: #1d2327;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .demo-logo svg {
            width: 32px;
            height: 32px;
            fill: #3b82f6;
        }
        
        .demo-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .demo-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .demo-intro {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        
        .demo-intro h1 {
            font-size: 32px;
            margin-bottom: 15px;
            color: #1d2327;
        }
        
        .demo-intro p {
            font-size: 16px;
            color: #50575e;
            margin-bottom: 20px;
        }
        
        .demo-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }
        
        .demo-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f6f7f7;
            border-radius: 6px;
        }
        
        .demo-feature svg {
            width: 20px;
            height: 20px;
            fill: #10b981;
            flex-shrink: 0;
        }
        
        .demo-feature span {
            font-size: 14px;
            font-weight: 500;
        }
        
        .editor-simulation {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            overflow: hidden;
        }
        
        .editor-header {
            background: #f6f7f7;
            border-bottom: 1px solid #dcdcde;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .editor-title {
            font-size: 15px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .editor-actions {
            display: flex;
            gap: 10px;
        }
        
        .editor-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .editor-button.primary {
            background: #2271b1;
            color: #fff;
        }
        
        .editor-button.primary:hover {
            background: #135e96;
        }
        
        .editor-button.secondary {
            background: #f0f0f1;
            color: #2c3338;
            border: 1px solid #8c8f94;
        }
        
        .editor-button.secondary:hover {
            background: #fff;
        }
        
        .editor-content {
            padding: 40px;
        }
        
        .article-preview {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f1;
            font-size: 14px;
            color: #646970;
        }
        
        .article-category {
            background: #d97706;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .article-title {
            font-size: 36px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 25px;
            color: #1d2327;
        }
        
        .article-body {
            font-size: 17px;
            line-height: 1.7;
            color: #2c3338;
        }
        
        .article-body p {
            margin-bottom: 20px;
        }
        
        .article-body strong {
            font-weight: 600;
            color: #1d2327;
        }
        
        .demo-fact-checker {
            margin-top: 50px;
            padding-top: 40px;
            border-top: 2px solid #f0f0f1;
        }
        
        .demo-footer {
            background: #1d2327;
            color: #fff;
            text-align: center;
            padding: 40px 20px;
            margin-top: 60px;
        }
        
        .demo-footer h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .demo-footer p {
            font-size: 16px;
            color: #c3c4c7;
            margin-bottom: 25px;
        }
        
        .demo-cta {
            display: inline-block;
            padding: 14px 30px;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .demo-cta:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        @media (max-width: 768px) {
            .demo-container {
                padding: 0 15px;
            }
            
            .editor-content {
                padding: 20px;
            }
            
            .article-title {
                font-size: 28px;
            }
            
            .demo-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <?php
    // Enqueue fact-checker CSS and JS
    wp_enqueue_style('facty-style', FACTY_PLUGIN_URL . 'assets/css/facty.css', array(), FACTY_VERSION);
    wp_enqueue_script('facty-script', FACTY_PLUGIN_URL . 'assets/js/facty.js', array('jquery'), FACTY_VERSION, true);
    
    wp_localize_script('facty-script', 'factChecker', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('facty_nonce'),
        'theme_mode' => isset($options['theme_mode']) ? $options['theme_mode'] : 'light',
        'require_email' => false, // Disable email requirement for demo
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
    <div class="demo-header">
        <div class="demo-logo">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            Facty - AI Fact-Checking System
        </div>
        <div class="demo-badge">LIVE DEMO</div>
    </div>
    
    <div class="demo-container">
        <div class="demo-intro">
            <h1>See Fact-Checking in Action</h1>
            <p>This is a live demonstration of our AI-powered fact-checking system. The article below is analyzed in real-time using advanced AI models and web search to verify accuracy.</p>
            
            <div class="demo-features">
                <div class="demo-feature">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <span>Real-time verification</span>
                </div>
                <div class="demo-feature">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <span>Multiple source checking</span>
                </div>
                <div class="demo-feature">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <span>Accuracy scoring</span>
                </div>
                <div class="demo-feature">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <span>Detailed issue reporting</span>
                </div>
            </div>
        </div>
        
        <div class="editor-simulation">
            <div class="editor-header">
                <div class="editor-title">Article Preview</div>
                <div class="editor-actions">
                    <button class="editor-button secondary">Preview</button>
                    <button class="editor-button primary">Publish</button>
                </div>
            </div>
            
            <div class="editor-content">
                <article class="article-preview">
                    <div class="article-meta">
                        <span class="article-category">NEWS</span>
                        <span>October 30, 2025</span>
                        <span>5 min read</span>
                    </div>
                    
                    <h1 class="article-title">Breaking: Government Announces Major Infrastructure Investment</h1>
                    
                    <div class="article-body">
                        <p>In a significant policy announcement today, the government unveiled a comprehensive $500 billion infrastructure plan aimed at modernizing the nation's transportation systems over the next decade.</p>
                        
                        <p>The plan includes <strong>$200 billion for highway improvements</strong>, $150 billion for public transit expansion, and $100 billion for bridge and tunnel repairs across the country.</p>
                        
                        <p>According to Transportation Secretary Jennifer Martinez, "This represents the largest infrastructure investment in our nation's history. We expect to create over 2 million jobs in the construction and engineering sectors."</p>
                        
                        <p>The proposal has received mixed reactions from economists. Some argue that the investment will boost economic growth, while others express concerns about the impact on the national deficit, which currently stands at approximately $1.5 trillion.</p>
                        
                        <p>Environmental groups have praised the inclusion of $50 billion dedicated to electric vehicle charging infrastructure and renewable energy integration into the transportation grid.</p>
                        
                        <p>The legislation is expected to face debate in Congress next month, where it will need bipartisan support to pass.</p>
                    </div>
                    
                    <div class="demo-fact-checker">
                        <div class="fact-check-container" data-post-id="demo" data-user-status='{"logged_in":false,"usage_count":0,"can_use":true,"email":"","is_registered":false}'>
                            <div class="fact-check-box">
                                <div class="fact-check-header">
                                    <div class="fact-check-title">
                                        <div class="fact-check-icon">✓</div>
                                        <h3>AI Fact Checker</h3>
                                    </div>
                                    <button class="check-button" aria-label="Check article facts">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                                        </svg>
                                        <span>Check Facts</span>
                                    </button>
                                </div>
                                
                                <p class="fact-check-description">
                                    Click the button above to verify this article using AI analysis with real-time web search and multiple sources.
                                </p>
                                
                                <div id="fact-check-progress" class="fact-check-progress" style="display: none;">
                                    <div class="progress-header">
                                        <div class="progress-title">
                                            <span class="progress-icon">⚡</span>
                                            <span>Analyzing Article...</span>
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
                                        
                                        <div class="progress-step" data-stage="extracting">
                                            <div class="step-icon">2</div>
                                            <div class="step-content">
                                                <div class="step-label">Content Analysis</div>
                                                <div class="step-status">Reading article content...</div>
                                            </div>
                                        </div>
                                        
                                        <div class="progress-step" data-stage="analyzing">
                                            <div class="step-icon">3</div>
                                            <div class="step-content">
                                                <div class="step-label">AI Analysis</div>
                                                <div class="step-status">Analyzing with AI...</div>
                                            </div>
                                        </div>
                                        
                                        <div class="progress-step" data-stage="searching">
                                            <div class="step-icon">4</div>
                                            <div class="step-content">
                                                <div class="step-label">Source Search</div>
                                                <div class="step-status">Searching sources...</div>
                                            </div>
                                        </div>
                                        
                                        <div class="progress-step" data-stage="verifying">
                                            <div class="step-icon">5</div>
                                            <div class="step-content">
                                                <div class="step-label">Verification</div>
                                                <div class="step-status">Verifying facts...</div>
                                            </div>
                                        </div>
                                        
                                        <div class="progress-step" data-stage="generating">
                                            <div class="step-icon">6</div>
                                            <div class="step-content">
                                                <div class="step-label">Report Generation</div>
                                                <div class="step-status">Generating report...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="fact-check-results" class="results-container" style="display: none;"></div>
                                
                                <div class="fact-check-mode-info" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
                                    <span>Mode: <strong>Deep Research Mode</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
    
    <div class="demo-footer">
        <h3>Ready to Add Fact-Checking to Your Publication?</h3>
        <p>Join leading news organizations using AI-powered fact-checking to build trust and credibility.</p>
        <a href="mailto:contact@yourcompany.com" class="demo-cta">Request a Demo</a>
    </div>
    
    <?php wp_print_footer_scripts(); ?>
    
    <script>
    // Demo-specific: Auto-trigger fact check after 2 seconds for demo purposes
    jQuery(document).ready(function($) {
        setTimeout(function() {
            // Uncomment this to auto-trigger the demo
            // $('.fact-check-container .check-button').click();
        }, 2000);
    });
    </script>
</body>
</html>