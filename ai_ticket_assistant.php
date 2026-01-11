<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

// Load addon hooks
require_once __DIR__ . '/hooks.php';

/**
 * AI Ticket Assistant Module Configuration
 */
function ai_ticket_assistant_config(): array
{
    return [
        'name' => 'AI Ticket Assistant',
        'description' => 'AI-powered ticket response generation using Google Gemini API with customizable settings.',
        'author' => 'Deploymance',
        'language' => 'english',
        'version' => '1.1.0',
        'fields' => [
            'license_key' => [
                'FriendlyName' => 'License Key',
                'Type' => 'password',
                'Size' => '50',
                'Description' => 'Enter your Deploymance license key. Get one at <a href="https://deploymance.com/addons" target="_blank">deploymance.com</a>',
                'Required' => true,
            ],
            'api_url_override' => [
                'FriendlyName' => 'API URL Override (Dev Only)',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Leave empty for production. For testing, enter your dev server URL (e.g., http://YOUR_IP:3001)',
                'Default' => '',
            ],
            'gemini_api_key' => [
                'FriendlyName' => 'Gemini API Key',
                'Type' => 'password',
                'Size' => '50',
                'Description' => 'Enter your Google Gemini API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>',
                'Required' => true,
            ],
            'gemini_model' => [
                'FriendlyName' => 'Gemini Model',
                'Type' => 'dropdown',
                'Options' => 'gemini-2.5-flash,gemini-2.5-pro,gemini-2.0-flash,gemini-2.5-flash-lite',
                'Description' => 'Select the Gemini model to use for generating responses<br>' .
                    '<small><em>Pricing as of January 2026</em><br><br>' .
                    '<strong>gemini-2.5-flash</strong> (Recommended): Balanced speed & intelligence - $0.30 in / $2.50 out per 1M tokens<br>' .
                    '<strong>gemini-2.5-pro</strong>: Most capable, advanced reasoning - $1.25 in / $10.00 out per 1M tokens<br>' .
                    '<strong>gemini-2.0-flash</strong>: Fast & stable - $0.10 in / $0.40 out per 1M tokens<br>' .
                    '<strong>gemini-2.5-flash-lite</strong>: Cost-efficient & fast - $0.10 in / $0.40 out per 1M tokens</small>',
                'Default' => 'gemini-2.5-flash',
            ],
            'max_instructions_chars' => [
                'FriendlyName' => 'Max Characters - Instructions Field',
                'Type' => 'text',
                'Size' => '10',
                'Description' => 'Maximum characters allowed in the instructions field (default: 1000)',
                'Default' => '1000',
            ],
            'max_context_chars' => [
                'FriendlyName' => 'Max Characters - Context Field',
                'Type' => 'text',
                'Size' => '10',
                'Description' => 'Maximum characters allowed in the additional context field (default: 1000)',
                'Default' => '1000',
            ],
            'response_language' => [
                'FriendlyName' => 'Response Language',
                'Type' => 'dropdown',
                'Options' => implode(',', [
                    'auto' => 'Auto-detect from ticket',
                    'en' => 'English',
                    'es' => 'Spanish',
                    'fr' => 'French',
                    'de' => 'German',
                    'it' => 'Italian',
                    'pt' => 'Portuguese',
                    'nl' => 'Dutch',
                    'pl' => 'Polish',
                    'ru' => 'Russian',
                    'ja' => 'Japanese',
                    'zh' => 'Chinese (Simplified)',
                    'ko' => 'Korean',
                    'ar' => 'Arabic',
                    'hi' => 'Hindi',
                    'tr' => 'Turkish',
                    'sv' => 'Swedish',
                    'no' => 'Norwegian',
                    'da' => 'Danish',
                    'fi' => 'Finnish',
                ]),
                'Description' => 'Language for AI-generated responses',
                'Default' => 'auto',
            ],
            'max_output_tokens' => [
                'FriendlyName' => 'Max Output Tokens',
                'Type' => 'text',
                'Size' => '10',
                'Description' => 'Maximum tokens for AI response (default: 4096, max: 8192)',
                'Default' => '4096',
            ],
            'enable_quick_reply' => [
                'FriendlyName' => 'Enable Quick Reply Button',
                'Type' => 'yesno',
                'Description' => 'Show the quick AI reply button (no context)',
                'Default' => 'yes',
            ],
            'enable_context_reply' => [
                'FriendlyName' => 'Enable Context Reply Button',
                'Type' => 'yesno',
                'Description' => 'Show the AI reply with context button',
                'Default' => 'yes',
            ],
        ],
    ];
}

/**
 * Activate Module
 */
function ai_ticket_assistant_activate(): array
{
    try {
        // Create settings table
        if (!Capsule::schema()->hasTable('mod_ai_ticket_assistant_settings')) {
            Capsule::schema()->create('mod_ai_ticket_assistant_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->timestamps();
            });
        }

        logActivity('[AI Ticket Assistant] Module activated successfully');

        return [
            'status' => 'success',
            'description' => 'AI Ticket Assistant module activated successfully.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error activating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate Module
 */
function ai_ticket_assistant_deactivate(): array
{
    try {
        logActivity('[AI Ticket Assistant] Module deactivated');

        return [
            'status' => 'success',
            'description' => 'AI Ticket Assistant module deactivated successfully.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error deactivating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Upgrade Module
 */
function ai_ticket_assistant_upgrade($vars): void
{
    $currentVersion = $vars['version'];

    // Add upgrade logic here as needed
    logActivity('[AI Ticket Assistant] Module upgraded to version ' . $currentVersion);
}

/**
 * Admin Area Output
 */
function ai_ticket_assistant_output($vars): void
{
    require_once __DIR__ . '/lib/Admin/Controllers/AdminController.php';
    
    $controller = new \WHMCS\Module\Addon\AITicketAssistant\Admin\Controllers\AdminController();
    $controller->index($vars);
}

/**
 * Admin Area Sidebar
 */
function ai_ticket_assistant_sidebar($vars): string
{
    $sidebar = '<div class="panel panel-default">';
    $sidebar .= '<div class="panel-heading"><h3 class="panel-title">AI Ticket Assistant</h3></div>';
    $sidebar .= '<div class="panel-body">';
    $sidebar .= '<p><strong>Version:</strong> 1.0.0</p>';
    $sidebar .= '<p><strong>Status:</strong> <span class="label label-success">Active</span></p>';
    $sidebar .= '<hr>';
    $sidebar .= '<h4>Quick Links</h4>';
    $sidebar .= '<ul>';
    $sidebar .= '<li><a href="https://ai.google.dev/gemini-api/docs" target="_blank">Gemini API Documentation</a></li>';
    $sidebar .= '<li><a href="https://aistudio.google.com/app/apikey" target="_blank">Get API Key</a></li>';
    $sidebar .= '</ul>';
    $sidebar .= '</div>';
    $sidebar .= '</div>';
    
    return $sidebar;
}
