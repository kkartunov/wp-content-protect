<?php
echo '<div class="WPCP_row">',
        '<label for="wpcp_add_protect" class="selectit">',
            '<input type="checkbox" id="wpcp_add_protect" name="wpcp_add_protect"',
                ($this -> settings['protect_by_default'] && ($post->post_status == 'auto-draft' || $post->post_status == 'draft' || $post->post_status == 'pending' || $post->post_status == 'future'))?' checked':'',
            '>',
            __('Restrict Content For:', 'WP_ContentProtect_Textdomain'),
        '</label>',
        '<input type="text" id="wpcp_protect_for" name="wpcp_protect_for" value="',
            isset($meta_wpcp['protect_for'])?$meta_wpcp['protect_for']:$this -> settings['protect_for'],'">',
    '</div>',
    '<div class="WPCP_row">',
        '<label for="wpcp_protect_indef" class="selectit">',
            '<input type="checkbox" id="wpcp_protect_indef" name="wpcp_protect_indef">',
            __('Lock Indefinitely', 'WP_ContentProtect_Textdomain'),
        '</label>',
    '</div>';
