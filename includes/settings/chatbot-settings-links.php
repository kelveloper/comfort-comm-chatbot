<?php
/**
 * Steven-Bot - Settings Links
 *
 * Adds links for Settings, Support, and View Details in the plugins page.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Add link to settings in the plugins page action links
function steven_bot_plugin_action_links( $links ) {

    if ( current_user_can( 'manage_options' ) ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=steven-bot' ) ) . '">' . __( 'Settings', 'steven-bot' ) . '</a>';
        $support_link = '<a href="' . esc_url( admin_url( 'admin.php?page=steven-bot&tab=support' ) ) . '">' . __( 'Support', 'steven-bot' ) . '</a>';
        array_unshift( $links, $settings_link, $support_link );
    }
    return $links;

}

// Add View details link in the plugin row meta
function steven_bot_plugin_row_meta( $links, $file ) {

    // Get the main plugin file basename
    $plugin_file = 'steven-bot/steven-bot.php';

    if ( $plugin_file == $file ) {
        $links[] = '<a href="#TB_inline?width=600&height=550&inlineId=steven-bot-details" class="thickbox" title="Steven-Bot">' . __( 'View details', 'steven-bot' ) . '</a>';
    }
    return $links;

}
add_filter( 'plugin_row_meta', 'steven_bot_plugin_row_meta', 10, 2 );

// Add the thickbox content for View details
function steven_bot_add_details_thickbox() {
    $screen = get_current_screen();
    if ( $screen->id !== 'plugins' ) {
        return;
    }

    // Enqueue thickbox
    add_thickbox();

    global $steven_bot_plugin_version;
    ?>
    <div id="steven-bot-details" style="display:none;">
        <div style="padding: 20px;">
            <h2 style="margin-top: 0;">Steven-Bot</h2>
            <p><strong>Version:</strong> <?php echo esc_html( $steven_bot_plugin_version ); ?></p>
            <p><strong>Author:</strong> <a href="https://github.com/kelveloper" target="_blank">kelveloper</a></p>

            <h3>Description</h3>
            <p>AI-powered FAQ chatbot with semantic search. Uses Supabase vector search to match customer questions with your knowledge base, with Gemini/OpenAI fallback for unmatched queries.</p>

            <h3>How It Works</h3>
            <ol>
                <li><strong>You add FAQs</strong> - Create a knowledge base of questions and answers</li>
                <li><strong>Vector embeddings</strong> - Each FAQ is converted to a vector embedding</li>
                <li><strong>Customer asks question</strong> - Chatbot finds the most similar FAQ using Supabase pgvector</li>
                <li><strong>Instant answer</strong> - Returns your FAQ answer directly (no AI generation)</li>
                <li><strong>AI fallback</strong> - If no match, falls back to Gemini/OpenAI</li>
            </ol>

            <h3>Key Features</h3>
            <ul>
                <li><strong>Semantic Search</strong> - Questions don't need exact matches</li>
                <li><strong>Cost Effective</strong> - FAQ matches are nearly free</li>
                <li><strong>Knowledge Base</strong> - Manage FAQs in WordPress admin</li>
                <li><strong>Gap Analysis</strong> - See unanswered questions with AI suggestions</li>
                <li><strong>Analytics Dashboard</strong> - Track conversations and identify gaps</li>
            </ul>

            <h3>Requirements</h3>
            <ul>
                <li>Google Gemini API key (for embeddings and AI fallback)</li>
                <li>Supabase project (for vector database with pgvector)</li>
            </ul>

            <h3>Links</h3>
            <p>
                <a href="https://github.com/kelveloper" target="_blank">GitHub</a> |
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=steven-bot&tab=support' ) ); ?>">Documentation</a>
            </p>
        </div>
    </div>
    <?php
}
add_action( 'admin_footer', 'steven_bot_add_details_thickbox' );
