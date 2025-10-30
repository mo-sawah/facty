<?php
/**
 * Facty Jina Grounding Analyzer
 * NEW: Faster fact-checking using Jina AI's Grounding API
 * Much faster than traditional web search methods
 */

if (!defined('ABSPATH')) {
    exit;
}

class Facty_Jina_Analyzer {
    
    private $options;
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Fast analysis using Jina Grounding
     */
    public function analyze($content, $task_id = null) {
        $jina_api_key = $this->options['jina_api_key']; // You'll need to add this to settings
        
        if (empty($jina_api_key)) {
            throw new Exception('Jina API key not configured');
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 20, 'analyzing', 'Fast fact-checking with Jina...');
        }
        
        $current_date = current_time('F j, Y');
        
        // Step 1: Generate factual claims using lightweight AI
        $claims = $this->extract_claims($content);
        
        if ($task_id) {
            $this->update_progress($task_id, 40, 'verifying', 'Grounding ' . count($claims) . ' claims...');
        }
        
        // Step 2: Ground each claim using Jina
        $results = array();
        $total_claims = count($claims);
        
        foreach ($claims as $index => $claim) {
            if ($task_id) {
                $percent = 40 + (($index + 1) / $total_claims * 50);
                $this->update_progress($task_id, $percent, 'verifying', 'Verifying claim ' . ($index + 1) . '/' . $total_claims);
            }
            
            $ground_result = $this->ground_claim($claim, $jina_api_key);
            $results[] = $ground_result;
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 95, 'generating', 'Generating report...');
        }
        
        // Step 3: Compile results
        return $this->compile_results($results, count($claims));
    }
    
    /**
     * Extract factual claims from content
     */
    private function extract_claims($content) {
        // Simple extraction: split into sentences and filter for factual claims
        $sentences = preg_split('/[.!?]+/', $content);
        $claims = array();
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 15) continue; // Skip very short
            if (strlen($sentence) > 200) continue; // Skip very long
            
            // Skip questions and opinions
            if (strpos($sentence, '?') !== false) continue;
            if (preg_match('/\b(I think|believe|feel|opinion|seems|might|maybe|should)\b/i', $sentence)) continue;
            
            // Include if it looks factual
            if (preg_match('/\b(is|are|was|were|has|have|will|according to|reported|said|announced)\b/i', $sentence)) {
                $claims[] = $sentence;
            }
        }
        
        // Limit to 8 claims for speed
        return array_slice($claims, 0, 8);
    }
    
    /**
     * Ground a single claim using Jina API
     */
    private function ground_claim($claim, $api_key) {
        $response = wp_remote_post('https://api.jina.ai/v1/grounding', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'text' => $claim,
                'return_related_facts' => true
            )),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'claim' => $claim,
                'verified' => false,
                'confidence' => 'low',
                'error' => $response->get_error_message()
            );
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            return array(
                'claim' => $claim,
                'verified' => false,
                'confidence' => 'low',
                'error' => 'API error: ' . $http_code
            );
        }
        
        $data = json_decode($body, true);
        
        if (!$data) {
            return array(
                'claim' => $claim,
                'verified' => false,
                'confidence' => 'low',
                'error' => 'Invalid response'
            );
        }
        
        // Parse Jina response
        $grounding_score = isset($data['score']) ? $data['score'] : 0.5;
        $factuality = isset($data['factuality']) ? $data['factuality'] : 'unknown';
        $sources = isset($data['sources']) ? $data['sources'] : array();
        
        return array(
            'claim' => $claim,
            'verified' => $grounding_score > 0.7,
            'confidence' => $grounding_score > 0.8 ? 'high' : ($grounding_score > 0.6 ? 'medium' : 'low'),
            'factuality' => $factuality,
            'score' => $grounding_score,
            'sources' => array_slice($sources, 0, 2)
        );
    }
    
    /**
     * Compile all results into final report
     */
    private function compile_results($results, $total_claims) {
        $verified_count = 0;
        $unverified_count = 0;
        $issues = array();
        $verified_facts = array();
        $all_sources = array();
        
        foreach ($results as $result) {
            if (isset($result['error'])) {
                continue; // Skip failed groundings
            }
            
            if ($result['verified']) {
                $verified_count++;
                $verified_facts[] = array(
                    'claim' => $result['claim'],
                    'confidence' => $result['confidence']
                );
            } else {
                $unverified_count++;
                
                $severity = 'medium';
                if ($result['confidence'] === 'low') {
                    $severity = 'high';
                }
                
                $issues[] = array(
                    'claim' => $result['claim'],
                    'type' => 'Unverified',
                    'severity' => $severity,
                    'what_article_says' => $result['claim'],
                    'the_problem' => 'This claim could not be verified against reliable sources.',
                    'actual_facts' => 'Unable to confirm or deny this claim with available sources.',
                    'why_it_matters' => 'Unverified claims may be inaccurate or misleading. Reader discretion advised.'
                );
            }
            
            if (!empty($result['sources'])) {
                foreach ($result['sources'] as $source) {
                    $all_sources[] = $source;
                }
            }
        }
        
        // Calculate score
        $score = 100;
        if ($total_claims > 0) {
            $score = round(($verified_count / $total_claims) * 100);
        }
        
        // Determine status
        $status = 'Verified';
        if ($score < 50) {
            $status = 'False';
        } elseif ($score < 70) {
            $status = 'Multiple Errors';
        } elseif ($score < 85) {
            $status = 'Needs Review';
        } elseif ($score < 95) {
            $status = 'Mostly Accurate';
        }
        
        $description = "Checked {$total_claims} factual claims. {$verified_count} verified, {$unverified_count} unverified.";
        
        return array(
            'score' => $score,
            'status' => $status,
            'description' => $description,
            'issues' => $issues,
            'verified_facts' => $verified_facts,
            'sources' => array_slice(array_unique($all_sources, SORT_REGULAR), 0, 10),
            'mode' => 'jina'
        );
    }
    
    /**
     * Update progress
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