jQuery(document).ready(function ($) {
    
    function steven_bot_localize() {
    
        // DIAG - Diagnostics - Ver 2.1.1.1
        // console.log('Chatbot: NOTICE: steven-bot-local.js - Before localStorage.set Item loop');

        // Resolve LocalStorage - Ver 2.1.1.1.R3
        const includeKeys = [
            'steven_bot_last_reset',
            'steven_bot_message_count',
            'steven_bot_display_message_count',
            'steven_bot_message_limit_setting',
            'steven_bot_message_limit_period_setting',
            'steven_bot_start_status',
            'steven_bot_start_status_new_visitor',
            'steven_bot_opened',
            'steven_bot_last_reset',
            'steven_bot_appearance_open_icon',
            'steven_bot_appearance_collapse_icon',
            'steven_bot_appearance_erase_icon',
            'steven_bot_appearance_mic_enabled_icon',
            'steven_bot_appearance_mic_disabled_icon',
        ];
        
        if (typeof kchat_settings === "object") {
            Object.keys(kchat_settings).forEach(function(key) {
                if (includeKeys.includes(key)) {
                    localStorage.setItem(key, kchat_settings[key]);
                    // DIAG - Diagnostics - Ver 2.1.1.1
                    // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + kchat_settings[key]);
                }
            });
        }

        // DIAG - Diagnostics - Ver 2.1.1.1
        // console.log('Chatbot: NOTICE: steven-bot-local.js - After localStorage.set Item loop');

    }

    // Function to check if the chatbot shortcode is present on the page
    function isChatbotShortcodePresent() {
        // console.log('Chatbot: NOTICE: steven-bot-local.js - isChatbotShortcodePresent: ' + document.querySelector('.steven-bot') !== null);
        return document.querySelector('.steven-bot') !== null;
    }

    // Only call the function if the chatbot shortcode is present
    if (isChatbotShortcodePresent()) {
        // console.log('Chatbot: NOTICE: steven-bot-local.js - isChatbotShortcodePresent: ' + isChatbotShortcodePresent());
        steven_bot_localize();
    }

});
