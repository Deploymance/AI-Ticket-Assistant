<?php

namespace WHMCS\Module\Addon\AITicketAssistant\Admin\Controllers;

use Exception;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\AITicketAssistant\Services\GeminiService;

/**
 * Admin Area Controller
 */
class AdminController
{
    /**
     * Display the main admin page
     */
    public function index($vars): void
    {
        $moduleLink = $vars['modulelink'];
        $version = $vars['version'];
        $apiKey = $vars['gemini_api_key'] ?? '';
        $geminiModel = $vars['gemini_model'] ?? 'gemini-2.0-flash-exp';
        $maxInstructions = $vars['max_instructions_chars'] ?? '1000';
        $maxContext = $vars['max_context_chars'] ?? '1000';
        $language = $vars['response_language'] ?? 'auto';
        $maxTokens = $vars['max_output_tokens'] ?? '4096';
        $enableQuick = $vars['enable_quick_reply'] ?? 'on';
        $enableContext = $vars['enable_context_reply'] ?? 'on';

        // Model display names
        $modelNames = [
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
        ];

        echo '<div class="container-fluid">';
        echo '<h2>AI Ticket Assistant</h2>';
        
        // Status panel
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading"><h3 class="panel-title">Status & Configuration</h3></div>';
        echo '<div class="panel-body">';
        
        if (empty($apiKey)) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fa fa-exclamation-triangle"></i> <strong>API Key Not Configured</strong><br>';
            echo 'Please configure your Gemini API key in the addon configuration to enable AI features.';
            echo '</div>';
        } else {
            echo '<div class="alert alert-success">';
            echo '<i class="fa fa-check-circle"></i> <strong>Module Active</strong><br>';
            echo 'AI Ticket Assistant is configured and ready to use.';
            echo '</div>';
        }
        
        echo '<table class="table table-striped">';
        echo '<tr><th width="30%">Setting</th><th>Value</th></tr>';
        echo '<tr><td>API Key Status</td><td>' . (!empty($apiKey) ? '<span class="label label-success">Configured</span>' : '<span class="label label-danger">Not Configured</span>') . '</td></tr>';
        echo '<tr><td>Gemini Model</td><td><strong>' . htmlspecialchars($modelNames[$geminiModel] ?? $geminiModel) . '</strong></td></tr>';
        echo '<tr><td>Max Instructions Characters</td><td>' . htmlspecialchars($maxInstructions) . '</td></tr>';
        echo '<tr><td>Max Context Characters</td><td>' . htmlspecialchars($maxContext) . '</td></tr>';
        echo '<tr><td>Response Language</td><td>' . htmlspecialchars($language) . '</td></tr>';
        echo '<tr><td>Max Output Tokens</td><td>' . htmlspecialchars($maxTokens) . '</td></tr>';
        echo '<tr><td>Quick Reply Button</td><td>' . ($enableQuick === 'on' ? '<span class="label label-success">Enabled</span>' : '<span class="label label-default">Disabled</span>') . '</td></tr>';
        echo '<tr><td>Context Reply Button</td><td>' . ($enableContext === 'on' ? '<span class="label label-success">Enabled</span>' : '<span class="label label-default">Disabled</span>') . '</td></tr>';
        echo '</table>';
        
        echo '</div>';
        echo '</div>';
        
        // How to use
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading"><h3 class="panel-title">How to Use</h3></div>';
        echo '<div class="panel-body">';
        echo '<h4>1. Configure the Module</h4>';
        echo '<p>Navigate to <strong>Setup → Addon Modules</strong>, find "AI Ticket Assistant", and configure the following:</p>';
        echo '<ul>';
        echo '<li><strong>Gemini API Key:</strong> Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>';
        echo '<li><strong>Gemini Model:</strong> Select the AI model (2.0 Flash Exp is recommended for best performance)</li>';
        echo '<li><strong>Max Characters:</strong> Set the maximum character limits for input fields</li>';
        echo '<li><strong>Response Language:</strong> Choose the language for AI-generated responses</li>';
        echo '<li><strong>Max Output Tokens:</strong> Control the length of generated responses (default: 4096)</li>';
        echo '</ul>';
        
        echo '<h4>2. Generate AI Replies</h4>';
        echo '<p>When viewing a support ticket in the admin area, you will see AI reply buttons above the reply textarea:</p>';
        echo '<ul>';
        echo '<li><strong>Quick Reply (🪄):</strong> Generates a standard professional response instantly</li>';
        echo '<li><strong>Reply with Context (🪄⚙️):</strong> Opens a dialog to provide specific instructions and context</li>';
        echo '</ul>';
        
        echo '<h4>3. Customize Responses</h4>';
        echo '<p>When using "Reply with Context", you can:</p>';
        echo '<ul>';
        echo '<li>Provide specific instructions (e.g., "Explain server setup", "Process refund request")</li>';
        echo '<li>Add additional context (e.g., "VIP customer", "Related to ticket #1234")</li>';
        echo '<li>Select the desired tone (Professional, Friendly, Empathetic, Apologetic, Technical)</li>';
        echo '</ul>';
        
        echo '</div>';
        echo '</div>';
        
        // API Information
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading"><h3 class="panel-title">API Information</h3></div>';
        echo '<div class="panel-body">';
        echo '<p><strong>Powered by:</strong> Google Gemini API</p>';
        echo '<p><strong>Current Model:</strong> ' . htmlspecialchars($modelNames[$geminiModel] ?? $geminiModel) . '</p>';
        echo '<p><strong>Available Models:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Gemini 2.0 Flash (Experimental)</strong> - Latest model, fastest, most efficient (Recommended)</li>';
        echo '<li><strong>Gemini 1.5 Flash</strong> - Production stable, balanced performance</li>';
        echo '<li><strong>Gemini 1.5 Flash 8B</strong> - Lightweight, faster for simple tasks</li>';
        echo '<li><strong>Gemini 1.5 Pro</strong> - Most capable, best for complex reasoning</li>';
        echo '</ul>';
        echo '<p><strong>Features:</strong></p>';
        echo '<ul>';
        echo '<li>Advanced language understanding and generation</li>';
        echo '<li>Context-aware responses</li>';
        echo '<li>Multi-language support</li>';
        echo '<li>Fast response times</li>';
        echo '</ul>';
        echo '<p><strong>Documentation:</strong> <a href="https://ai.google.dev/gemini-api/docs" target="_blank">Gemini API Documentation</a></p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Handle AJAX request for generating AI reply
     */
    public function generateReply(): void
    {
        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        try {
            $ticketId = (int) ($_POST['ticket_id'] ?? 0);
            $adminInstructions = trim($_POST['admin_instructions'] ?? '');
            $extraContext = trim($_POST['extra_context'] ?? '');
            $tone = trim($_POST['tone'] ?? 'professional');

            if ($ticketId <= 0) {
                throw new Exception('Invalid ticket ID');
            }

            // Default instruction if not provided (for quick reply)
            if (empty($adminInstructions)) {
                $adminInstructions = 'Provide a helpful and professional response to this ticket.';
            }

            logActivity('[AI Ticket Assistant] Generating reply for ticket #' . $ticketId);

            // Get addon configuration from database
            $addonConfig = Capsule::table('tbladdonmodules')
                ->where('module', 'ai_ticket_assistant')
                ->pluck('value', 'setting')
                ->toArray();

            if (empty($addonConfig)) {
                throw new Exception('Addon configuration not found');
            }

            $geminiService = new GeminiService($addonConfig);
            $result = $geminiService->generateReply($ticketId, $adminInstructions, $extraContext, $tone);

            logActivity('[AI Ticket Assistant] Reply generated successfully (length: ' . strlen($result['reply']) . ' chars)');

            echo json_encode([
                'success' => true,
                'reply' => $result['reply'],
                'tone' => $result['tone'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            logActivity('[AI Ticket Assistant] Error: ' . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate AI reply: ' . $e->getMessage(),
            ]);
        }

        exit;
    }
}
