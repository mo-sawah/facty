<?php
/**
 * Facty Firecrawl Analyzer
 * Handles deep multi-step fact-checking using Firecrawl API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Firecrawl {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Main Firecrawl analysis with progress tracking
     */
    public function analyze($content, $task_id = null) {
        if ($task_id) {
            $this->update_progress($task_id, 10, 'extracting', 'Extracting claims from article...');
        }
        
        // Step 1: Extract claims from content using AI
        $claims = $this->extract_claims($content);
        
        if (empty($claims)) {
            throw new Exception('No verifiable claims found in content');
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 30, 'searching', 'Searching sources for verification...');
        }
        
        // Step 2: Multi-step verification using Firecrawl
        $verification_results = array();
        $all_sources = array();
        $total_claims = count($claims);
        
        foreach ($claims as $index => $claim) {
            if ($task_id) {
                $progress = 30 + (($index + 1) / $total_claims) * 50;
                $this->update_progress($task_id, $progress, 'verifying', 'Verifying claim ' . ($index + 1) . ' of ' . $total_claims . '...');
            }
            
            $claim_result = $this->verify_claim($claim);
            $verification_results[] = $claim_result;
            
            if (!empty($claim_result['sources'])) {
                $all_sources = array_merge($all_sources, $claim_result['sources']);
            }
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 85, 'generating', 'Generating final report...');
        }
        
        // Step 3: Generate final report using AI
        $final_result = $this->generate_report($content, $verification_results, $all_sources);
        
        return $final_result;
    }
    
    /**
     * Extract claims from content using AI
     */
    private function extract_claims($content) {
        $api_key = $this->options['api_key'];
        $model = $this->options['model'];
        $max_claims = intval($this->options['firecrawl_max_claims']);
        
        $prompt = "Analyze this article and extract the top {$max_claims} most important FACTUAL claims that can be verified.

IMPORTANT: Extract claims from ALL types of articles including news, analysis, and OPINION pieces. Only skip pure satire/parody.

Do NOT extract:
- Pure satirical or parody content (like The Onion)
- Obvious humor content

DO extract claims from:
- News articles
- Opinion pieces and editorials (extract the factual claims used to support the opinions)
- Analysis articles

For each claim, provide:
1. The exact claim from the article
2. A concise search query to verify it (3-5 words)
3. The importance level (high/medium/low)

ARTICLE:
{$content}

Return ONLY valid JSON (no markdown):
{
    \"content_type\": \"news\" | \"opinion\" | \"satire\",
    \"claims\": [
        {
            \"claim\": \"specific factual claim\",
            \"search_query\": \"concise search terms\",
            \"importance\": \"high\" | \"medium\" | \"low\"
        }
    ]
}";

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
                'max_tokens' => 1500,
                'temperature' => 0.1
            )),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to extract claims: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid AI response when extracting claims');
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        $ai_content = preg_replace('/^```json\s*/', '', $ai_content);
        $ai_content = preg_replace('/\s*```$/', '', $ai_content);
        $result = json_decode($ai_content, true);
        
        // Only skip satire, allow opinions to be fact-checked
        if (isset($result['content_type']) && $result['content_type'] === 'satire') {
            return array();
        }
        
        return isset($result['claims']) ? $result['claims'] : array();
    }
    
    /**
     * Verify a single claim using Firecrawl
     */
    private function verify_claim($claim) {
        $api_key = $this->options['firecrawl_api_key'];
        $search_query = $claim['search_query'];
        $searches_per_claim = intval($this->options['firecrawl_searches_per_claim']);
        
        // Search and scrape with Firecrawl
        $response = wp_remote_post('https://api.firecrawl.dev/v2/search', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'query' => $search_query,
                'limit' => $searches_per_claim,
                'scrapeOptions' => array(
                    'formats' => array('markdown')
                ),
                'tbs' => 'qdr:m' // Past month for recent info
            )),
            'timeout' => 45
        ));
        
        if (is_wp_error($response)) {
            return array(
                'claim' => $claim['claim'],
                'status' => 'error',
                'message' => 'Search failed',
                'sources' => array()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $sources = array();
        $scraped_content = '';
        
        if (isset($data['success']) && $data['success'] && isset($data['data'])) {
            // Handle both 'web' and direct array responses
            $results = isset($data['data']['web']) ? $data['data']['web'] : (isset($data['data']) ? $data['data'] : array());
            
            foreach ($results as $result) {
                $sources[] = array(
                    'title' => isset($result['title']) ? $result['title'] : 'Source',
                    'url' => isset($result['url']) ? $result['url'] : '',
                    'credibility' => 'medium'
                );
                
                // Collect scraped content for verification
                if (isset($result['markdown'])) {
                    $scraped_content .= substr($result['markdown'], 0, 800) . "\n\n";
                }
            }
        }
        
        // Verify claim against scraped content
        $verification = $this->verify_against_sources($claim['claim'], $scraped_content);
        
        return array(
            'claim' => $claim['claim'],
            'importance' => $claim['importance'],
            'verification' => $verification,
            'sources' => $sources
        );
    }
    
    /**
     * Verify claim against scraped source content
     */
    private function verify_against_sources($claim, $scraped_content) {
        $api_key = $this->options['api_key'];
        $model = $this->options['model'];
        
        $prompt = "Verify this claim against the provided source content:

CLAIM: {$claim}

SOURCE CONTENT:
{$scraped_content}

Analyze and return ONLY valid JSON (no markdown):
{
    \"is_accurate\": true | false,
    \"confidence\": \"high\" | \"medium\" | \"low\",
    \"explanation\": \"brief explanation of verification\",
    \"correction\": \"correct information if claim is false, otherwise empty string\"
}";

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
                'max_tokens' => 500,
                'temperature' => 0.1
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'is_accurate' => false,
                'confidence' => 'low',
                'explanation' => 'Verification failed',
                'correction' => ''
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return array(
                'is_accurate' => false,
                'confidence' => 'low',
                'explanation' => 'Verification failed',
                'correction' => ''
            );
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        $ai_content = preg_replace('/^```json\s*/', '', $ai_content);
        $ai_content = preg_replace('/\s*```$/', '', $ai_content);
        $result = json_decode($ai_content, true);
        
        return is_array($result) ? $result : array(
            'is_accurate' => false,
            'confidence' => 'low',
            'explanation' => 'Verification failed',
            'correction' => ''
        );
    }
    
    /**
     * Generate final report from verification results
     */
    private function generate_report($content, $verification_results, $all_sources) {
        $api_key = $this->options['api_key'];
        $model = $this->options['model'];
        $current_date = current_time('F j, Y');
        
        $verification_summary = json_encode($verification_results);
        
        $prompt = "Based on the verification results below, generate a comprehensive fact-check report:

ORIGINAL ARTICLE EXCERPT:
{$content}

VERIFICATION RESULTS:
{$verification_summary}

Today's Date: {$current_date}

Generate a detailed report in ONLY valid JSON (no markdown):
{
    \"score\": 0-100,
    \"status\": \"Accurate\" | \"Mostly Accurate\" | \"Partially Accurate\" | \"Mostly Inaccurate\" | \"False\",
    \"description\": \"2-3 sentence summary\",
    \"detailed_analysis\": {
        \"claims_identified\": number,
        \"claims_verified\": number,
        \"claims_false\": number,
        \"overall_assessment\": \"detailed paragraph\"
    },
    \"issues\": [
        {
            \"claim\": \"the claim\",
            \"type\": \"Factual Error\" | \"Outdated Information\" | \"Misleading\" | \"Unverified\",
            \"severity\": \"high\" | \"medium\" | \"low\",
            \"description\": \"what's wrong\",
            \"correct_information\": \"correct info\",
            \"suggestion\": \"how to fix\"
        }
    ],
    \"verified_claims\": [
        {
            \"claim\": \"accurate claim\",
            \"verification\": \"why it's correct\"
        }
    ]
}";

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
                'max_tokens' => 3000,
                'temperature' => 0.2
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to generate report: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid AI response when generating report');
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        $ai_content = preg_replace('/^```json\s*/', '', $ai_content);
        $ai_content = preg_replace('/\s*```$/', '', $ai_content);
        $result = json_decode($ai_content, true);
        
        if (!is_array($result)) {
            throw new Exception('Failed to parse final report');
        }
        
        // Add sources from Firecrawl searches
        $result['sources'] = array_slice($all_sources, 0, 8);
        
        // Add metadata
        $result['mode'] = 'firecrawl';
        $result['searches_performed'] = count($verification_results) * intval($this->options['firecrawl_searches_per_claim']);
        
        return $result;
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
        ), 300); // 5 minutes
    }
}
