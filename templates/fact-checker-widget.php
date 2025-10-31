<?php
/**
 * Fact Checker Widget Template
 * Frontend HTML for the fact-checking widget
 * 
 * Available variables:
 * - $user_status: Array with user status information
 * - $mode_label: String describing the current fact-check mode
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fact-check-container" data-post-id="<?php echo get_the_ID(); ?>" data-user-status='<?php echo json_encode($user_status); ?>'>
    <div class="fact-check-box">
        <!-- Header Section -->
        <div class="fact-check-header">
            <div class="fact-check-title">
                <div class="fact-check-icon">✓</div>
                <h3>Fact Checker</h3>
            </div>
            <button class="check-button" aria-label="Check article facts">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                </svg>
                <span>Check Facts</span>
            </button>
        </div>

        <!-- Description -->
        <p class="fact-check-description">
            <?php echo esc_html($this->options['description_text']); ?>
        </p>

        <!-- Email Capture Form (shown when required) -->
        <div id="email-capture-form" class="email-capture-form" style="display: none;">
            <div class="form-header">
                <h4>Get Your Free Fact Check Report</h4>
                <p>You have <strong><?php echo esc_html($this->options['free_limit'] - $user_status['usage_count']); ?></strong> free fact checks remaining.</p>
            </div>
            
            <form class="email-form">
                <div class="form-group">
                    <label for="visitor-email">Email Address</label>
                    <input 
                        type="email" 
                        id="visitor-email" 
                        name="email" 
                        class="form-control" 
                        placeholder="your.email@example.com"
                        required
                    >
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="accept-terms" name="accept_terms" required>
                        <span>I agree to the 
                            <?php if (!empty($this->options['terms_url'])): ?>
                                <a href="<?php echo esc_url($this->options['terms_url']); ?>" target="_blank">Terms & Conditions</a>
                            <?php else: ?>
                                Terms & Conditions
                            <?php endif; ?>
                            and 
                            <?php if (!empty($this->options['privacy_url'])): ?>
                                <a href="<?php echo esc_url($this->options['privacy_url']); ?>" target="_blank">Privacy Policy</a>
                            <?php else: ?>
                                Privacy Policy
                            <?php endif; ?>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="submit-btn">
                    <span>Get Report</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13"></path>
                        <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                    </svg>
                </button>
            </form>
            
            <div class="form-footer">
                <p>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Your email is safe and will never be shared.
                </p>
            </div>
        </div>

        <!-- Signup Form (shown when limit reached) -->
        <div id="signup-form" class="signup-form" style="display: none;">
            <div class="form-header">
                <h4>🚀 Create Your Free Account</h4>
                <p>Get <strong>unlimited</strong> fact checks with a free account!</p>
            </div>
            
            <form class="signup-form-inner">
                <div class="form-group">
                    <label for="signup-name">Full Name</label>
                    <input 
                        type="text" 
                        id="signup-name" 
                        name="name" 
                        class="form-control" 
                        placeholder="John Doe"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="signup-email">Email Address</label>
                    <input 
                        type="email" 
                        id="signup-email" 
                        name="email" 
                        class="form-control" 
                        placeholder="your.email@example.com"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input 
                        type="password" 
                        id="signup-password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Minimum 6 characters"
                        required
                        minlength="6"
                    >
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="signup-terms" name="accept_terms" required>
                        <span>I agree to the 
                            <?php if (!empty($this->options['terms_url'])): ?>
                                <a href="<?php echo esc_url($this->options['terms_url']); ?>" target="_blank">Terms & Conditions</a>
                            <?php else: ?>
                                Terms & Conditions
                            <?php endif; ?>
                            and 
                            <?php if (!empty($this->options['privacy_url'])): ?>
                                <a href="<?php echo esc_url($this->options['privacy_url']); ?>" target="_blank">Privacy Policy</a>
                            <?php else: ?>
                                Privacy Policy
                            <?php endif; ?>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="signup-btn">
                    <span>Create Account & Start Checking</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"></path>
                        <path d="M12 5l7 7-7 7"></path>
                    </svg>
                </button>
            </form>
            
            <div class="form-footer">
                <p class="form-benefits">
                    ✓ Unlimited fact checks<br>
                    ✓ Save your reports<br>
                    ✓ No credit card required
                </p>
            </div>
        </div>

        <!-- Progress Container (shown during fact checking) -->
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

        <!-- Results Container (shown after analysis) -->
        <div id="fact-check-results" class="results-container" style="display: none;">
            <!-- Results will be dynamically inserted here by JavaScript -->
        </div>

        <!-- Mode Info -->
        <div class="fact-check-mode-info" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; display: flex; justify-content: space-between; align-items: center;">
            <span>Mode: <strong><?php echo esc_html($mode_label); ?></strong></span>
            <a href="https://disinformationcommission.com/" target="_blank" rel="noopener" style="color: #6b7280; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#6b7280'">
                Powered by <strong>The Disinformation Commission</strong>
            </a>
        </div>
    </div>
</div>