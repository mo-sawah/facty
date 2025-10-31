<?php
/**
 * Facty OpenRouter Analyzer
 * IMPROVED: Better scoring, user-focused reports, faster processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Analyzer {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Main analysis method with progress tracking
     */
    public function analyze($content, $task_id = null) {
        $api_key = $this->options['api_key'];
        $model = $this->options['model'];
        
        if ($task_id) {
            $this->update_progress($task_id, 20, 'analyzing', 'Analyzing with AI...');
        }
        
        $current_date = current_time('F j, Y');
        
        // IMPROVED: Shorter, faster, user-focused prompt
        $prompt = "You are analyzing this article to help READERS understand its accuracy. Today is {$current_date}.

STEP 1: Detect if SATIRE (absurd scenarios, obvious jokes). If satire, return: {\"score\":100,\"status\":\"Satire\",\"description\":\"This is satirical content.\",\"issues\":[],\"sources\":[]}

STEP 2: For real articles, analyze factual claims and score PRECISELY:
- 95-100: Completely accurate, well-sourced
- 85-94: Accurate with minor issues  
- 70-84: Mostly accurate, some problems
- 50-69: Mixed - significant concerns
- 30-49: Mostly inaccurate
- 0-29: False or highly misleading

ARTICLE:
{$content}

Return pure JSON (no markdown):
{
    \"score\": <precise 0-100 integer>,
    \"status\": \"Verified\" | \"Mostly Accurate\" | \"Needs Review\" | \"Multiple Errors\" | \"False\" | \"Satire\",
    \"description\": \"One sentence for readers\",
    \"issues\": [
        {
            \"claim\": \"Exact quote\",
            \"type\": \"Factual Error\" | \"Outdated\" | \"Misleading\" | \"Unverified\" | \"Missing Context\",
            \"severity\": \"high\" | \"medium\" | \"low\",
            \"what_article_says\": \"The claim\",
            \"the_problem\": \"Why it's wrong\",
            \"actual_facts\": \"What's actually true\",
            \"why_it_matters\": \"Impact on readers\"
        }
    ],
    \"verified_facts\": [
        {\"claim\": \"Accurate claim\", \"confidence\": \"high\"}
    ],
    \"sources\": [
        {\"title\": \"Source\", \"url\": \"https://...\", \"credibility\": \"high\"}
    ]
}

RULES:
- **CRITICAL: NEVER mark as \"Factual Error\" unless you have STRONG evidence that CONTRADICTS the claim**
- **Lack of sources = \"Unverified\", NOT \"Factual Error\"** (this is critical!)
- Unverified ≠ False (if you can't verify, say that, don't call it false)
- IMPORTANT: Unverified claims (lack of sources) should NOT heavily penalize the score - they're not the same as false claims
- For very recent events (last 1-2 hours), it's acceptable to mark as unverified with note \"Very recent - sources not indexed yet\"
- Write for readers, not editors  
- Use precise scores (not always 50)
- Be fair: good articles with some unverified claims can still get 75-85+
- When in doubt between Unverified and Factual Error: Choose Unverified";
        
        if ($task_id) {
            $this->update_progress($task_id, 60, 'verifying', 'Verifying facts...');
        }
        
        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url(),
                'X-Title' => get_bloginfo('name')
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 3000, // Reduced from 4000 for speed
                'temperature' => 0.2
            )),
            'timeout' => 90 // Reduced from 120 for speed
        ));
        
        if ($task_id) {
            $this->update_progress($task_id, 90, 'generating', 'Formatting report...');
        }
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
            throw new Exception('API Error (' . $http_code . '): ' . $error_message);
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response structure');
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        
        // Clean up the AI response
        $ai_content = preg_replace('/^```json\s*/', '', $ai_content);
        $ai_content = preg_replace('/\s*```$/', '', $ai_content);
        $ai_content = trim($ai_content);
        
        $result = json_decode($ai_content, true);
        
        if (!$result || !is_array($result)) {
            return array(
                'score' => 50,
                'status' => 'Analysis Incomplete',
                'description' => 'Analysis completed but response parsing failed.',
                'issues' => array(),
                'sources' => $this->extract_sources_from_response($body)
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
        
        // Validate score
        $result['score'] = intval($result['score']);
        if ($result['score'] < 0) $result['score'] = 0;
        if ($result['score'] > 100) $result['score'] = 100;
        
        // If no sources in result, try to extract from response annotations
        if (empty($result['sources'])) {
            $result['sources'] = $this->extract_sources_from_response($body);
        }
        
        // Add mode metadata
        $result['mode'] = 'openrouter';
        
        return $result;
    }
    
    /**
     * Extract sources from API response annotations
     */
    private function extract_sources_from_response($response_body) {
        $sources = array();
        $data = json_decode($response_body, true);
        
        if (isset($data['choices'][0]['message']['annotations'])) {
            foreach ($data['choices'][0]['message']['annotations'] as $annotation) {
                if (isset($annotation['type']) && $annotation['type'] === 'web_search') {
                    if (isset($annotation['web_search']['results'])) {
                        foreach ($annotation['web_search']['results'] as $result) {
                            if (isset($result['title']) && isset($result['url'])) {
                                $sources[] = array(
                                    'title' => $result['title'],
                                    'url' => $result['url']
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return array_slice($sources, 0, 8);
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
        ), 600); // 10 minutes
    }
}