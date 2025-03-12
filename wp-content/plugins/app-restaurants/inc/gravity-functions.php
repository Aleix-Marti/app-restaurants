<?php

/**
 * Fucionalitats relacionades amb Gravity Forms
 */


function amc_toggle_visibility($atts) {
  if (is_user_logged_in()) {
    return '<p class="gf-already-registered">Ja estàs registrat i loguejat.</p>';
  } else {
    $atts = shortcode_atts(array(
      'id' => ''
    ), $atts);
    
    return do_shortcode('[gravityform id="' . $atts['id'] . '" title="false" description="false" ajax="true"]');
  }
}
add_shortcode('amc_toggle_gf_visibility', 'amc_toggle_visibility');