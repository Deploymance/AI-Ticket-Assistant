# AI Ticket Assistant - WHMCS Addon

AI-powered ticket response generation using Google Gemini API with fully customizable settings.

## Features

- **Quick AI Reply**: Generate instant professional responses with one click
- **Context-Aware Replies**: Provide specific instructions and context for tailored responses
- **Configurable Settings**:
  - Custom Gemini API key
  - Adjustable character limits for input fields
  - Multi-language support (20+ languages)
  - Configurable output token limits
  - Enable/disable individual buttons
- **Multiple Tone Options**: Professional, Friendly, Empathetic, Apologetic, Technical
- **Smart Response Generation**: Uses Google Gemini 2.0 Flash for fast, high-quality responses
- **Character Counters**: Visual feedback with color-coded warnings
- **No Truncation**: Increased token limits prevent response cutoff

## Installation

1. The addon is already installed in `/custom/modules/addons/ai_ticket_assistant/`
2. Navigate to **Setup → Addon Modules** in WHMCS admin
3. Find "AI Ticket Assistant" and click "Activate"
4. Click "Configure" to set up your settings

## Configuration

### Required Settings

**Gemini API Key** (Required)
- Get your API key from [Google AI Studio](https://aistudio.google.com/app/apikey)
- Paste it in the configuration

### Optional Settings

**Max Characters - Instructions Field** (Default: 1000)
- Maximum characters allowed in the instructions textarea
- Range: 100-5000

**Max Characters - Context Field** (Default: 1000)
- Maximum characters allowed in the additional context textarea
- Range: 100-5000

**Response Language** (Default: Auto-detect)
- Choose the language for AI-generated responses
- Options: Auto-detect, English, Spanish, French, German, Italian, Portuguese, Dutch, Polish, Russian, Japanese, Chinese, Korean, Arabic, Hindi, Turkish, Swedish, Norwegian, Danish, Finnish

**Max Output Tokens** (Default: 4096)
- Controls the maximum length of generated responses
- Range: 1024-8192
- Higher values allow longer responses but cost more

**Enable Quick Reply Button** (Default: Yes)
- Show/hide the quick reply button (🪄)

**Enable Context Reply Button** (Default: Yes)
- Show/hide the reply with context button (🪄⚙️)

## Usage

### Quick Reply (🪄)

1. Open any support ticket
2. Click the **🪄** button above the reply textarea
3. AI generates a professional response instantly
4. Review and edit the generated response
5. Send to customer

### Reply with Context (🪄⚙️)

1. Open any support ticket
2. Click the **🪄⚙️** button above the reply textarea
3. In the modal dialog:
   - **Instructions**: Provide specific directions (e.g., "Explain server configuration", "Process refund")
   - **Additional Context**: Add relevant information (e.g., "VIP customer", "Related to ticket #1234")
   - **Tone**: Select the desired tone
4. Click "Generate Reply"
5. Review and edit the generated response
6. Send to customer

## Examples

### Quick Reply
- Best for standard inquiries
- Uses default professional tone
- No additional input required

### Reply with Context

**Example 1: Technical Support**
- Instructions: "Explain how to configure FTP settings for their game server"
- Context: "Customer is using Minecraft, server IP: 192.168.1.100"
- Tone: Technical

**Example 2: Billing Issue**
- Instructions: "Process refund for this month due to server downtime"
- Context: "Server was down for 3 days last week"
- Tone: Apologetic

**Example 3: VIP Customer**
- Instructions: "Provide upgrade options for their current plan"
- Context: "VIP customer, has been with us for 3 years"
- Tone: Friendly

## API Information

- **Model**: Google Gemini 2.0 Flash Experimental
- **Provider**: Google AI
- **Features**: Advanced language understanding, multi-language support, fast response times
- **Documentation**: [Gemini API Docs](https://ai.google.dev/gemini-api/docs)

## Troubleshooting

### Buttons Not Showing
- Ensure the addon is activated
- Check that API key is configured
- Verify at least one button is enabled in settings

### API Errors
- Verify API key is correct
- Check your API quota at [Google AI Studio](https://aistudio.google.com/app/apikey)
- Ensure you have an active Gemini API account

### Response Truncated
- Increase "Max Output Tokens" in settings
- Default is 4096, maximum is 8192

### Wrong Language
- Check "Response Language" setting
- Set to "Auto-detect" to match ticket language
- Or select specific language

## Version History

### 1.0.0 (Current)
- Initial release
- Configurable settings
- Multi-language support (20+ languages)
- Dual button interface
- Character limits with visual feedback
- Multiple Gemini model selection
- Customizable token limits

## Support

For issues or questions, contact your system administrator.

## Credits

Developed by astroom.
