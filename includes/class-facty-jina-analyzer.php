<?php
/**
 * Facty Jina DeepSearch Analyzer
 * FIXED: Now uses correct Jina DeepSearch API for ultra-fast fact-checking
 * API Docs: https://jina.ai/deepsearch/
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
     * Fast analysis using Jina DeepSearch
     */
    public function analyze($content, $task_id = null) {
        $jina_api_key = $this->options['jina_api_key'];
        
        if (empty($jina_api_key)) {
            throw new Exception('Jina API key not configured');
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 20, 'analyzing', 'Starting Jina DeepSearch analysis...');
        }
        
        $current_date = current_time('F j, Y');
        
        // Extract key claims from the article
        $claims = $this->extract_claims($content);
        
        if (empty($claims)) {
            throw new Exception('No factual claims found in article');
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 30, 'analyzing', 'Extracted ' . count($claims) . ' claims for verification...');
        }
        
        // Verify all claims using Jina DeepSearch
        $results = array();
        $total_claims = count($claims);
        
        foreach ($claims as $index => $claim) {
            if ($task_id) {
                $percent = 30 + (($index + 1) / $total_claims * 60);
                $this->update_progress($task_id, $percent, 'verifying', 'Verifying claim ' . ($index + 1) . '/' . $total_claims . '...');
            }
            
            $result = $this->verify_claim($claim, $jina_api_key, $current_date);
            $results[] = $result;
            
            // Small delay to avoid rate limits
            usleep(200000); // 0.2 seconds
        }
        
        if ($task_id) {
            $this->update_progress($task_id, 95, 'generating', 'Compiling final report...');
        }
        
        // Compile results into final report
        return $this->compile_results($results, $total_claims, $content);
    }
    
    /**
     * Extract factual claims from content
     */
    private function extract_claims($content) {
        // Split into sentences
        $sentences = preg_split('/[.!?]+/', $content);
        $claims = array();
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            
            // Skip very short or very long sentences
            if (strlen($sentence) < 20 || strlen($sentence) > 250) continue;
            
            // Skip questions
            if (strpos($sentence, '?') !== false) continue;
            
            // Skip obvious opinions
            if (preg_match('/\b(I think|believe|feel|opinion|seems|might|maybe|should|could|would)\b/i', $sentence)) {
                continue;
            }
            
            // Include sentences with factual indicators
            if (preg_match('/\b(is|are|was|were|has|have|had|will|according to|reported|said|announced|confirmed|data shows|research|study|statistics|percent|million|billion|year|date)\b/i', $sentence)) {
                $claims[] = $sentence;
            }
        }
        
        // Limit to 10 claims for efficiency
        return array_slice($claims, 0, 10);
    }
    
    /**
     * Verify a claim using Jina DeepSearch API
     */
    private function verify_claim($claim, $api_key, $current_date) {
        // Define response schema for structured output
        $schema = array(
            'type' => 'object',
            'properties' => array(
                'statement' => array(
                    'type' => 'string',
                    'description' => 'The statement being checked'
                ),
                'validity' => array(
                    'type' => 'string',
                    'enum' => array('true', 'false', 'partially_true', 'unverifiable'),
                    'description' => 'Whether the statement is valid'
                ),
                'confidence' => array(
                    'type' => 'string',
                    'enum' => array('high', 'medium', 'low'),
                    'description' => 'Confidence level in the assessment'
                ),
                'explanation' => array(
                    'type' => 'string',
                    'description' => 'Brief explanation of the validity assessment'
                ),
                'sources' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'title' => array('type' => 'string'),
                            'url' => array('type' => 'string')
                        )
                    ),
                    'description' => 'Sources used to verify the claim'
                )
            ),
            'required' => array('statement', 'validity', 'confidence', 'explanation')
        );
        
        $request_body = array(
            'model' => 'jina-deepsearch-v1',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => "Today is {$current_date}. Fact-check this statement using reliable web sources: \"{$claim}\"\n\nReturn a structured assessment with validity (true/false/partially_true/unverifiable), confidence level, explanation, and sources."
                )
            ),
            'response_format' => array(
                'type' => 'json_schema',
                'json_schema' => $schema
            ),
            'reasoning_effort' => 'low',
            'max_returned_urls' => 3
        );
        
        $response = wp_remote_post('https://deepsearch.jina.ai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            error_log('Jina API Error: ' . $response->get_error_message());
            return array(
                'claim' => $claim,
                'validity' => 'unverifiable',
                'confidence' => 'low',
                'explanation' => 'API request failed: ' . $response->get_error_message(),
                'sources' => array()
            );
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 200) {
            error_log('Jina API HTTP Error ' . $http_code . ': ' . $body);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'API request failed';
            
            return array(
                'claim' => $claim,
                'validity' => 'unverifiable',
                'confidence' => 'low',
                'explanation' => 'API error: ' . $error_message,
                'sources' => array()
            );
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            error_log('Jina API Invalid Response: ' . $body);
            return array(
                'claim' => $claim,
                'validity' => 'unverifiable',
                'confidence' => 'low',
                'explanation' => 'Invalid API response format',
                'sources' => array()
            );
        }
        
        // Parse the structured response
        $content = $data['choices'][0]['message']['content'];
        $result = json_decode($content, true);
        
        if (!$result || !is_array($result)) {
            error_log('Jina Response Parse Error: ' . $content);
            return array(
                'claim' => $claim,
                'validity' => 'unverifiable',
                'confidence' => 'low',
                'explanation' => 'Could not parse fact-check response',
                'sources' => array()
            );
        }
        
        // Ensure required fields
        $result = array_merge(array(
            'claim' => $claim,
            'validity' => 'unverifiable',
            'confidence' => 'low',
            'explanation' => 'No explanation provided',
            'sources' => array()
        ), $result);
        
        return $result;
    }
    
    /**
     * Compile all results into final report
     */
    private function compile_results($results, $total_claims, $original_content) {
        $verified_count = 0;
        $false_count = 0;
        $partially_true_count = 0;
        $unverifiable_count = 0;
        
        $issues = array();
        $verified_facts = array();
        $all_sources = array();
        
        foreach ($results as $result) {
            $validity = isset($result['validity']) ? $result['validity'] : 'unverifiable';
            $confidence = isset($result['confidence']) ? $result['confidence'] : 'low';
            
            // Count by validity
            switch ($validity) {
                case 'true':
                    $verified_count++;
                    $verified_facts[] = array(
                        'claim' => $result['claim'],
                        'confidence' => $confidence
                    );
                    break;
                case 'false':
                    $false_count++;
                    $issues[] = array(
                        'claim' => $result['claim'],
                        'type' => 'Factual Error',
                        'severity' => 'high',
                        'what_article_says' => $result['claim'],
                        'the_problem' => 'This claim is false',
                        'actual_facts' => isset($result['explanation']) ? $result['explanation'] : 'Verified as incorrect',
                        'why_it_matters' => 'False information misleads readers and damages credibility'
                    );
                    break;
                case 'partially_true':
                    $partially_true_count++;
                    $issues[] = array(
                        'claim' => $result['claim'],
                        'type' => 'Misleading',
                        'severity' => 'medium',
                        'what_article_says' => $result['claim'],
                        'the_problem' => 'This claim is only partially true',
                        'actual_facts' => isset($result['explanation']) ? $result['explanation'] : 'Requires additional context',
                        'why_it_matters' => 'Partial truths can be misleading without proper context'
                    );
                    break;
                default: // unverifiable
                    $unverifiable_count++;
                    if ($confidence !== 'high') { // Only report low-confidence unverifiable claims
                        $issues[] = array(
                            'claim' => $result['claim'],
                            'type' => 'Unverified',
                            'severity' => 'low',
                            'what_article_says' => $result['claim'],
                            'the_problem' => 'This claim could not be verified',
                            'actual_facts' => isset($result['explanation']) ? $result['explanation'] : 'No reliable sources found',
                            'why_it_matters' => 'Unverified claims should be approached with caution'
                        );
                    }
                    break;
            }
            
            // Collect sources
            if (!empty($result['sources'])) {
                foreach ($result['sources'] as $source) {
                    if (isset($source['url']) && !empty($source['url'])) {
                        $all_sources[] = array(
                            'title' => isset($source['title']) ? $source['title'] : 'Source',
                            'url' => $source['url'],
                            'credibility' => 'high'
                        );
                    }
                }
            }
        }
        
        // Calculate overall score
        $score = 100;
        if ($total_claims > 0) {
            // Weight: true = 100%, partially_true = 60%, unverifiable = 40%, false = 0%
            $weighted_score = (
                ($verified_count * 100) +
                ($partially_true_count * 60) +
                ($unverifiable_count * 40) +
                ($false_count * 0)
            ) / $total_claims;
            
            $score = round($weighted_score);
        }
        
        // Determine status
        $status = 'Verified';
        if ($score >= 95) {
            $status = 'Verified';
        } elseif ($score >= 85) {
            $status = 'Mostly Accurate';
        } elseif ($score >= 70) {
            $status = 'Needs Review';
        } elseif ($score >= 50) {
            $status = 'Mixed Accuracy';
        } else {
            if ($false_count > $total_claims / 2) {
                $status = 'False';
            } else {
                $status = 'Multiple Errors';
            }
        }
        
        // Check if satirical
        if (preg_match('/\b(satire|satirical|parody|joke|humor|humorous|comedy)\b/i', $original_content)) {
            $score = 100;
            $status = 'Satire';
            $issues = array();
        }
        
        $description = "Analyzed {$total_claims} claims using Jina DeepSearch. {$verified_count} verified";
        if ($false_count > 0) $description .= ", {$false_count} false";
        if ($partially_true_count > 0) $description .= ", {$partially_true_count} partially true";
        if ($unverifiable_count > 0) $description .= ", {$unverifiable_count} unverifiable";
        $description .= ".";
        
        return array(
            'score' => $score,
            'status' => $status,
            'description' => $description,
            'issues' => $issues,
            'verified_facts' => $verified_facts,
            'sources' => array_slice(array_unique($all_sources, SORT_REGULAR), 0, 15),
            'mode' => 'jina-deepsearch'
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