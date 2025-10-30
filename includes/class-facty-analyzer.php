<?php
/**
 * Facty OpenRouter Analyzer
 * Handles fact-checking using OpenRouter API with web search
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
            $this->update_progress($task_id, 20, 'analyzing', 'Analyzing article with AI...');
        }
        
        $current_date = current_time('F j, Y');
        $website_name = get_bloginfo('name');
        $website_url = home_url();
        
        // Comprehensive fact-checking prompt
        $prompt = "You are a professional fact-checker working for {$website_name}.

CONTEXT INFORMATION:
- Today's Date: {$current_date}
- Website: {$website_name} ({$website_url})
- Your Role: Verify factual accuracy of published content

═══════════════════════════════════════════════════════════════
CRITICAL STEP 1: CONTENT TYPE DETECTION
═══════════════════════════════════════════════════════════════

BEFORE analyzing, determine the content type:

🎭 SATIRE/PARODY/COMEDY Indicators:
   - Absurd or impossible scenarios
   - Obvious exaggeration for humor
   - Fake advice columns or letters
   - Impersonating public figures in humorous way
   - \"The Onion\"-style writing
   - Clearly joke headlines or premises
   
📰 NEWS/FACTUAL Indicators:
   - Reporting real events
   - Citing real sources
   - Attempting serious journalism
   - No obvious humor or satire markers

💭 OPINION/EDITORIAL Indicators:
   - Personal viewpoint pieces
   - Clearly marked as opinion
   - Subjective analysis

IF SATIRE/COMEDY DETECTED:
Return immediately with:
{
    \"content_type\": \"satire\",
    \"score\": 100,
    \"status\": \"Satire/Parody - Not Subject to Fact-Checking\",
    \"description\": \"This is satirical or comedic content, not factual reporting.\",
    \"issues\": [],
    \"sources\": []
}

IF OPINION/EDITORIAL DETECTED:
STILL fact-check the factual claims used within the opinion piece. Opinions should be verified for accuracy of supporting facts.

═══════════════════════════════════════════════════════════════
STEP 2: DETAILED FACT-CHECKING (For News/Factual/Opinion Content)
═══════════════════════════════════════════════════════════════

Your Analysis Process:
1. IDENTIFY individual factual claims (not opinions)
2. VERIFY each claim against current information
3. EVALUATE accuracy with specific examples
4. PROVIDE actionable corrections

ARTICLE CONTENT TO ANALYZE:
{$content}

═══════════════════════════════════════════════════════════════
REQUIRED OUTPUT FORMAT
═══════════════════════════════════════════════════════════════

Return ONLY valid JSON (no markdown, no code blocks):

{
    \"content_type\": \"news\" | \"opinion\" | \"satire\",
    \"score\": 0-100,
    \"status\": \"Accurate\" | \"Mostly Accurate\" | \"Partially Accurate\" | \"Mostly Inaccurate\" | \"False\" | \"Satire - Not Subject to Fact-Checking\",
    \"description\": \"2-3 sentence summary of findings\",
    
    \"detailed_analysis\": {
        \"claims_identified\": 5,
        \"claims_verified\": 4,
        \"claims_false\": 1,
        \"overall_assessment\": \"Detailed paragraph explaining your analysis\"
    },
    
    \"issues\": [
        {
            \"claim\": \"The specific claim from the article\",
            \"type\": \"Factual Error\" | \"Outdated Information\" | \"Misleading\" | \"Unverified\" | \"Missing Context\",
            \"severity\": \"high\" | \"medium\" | \"low\",
            \"description\": \"Detailed explanation of what's wrong\",
            \"correct_information\": \"What the correct information should be\",
            \"suggestion\": \"How to fix this\"
        }
    ],
    
    \"verified_claims\": [
        {
            \"claim\": \"Claim that checked out as accurate\",
            \"verification\": \"Why this is correct\"
        }
    ],
    
    \"sources\": [
        {
            \"title\": \"Actual source title\",
            \"url\": \"https://actual-url.com\",
            \"credibility\": \"high\" | \"medium\" | \"low\",
            \"relevance\": \"What this source verifies\"
        }
    ],
    
    \"context_notes\": [
        \"Important context point 1\",
        \"Important context point 2\"
    ]
}

SCORING GUIDELINES:
90-100: Highly accurate, no significant errors
75-89: Mostly accurate, minor issues
60-74: Partially accurate, some significant issues
40-59: Multiple factual errors
0-39: Mostly or entirely false

Remember: You're checking facts as of {$current_date}. Information changes over time.";
        
        if ($task_id) {
            $this->update_progress($task_id, 60, 'verifying', 'Searching and verifying facts...');
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
                'max_tokens' => 4000,
                'temperature' => 0.2
            )),
            'timeout' => 120
        ));
        
        if ($task_id) {
            $this->update_progress($task_id, 90, 'generating', 'Formatting final report...');
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
        ), 300); // 5 minutes
    }
}
