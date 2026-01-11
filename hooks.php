<?php
/**
 * AI Ticket Assistant - Addon Hooks
 * 
 * This file is loaded by the main addon file
 * It injects the AI reply buttons into ticket view pages
 */

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Hook: AdminAreaHeadOutput
 * Inject AI reply buttons and modal into ticket view
 */
add_hook('AdminAreaHeadOutput', 1, function($vars) {
    // Only run on ticket view pages (supporttickets.php?action=view&id=X)
    if (basename($_SERVER['PHP_SELF']) !== 'supporttickets.php') {
        return '';
    }
    
    if (!isset($_GET['action']) || $_GET['action'] !== 'view') {
        return '';
    }
    
    if (!isset($_GET['id'])) {
        return '';
    }

    $ticketId = (int) $_GET['id'];
    
    // Get addon configuration
    $addonSettings = Capsule::table('tbladdonmodules')
        ->where('module', 'ai_ticket_assistant')
        ->pluck('value', 'setting')
        ->toArray();
    
    // Check if module has settings (if settings exist, module is active)
    if (empty($addonSettings)) {
        return '';
    }
    
    // Check if license key and API key are configured
    if (empty($addonSettings['license_key'] ?? '') || empty($addonSettings['gemini_api_key'] ?? '')) {
        return '';
    }
    
    // Get settings
    $maxInstructions = (int) ($addonSettings['max_instructions_chars'] ?? 1000);
    $maxContext = (int) ($addonSettings['max_context_chars'] ?? 1000);
    $enableQuick = ($addonSettings['enable_quick_reply'] ?? 'on') === 'on';
    $enableContext = ($addonSettings['enable_context_reply'] ?? 'on') === 'on';
    
    // If both are disabled, don't show anything
    if (!$enableQuick && !$enableContext) {
        return '';
    }
    
    $moduleLink = 'addonmodules.php?module=ai_ticket_assistant';
    
    // Build the JavaScript output
    $output = <<<HTML
<script type="text/javascript">
jQuery(document).ready(function(\$) {
  console.log('[AI Ticket Assistant] Initializing on ticket #{$ticketId}');
  
  // Add AI Reply buttons above reply textarea
  setTimeout(function() {
    var replyTextarea = \$('textarea[name="replycontents"], textarea[name="message"]').first();
    if (replyTextarea.length && !\$('#aiTicketAssistantButtons').length) {
      var buttonGroup = \$('<div>', {
        id: 'aiTicketAssistantButtons',
        class: 'btn-group',
        style: 'margin-bottom: 10px; margin-right: 5px;'
      });
      
HTML;

    // Add quick reply button if enabled
    if ($enableQuick) {
        $output .= <<<HTML
      var quickAIButton = \$('<button>', {
        type: 'button',
        id: 'aiQuickReply',
        class: 'btn btn-success btn-sm',
        title: 'Generate AI Reply (Quick)',
        'data-toggle': 'tooltip',
        html: '<i class="fa fa-magic"></i>'
      });
      buttonGroup.append(quickAIButton);
      quickAIButton.tooltip();
      
HTML;
    }
    
    // Add context reply button if enabled
    if ($enableContext) {
        $output .= <<<HTML
      var contextAIButton = \$('<button>', {
        type: 'button',
        id: 'aiContextReply',
        class: 'btn btn-primary btn-sm',
        title: 'Generate AI Reply with Context',
        'data-toggle': 'tooltip',
        html: '<i class="fa fa-magic"></i> <i class="fa fa-cog"></i>'
      });
      buttonGroup.append(contextAIButton);
      contextAIButton.tooltip();
      
HTML;
    }
    
    $output .= <<<HTML
      replyTextarea.before(buttonGroup);
      console.log('[AI Ticket Assistant] Buttons added');
    }
  }, 500);
  
HTML;

    // Add modal if context button is enabled
    if ($enableContext) {
        $output .= <<<HTML
  // Add AI Reply modal
  if (!\$('#aiReplyModal').length) {
    var modalHtml = '<div class="modal fade" id="aiReplyModal" tabindex="-1" role="dialog">' +
      '<div class="modal-dialog modal-lg" role="document">' +
      '<div class="modal-content">' +
      '<div class="modal-header">' +
      '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
      '<h4 class="modal-title"><i class="fa fa-magic"></i> Generate AI Reply with Context</h4>' +
      '</div>' +
      '<div class="modal-body">' +
      '<div class="form-group">' +
      '<label for="aiInstructions"><strong>Instructions for AI:</strong> <small class="text-muted"><span id="instructionsCounter">0</span> / {$maxInstructions}</small></label>' +
      '<textarea class="form-control" id="aiInstructions" rows="3" maxlength="{$maxInstructions}" placeholder="e.g., Explain how to configure the server settings, Provide refund information, Escalate to technical team, etc."></textarea>' +
      '<p class="help-block">Provide specific directions on how the AI should respond to this ticket.</p>' +
      '</div>' +
      '<div class="form-group">' +
      '<label for="aiContext"><strong>Additional Context (Optional):</strong> <small class="text-muted"><span id="contextCounter">0</span> / {$maxContext}</small></label>' +
      '<textarea class="form-control" id="aiContext" rows="3" maxlength="{$maxContext}" placeholder="e.g., Customer has VIP status, Related to ticket #1234, Server was upgraded yesterday, etc."></textarea>' +
      '<p class="help-block">Add any extra information the AI should consider when generating the response.</p>' +
      '</div>' +
      '<div class="form-group">' +
      '<label for="aiTone"><strong>Tone:</strong></label>' +
      '<select class="form-control" id="aiTone">' +
      '<option value="professional">Professional</option>' +
      '<option value="friendly">Friendly</option>' +
      '<option value="empathetic">Empathetic</option>' +
      '<option value="apologetic">Apologetic</option>' +
      '<option value="technical">Technical</option>' +
      '</select>' +
      '</div>' +
      '</div>' +
      '<div class="modal-footer">' +
      '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>' +
      '<button type="button" class="btn btn-success" id="aiGenerateSubmit">' +
      '<i class="fa fa-magic"></i> Generate Reply</button>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>';
    \$('body').append(modalHtml);

    // Character counters
    \$(document).on('input', '#aiInstructions', function() {
      var length = \$(this).val().length;
      var counter = \$('#instructionsCounter');
      counter.text(length);
      
      if (length > {$maxInstructions} * 0.9) {
        counter.parent().removeClass('text-muted text-warning').addClass('text-danger');
      } else if (length > {$maxInstructions} * 0.75) {
        counter.parent().removeClass('text-muted text-danger').addClass('text-warning');
      } else {
        counter.parent().removeClass('text-warning text-danger').addClass('text-muted');
      }
    });

    \$(document).on('input', '#aiContext', function() {
      var length = \$(this).val().length;
      var counter = \$('#contextCounter');
      counter.text(length);
      
      if (length > {$maxContext} * 0.9) {
        counter.parent().removeClass('text-muted text-warning').addClass('text-danger');
      } else if (length > {$maxContext} * 0.75) {
        counter.parent().removeClass('text-muted text-danger').addClass('text-warning');
      } else {
        counter.parent().removeClass('text-warning text-danger').addClass('text-muted');
      }
    });
  }

  // Show modal when context button clicked
  \$(document).on('click', '#aiContextReply', function() {
    var replyTextarea = \$('textarea[name="replycontents"], textarea[name="message"]').first();
    
    if (!replyTextarea.length) {
      alert('Could not find reply textarea');
      return;
    }

    if (replyTextarea.val().trim().length > 0) {
      if (!confirm('This will replace your current draft. Continue?')) {
        return;
      }
    }

    // Reset fields
    \$('#aiInstructions').val('');
    \$('#aiContext').val('');
    \$('#aiTone').val('professional');
    \$('#instructionsCounter').text('0');
    \$('#contextCounter').text('0');
    \$('#instructionsCounter').parent().removeClass('text-warning text-danger').addClass('text-muted');
    \$('#contextCounter').parent().removeClass('text-warning text-danger').addClass('text-muted');
    
    \$('#aiReplyModal').modal('show');
  });

  // Handle AI reply generation from modal
  \$(document).on('click', '#aiGenerateSubmit', function() {
    var btn = \$(this);
    var instructions = \$('#aiInstructions').val().trim();
    var context = \$('#aiContext').val().trim();
    var tone = \$('#aiTone').val();

    if (!instructions) {
      alert('Please provide instructions for the AI on how to respond.');
      \$('#aiInstructions').focus();
      return;
    }

    generateAIReply({$ticketId}, instructions, context, tone, btn);
  });
HTML;
    }
    
    // Add quick reply handler if enabled
    if ($enableQuick) {
        $output .= <<<HTML

  // Quick AI Reply handler
  \$(document).on('click', '#aiQuickReply', function() {
    var btn = \$(this);
    var replyTextarea = \$('textarea[name="replycontents"], textarea[name="message"]').first();
    
    if (!replyTextarea.length) {
      alert('Could not find reply textarea');
      return;
    }

    if (replyTextarea.val().trim().length > 0) {
      if (!confirm('This will replace your current draft. Continue?')) {
        return;
      }
    }

    generateAIReply({$ticketId}, 'Provide a helpful and professional response to this ticket.', '', 'professional', btn);
  });
HTML;
    }
    
    $output .= <<<HTML

  // Generate AI Reply function
  function generateAIReply(ticketId, instructions, context, tone, btn) {
    var replyTextarea = \$('textarea[name="replycontents"], textarea[name="message"]').first();
    var originalHtml = btn.html();
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');
    \$('#aiQuickReply, #aiContextReply').prop('disabled', true);

    \$.ajax({
      url: '{$moduleLink}&action=generate_reply',
      method: 'POST',
      data: { 
        ticket_id: ticketId,
        admin_instructions: instructions,
        extra_context: context,
        tone: tone
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          replyTextarea.removeAttr('maxlength');
          replyTextarea.val(response.reply);
          replyTextarea.trigger('change');
          replyTextarea.focus();
          
          \$('#aiReplyModal').modal('hide');
          
          console.log('[AI Ticket Assistant] Reply generated: ' + response.reply.length + ' characters');
        } else {
          alert('Error: ' + (response.message || 'Failed to generate reply'));
        }
        btn.prop('disabled', false).html(originalHtml);
        \$('#aiQuickReply, #aiContextReply').prop('disabled', false);
      },
      error: function(xhr, status, error) {
        console.error('[AI Ticket Assistant] AJAX Error:', status, error);
        alert('Failed to generate AI reply. Please try again.');
        btn.prop('disabled', false).html(originalHtml);
        \$('#aiQuickReply, #aiContextReply').prop('disabled', false);
      }
    });
  }
});
</script>
HTML;
    
    return $output;
});

/**
 * Handle AJAX requests for AI reply generation
 */
add_hook('AdminAreaPage', 1, function($vars) {
    if (isset($_GET['module']) && $_GET['module'] === 'ai_ticket_assistant' && isset($_GET['action']) && $_GET['action'] === 'generate_reply') {
        require_once __DIR__ . '/lib/Services/GeminiService.php';
        require_once __DIR__ . '/lib/Admin/Controllers/AdminController.php';
        
        $controller = new \WHMCS\Module\Addon\AITicketAssistant\Admin\Controllers\AdminController();
        $controller->generateReply();
    }
});
