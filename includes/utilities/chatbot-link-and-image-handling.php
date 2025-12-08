<?php
/**
 * Steven-Bot - Link and Image Handling
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package steven-bot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function steven_bot_check_for_links_and_images( $response ) {

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', "Entering steven_bot_check_for_links_and_images()" );
    // back_trace( 'NOTICE', "Response: " . print_r($response, true) );

    // Get stored image width
    $img_width = esc_attr(get_option('steven_bot_image_width_setting', '100%'));
    if (!str_ends_with($img_width, 'px') && !str_ends_with($img_width, '%')) {
        $img_width = 'auto';
    } else if ($img_width === '100%') {
        $img_width = 'auto';
    }

    $response = preg_replace_callback('/(!)?\[([^\]]+)\]\(([^)]+)\)/', function($matches) use ($img_width) {
        // If the first character is "!", it's an image
        if ($matches[1] === "!") {
            return "<span><center><img src=\"" . $matches[3] . "\" alt=\"" . $matches[2] . "\" style=\"max-width: 95%; width: " . $img_width . ";\" /></center></span>";
        } else {
            // Otherwise, it's a link
            return "<span><a href=\"" . $matches[3] . "\" target=\"_blank\">" . $matches[2] . "</a></span>"; 
        }
    }, $response);

    return $response;
}

