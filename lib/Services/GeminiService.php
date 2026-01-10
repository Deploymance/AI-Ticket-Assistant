<?php

namespace WHMCS\Module\Addon\AITicketAssistant\Services;

use Exception;
use WHMCS\Support\Ticket;

/**
 * Gemini AI Service for Ticket Response Generation
 */
class GeminiService
{
    private string $apiKey;
    private string $apiEndpoint;
    private array $settings;

    public function __construct(array $addonConfig = [])
    {
        // Get API key from addon configuration
        $this->apiKey = $addonConfig['gemini_api_key'] ?? '';
        
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API Key is not configured. Please configure it in the addon settings.');
        }

        // Get model from configuration (default to gemini-2.5-flash)
        $model = $addonConfig['gemini_model'] ?? 'gemini-2.5-flash';
        $this->apiEndpoint = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent';

        // Load settings
        $this->settings = [
            'max_output_tokens' => (int) ($addonConfig['max_output_tokens'] ?? 4096),
            'response_language' => $addonConfig['response_language'] ?? 'auto',
            'model' => $model,
        ];
    }

    /**
     * Generate an AI reply for a ticket
     */
    public function generateReply(
        int $ticketId,
        string $adminInstructions = '',
        string $extraContext = '',
        string $tone = 'professional'
    ): array {
        logActivity('[AI Ticket Assistant] Starting reply generation for ticket #' . $ticketId . ' using model: ' . $this->settings['model']);

        $ticket = Ticket::with(['replies', 'client'])->findOrFail($ticketId);

        // Build conversation context
        $context = $this->buildReplyContext($ticket);
        $context['admin_instructions'] = $adminInstructions;
        $context['extra_context'] = $extraContext;
        $context['desired_tone'] = $tone;
        $context['language'] = $this->settings['response_language'];

        // Generate reply using Gemini
        $prompt = $this->buildReplyPrompt($context);

        logActivity('[AI Ticket Assistant] Calling Gemini API (' . $this->settings['model'] . ') for ticket #' . $ticketId);
        $response = $this->callGeminiApi($prompt);

        $reply = $this->parseReplyResponse($response);

        logActivity('[AI Ticket Assistant] Reply generated successfully for ticket #' . $ticketId . ' (length: ' . strlen($reply['message']) . ' chars)');

        return [
            'reply' => $reply['message'],
            'tone' => $tone,
        ];
    }

    /**
     * Build context for reply generation
     */
    private function buildReplyContext(Ticket $ticket): array
    {
        $context = [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'department' => $ticket->deptname ?? 'General',
            'client_name' => $ticket->client ? $ticket->client->fullName : 'Unknown',
            'messages' => [],
        ];

        // Add initial message
        $context['messages'][] = [
            'from' => 'client',
            'message' => $ticket->message,
            'date' => $ticket->date,
        ];

        // Add all replies
        foreach ($ticket->replies as $reply) {
            $context['messages'][] = [
                'from' => $reply->admin ? 'staff' : 'client',
                'author' => $reply->admin ?: ($ticket->client ? $ticket->client->fullName : 'Customer'),
                'message' => $reply->message,
                'date' => $reply->date,
            ];
        }

        return $context;
    }

    /**
     * Build prompt for reply generation
     */
    private function buildReplyPrompt(array $context): string
    {
        $conversation = "Ticket #" . $context['ticket_id'] . "\n";
        $conversation .= "Subject: " . $context['subject'] . "\n";
        $conversation .= "Department: " . $context['department'] . "\n";
        $conversation .= "Priority: " . $context['priority'] . "\n\n";
        $conversation .= "Conversation:\n";
        $conversation .= str_repeat('-', 50) . "\n\n";

        foreach ($context['messages'] as $msg) {
            $from = $msg['from'] === 'client' ? "Customer" : "Support Staff";
            if (isset($msg['author'])) {
                $from .= " (" . $msg['author'] . ")";
            }
            $conversation .= "[{$from}] - {$msg['date']}\n";
            $conversation .= strip_tags($msg['message']) . "\n\n";
        }

        // Build tone guidance
        $toneGuidance = match($context['desired_tone']) {
            'friendly' => "Use a warm, friendly, and casual tone while remaining professional.",
            'empathetic' => "Be highly empathetic and understanding, acknowledging the customer's feelings and concerns.",
            'apologetic' => "Express sincere apologies for any inconvenience and focus on making things right.",
            'technical' => "Use a technical, detailed tone with specific technical information and explanations.",
            default => "Maintain a professional, courteous tone.",
        };

        // Language instruction
        $languageInstruction = '';
        if ($context['language'] !== 'auto') {
            $languageMap = [
                'en' => 'English',
                'es' => 'Spanish (Español)',
                'fr' => 'French (Français)',
                'de' => 'German (Deutsch)',
                'it' => 'Italian (Italiano)',
                'pt' => 'Portuguese (Português)',
                'nl' => 'Dutch (Nederlands)',
                'pl' => 'Polish (Polski)',
                'ru' => 'Russian (Русский)',
                'ja' => 'Japanese (日本語)',
                'zh' => 'Chinese Simplified (简体中文)',
                'ko' => 'Korean (한국어)',
                'ar' => 'Arabic (العربية)',
                'hi' => 'Hindi (हिन्दी)',
                'tr' => 'Turkish (Türkçe)',
                'sv' => 'Swedish (Svenska)',
                'no' => 'Norwegian (Norsk)',
                'da' => 'Danish (Dansk)',
                'fi' => 'Finnish (Suomi)',
            ];
            
            $languageName = $languageMap[$context['language']] ?? 'English';
            $languageInstruction = "\n- CRITICAL: Respond ONLY in {$languageName}. The entire response must be written in {$languageName}.";
        }

        // Build the prompt
        $prompt = "You are a professional customer support representative for a hosting company. "
            . "Generate a helpful reply to the following support ticket.\n\n";

        // Add admin instructions prominently
        if (!empty($context['admin_instructions'])) {
            $prompt .= "ADMIN INSTRUCTIONS (PRIORITY):\n"
                . $context['admin_instructions'] . "\n\n";
        }

        // Add extra context if provided
        if (!empty($context['extra_context'])) {
            $prompt .= "ADDITIONAL CONTEXT:\n"
                . $context['extra_context'] . "\n\n";
        }

        $prompt .= "GUIDELINES:\n"
            . "- {$toneGuidance}\n"
            . "- Follow the admin instructions above as your primary directive\n"
            . "- Address the customer's issue directly\n"
            . "- Provide clear, actionable steps when applicable\n"
            . "- Use proper markdown formatting for better readability\n"
            . "- If technical information is needed that you don't have, acknowledge this and offer to investigate\n"
            . "- Do NOT include a signature, name, closing salutation, or sign-off at the end\n"
            . "- Do NOT include phrases like 'Sincerely', 'Best regards', '[Your Name]', 'Customer Support', etc.\n"
            . "- End the message naturally after addressing the customer's issue\n"
            . "- Focus on solving the customer's problem\n"
            . "- Use blank lines to separate paragraphs for better readability\n"
            . "- Keep paragraphs concise and well-structured\n"
            . "- Generate a COMPLETE response, do not truncate or cut off mid-sentence"
            . $languageInstruction . "\n\n"
            . $conversation . "\n\n"
            . "Generate the complete reply message following the admin instructions. "
            . "IMPORTANT: Do NOT include a subject line, 'Re:', or any email headers. Start directly with the message body. "
            . "Do not include labels, explanations, or meta-commentary. "
            . "Use proper paragraph breaks (blank lines) between sections. "
            . "Ensure the response is complete and not cut off.";

        return $prompt;
    }

    /**
     * Call Gemini API
     */
    private function callGeminiApi(string $prompt): array
    {
        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => $this->settings['max_output_tokens'],
                'topK' => 40,
                'topP' => 0.95,
            ],
        ];

        logActivity('[AI Ticket Assistant] Sending request to Gemini API (maxTokens: ' . $this->settings['max_output_tokens'] . ')');

        $ch = curl_init($this->apiEndpoint . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        logActivity('[AI Ticket Assistant] Response received. HTTP Code: ' . $httpCode . ', Response size: ' . strlen($response) . ' bytes');

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            logActivity('[AI Ticket Assistant] API Error: ' . substr($response, 0, 500));
            throw new Exception('Gemini API returned HTTP ' . $httpCode . ': ' . substr($response, 0, 200));
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode Gemini API response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Parse reply generation response
     */
    private function parseReplyResponse(array $response): array
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            logActivity('[AI Ticket Assistant] Invalid response structure: ' . json_encode($response));
            throw new Exception('Invalid response structure from Gemini API');
        }

        $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        logActivity('[AI Ticket Assistant] Finish reason: ' . $finishReason);

        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        $originalLength = strlen($text);

        // Clean control characters BUT preserve newlines
        $cleanText = preg_replace('/[\x00-\x09\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        $cleanText = preg_replace('/\r\n|\r/', "\n", $cleanText);
        $cleanText = preg_replace('/\n{3,}/', "\n\n", $cleanText);
        
        // Remove "Re: [subject]" or "Subject: ..." lines from the beginning
        $cleanText = preg_replace('/^(Re:|Subject:)[^\n]+\n*/i', '', $cleanText);
        
        // Remove common signature patterns and instructions at the start
        $cleanText = preg_replace('/^(Skip generating responder name\.?\s*\n*)/i', '', $cleanText);
        
        // Remove signature blocks at the end (Sincerely, Best regards, etc.)
        $cleanText = preg_replace('/\n+(Sincerely|Best regards|Regards|Thank you|Thanks|Cheers|Best|Kind regards|Warm regards),?\s*\n+(\[Your Name\]|Customer Support|Support Team|[A-Z][a-z]+ [A-Z][a-z]+|\[.*?\])?\s*$/i', '', $cleanText);
        
        // Remove standalone signature lines at the end
        $cleanText = preg_replace('/\n+(\[Your Name\]|Customer Support|Support Team|The Support Team)\s*$/i', '', $cleanText);
        
        $cleanText = trim($cleanText);

        logActivity('[AI Ticket Assistant] Cleaned response length: ' . strlen($cleanText) . ' chars (original: ' . $originalLength . ')');

        // Add truncation warning if needed
        if ($finishReason === 'MAX_TOKENS') {
            $cleanText .= "\n\n[Note: Response may have been truncated. Please review and complete if needed.]";
            logActivity('[AI Ticket Assistant] Response truncated due to MAX_TOKENS');
        }

        return [
            'message' => $cleanText,
        ];
    }
}
