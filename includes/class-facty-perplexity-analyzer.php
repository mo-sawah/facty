<?php
/**
 * Facty Perplexity Analyzer
 * Ultra-fast, accurate fact-checking using Perplexity Sonar Pro with real-time web search
 * OPTIMIZED: Single API call for maximum speed and efficiency
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Perplexity_Analyzer {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Main analysis method - Single optimized call for speed
     */
    public function analyze($content, $task_id = null) {
        $api_key = $this->options['perplexity_api_key'];
        $model = isset($this->options['perplexity_model']) ? $this->options['perplexity_model'] : 'sonar-pro';
        
        if (empty($api_key)) {
            throw new Exception('Perplexity API key not configured');
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 15, 'analyzing', 'Starting Perplexity analysis with real-time web search...');
        }
        
        $current_date = current_time('F j, Y');
        
        // Check for satire first (quick check)
        if ($this->is_satire($content)) {
            if ($task_id) {
                $this->update_progress($task_id, 100, 'complete', 'Satire detected');
            }
            
            return array(
                'score' => 100,
                'status' => 'Satire',
                'description' => 'This is satirical content meant for entertainment.',
                'issues' => array(),
                'verified_facts' => array(),
                'sources' => array(),
                'mode' => 'perplexity'
            );
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 30, 'analyzing', 'AI analyzing article and searching web sources...');
        }
        
        // OPTIMIZED SINGLE CALL: Extract claims AND verify them with real-time web search
        $prompt = $this->build_fact_check_prompt($content, $current_date);
        
        if ($task_id) {
            $this->update_progress($task_id, 50, 'verifying', 'Perplexity searching and verifying facts...');
        }
        
        try {
            $result = $this->call_perplexity_api($prompt, $model, $api_key);
            
            if ($task_id) {
                $this->update_progress($task_id, 95, 'generating', 'Compiling final report...');
            }
            
            // Add mode metadata
            $result['mode'] = 'perplexity';
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Perplexity API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Build optimized fact-check prompt for single-call verification
     */
    private function build_fact_check_prompt($content, $current_date) {
        return "You are an expert fact-checker analyzing this article to help READERS understand its accuracy. Today is {$current_date}.

**YOUR TASK:**
1. Identify 5-10 key FACTUAL claims (skip opinions/predictions)
2. Use real-time web search to verify EACH claim
3. Assign a PRECISE accuracy score (0-100)
4. Return structured JSON

**SCORING GUIDE** (Be precise - not everything is 50):
- 95-100: Completely accurate, well-sourced, current
- 85-94: Accurate with minor/outdated details
- 70-84: Mostly accurate, some problems
- 50-69: Mixed - significant concerns
- 30-49: Mostly inaccurate
- 0-29: False or highly misleading

**ARTICLE:**
{$content}

**RETURN THIS EXACT JSON STRUCTURE:**
```json
{
    \"score\": <0-100 integer>,
    \"status\": \"Verified\" | \"Mostly Accurate\" | \"Needs Review\" | \"Multiple Errors\" | \"False\",
    \"description\": \"One clear sentence explaining overall accuracy\",
    \"issues\": [
        {
            \"claim\": \"Exact quote from article\",
            \"type\": \"Factual Error\" | \"Outdated\" | \"Misleading\" | \"Unverified\" | \"Missing Context\",
            \"severity\": \"high\" | \"medium\" | \"low\",
            \"what_article_says\": \"The problematic claim\",
            \"the_problem\": \"Why it's inaccurate/misleading\",
            \"actual_facts\": \"What's actually true (with sources)\",
            \"why_it_matters\": \"Impact on readers\"
        }
    ],
    \"verified_facts\": [
        {
            \"claim\": \"Accurate claim from article\",
            \"confidence\": \"high\" | \"medium\"
        }
    ],
    \"sources\": [
        {
            \"title\": \"Source name\",
            \"url\": \"https://...\",
            \"credibility\": \"high\" | \"medium\"
        }
    ]
}
```

**CRITICAL RULES:**
- Unverified ≠ False (if can't verify, mark as \"Unverified\", don't call it false)
- Use precise scores (not always 50/70/85)
- Be fair: truly accurate articles deserve 90-100
- Include ONLY the JSON in your response (no markdown formatting)
- Use YOUR real-time web search to verify current facts
- Cite credible sources you actually found";
    }
    
    /**
     * Call Perplexity API with web search enabled
     */
    private function call_perplexity_api($prompt, $model, $api_key) {
        $response = wp_remote_post('https://api.perplexity.ai/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a precise fact-checker that returns only valid JSON. Use your real-time web search to verify claims.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.2,
                'max_tokens' => 4000,
                'return_citations' => true,
                'search_recency_filter' => 'month' // Focus on recent sources
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
            throw new Exception('Perplexity API Error (' . $http_code . '): ' . $error_message);
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        
        // Clean up JSON response (remove markdown code blocks if present)
        $ai_content = preg_replace('/^```json\s*/m', '', $ai_content);
        $ai_content = preg_replace('/\s*```$/m', '', $ai_content);
        $ai_content = trim($ai_content);
        
        $result = json_decode($ai_content, true);
        
        if (!$result || !is_array($result)) {
            // Fallback if JSON parsing fails
            return array(
                'score' => 50,
                'status' => 'Analysis Incomplete',
                'description' => 'Analysis completed but response format was invalid. Please try again.',
                'issues' => array(),
                'verified_facts' => array(),
                'sources' => $this->extract_citations($data)
            );
        }
        
        // Ensure required fields exist
        $result = array_merge(array(
            'score' => 0,
            'status' => 'Unknown',
            'description' => 'No description provided',
            'issues' => array(),
            'verified_facts' => array(),
            'sources' => array()
        ), $result);
        
        // Validate and clamp score
        $result['score'] = max(0, min(100, intval($result['score'])));
        
        // Extract citations from Perplexity response and merge with sources
        $citations = $this->extract_citations($data);
        if (!empty($citations)) {
            $result['sources'] = array_merge($citations, $result['sources']);
            // Remove duplicates by URL
            $result['sources'] = array_values(array_unique($result['sources'], SORT_REGULAR));
            // Limit to 15 sources
            $result['sources'] = array_slice($result['sources'], 0, 15);
        }
        
        return $result;
    }
    
    /**
     * Extract citations from Perplexity API response
     */
    private function extract_citations($api_response_data) {
        $sources = array();
        
        // Perplexity returns citations in the response
        if (isset($api_response_data['citations']) && is_array($api_response_data['citations'])) {
            foreach ($api_response_data['citations'] as $citation) {
                if (isset($citation['url'])) {
                    $sources[] = array(
                        'title' => isset($citation['title']) ? $citation['title'] : parse_url($citation['url'], PHP_URL_HOST),
                        'url' => $citation['url'],
                        'credibility' => 'high'
                    );
                }
            }
        }
        
        return $sources;
    }
    
    /**
     * Quick satire detection
     */
    private function is_satire($content) {
        $satire_indicators = array(
            '/\b(satire|satirical|parody|joke|humor|humorous|comedy|comedic|onion|babylonbee)\b/i',
            '/\b(not to be taken seriously|for entertainment purposes|fictional account)\b/i'
        );
        
        foreach ($satire_indicators as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update progress for background processing
     */
    private function update_progress($task_id, $percentage, $stage, $message) {
        set_transient($task_id, array(
            'status' => 'processing',
            'progress' => $percentage,
            'stage' => $stage,
            'message' => $message
        ), 600);
    }
}