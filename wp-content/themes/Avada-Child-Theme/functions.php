<?php

//** Spots Shortcode
function project_spots(){
 // Get the event ID.
$event_id = get_the_ID();
 
// Fetch from this Event all custom fields and their values.
$fields = tribe_get_custom_fields( $event_id );
 
if ( ! empty( $fields['Spots'] ) ) :
      return ( $fields['Spots'] );
endif;
}

add_shortcode('spots', 'project_spots');

//** Form ID Shortcode
function get_tec_form_id(){
 // Get the event ID.
$event_id = get_the_ID();
 
// Fetch from this Event all custom fields and their values.
$fields = tribe_get_custom_fields( $event_id );
 
if ( ! empty( $fields['Form ID'] ) ) :
      return ( $fields['Form ID'] );
endif;
}

add_shortcode('form_id', 'get_tec_form_id');

/**
 * Gravity Forms // Entries Left Shortcode
 * https://gravitywiz.com/shortcode-display-number-of-entries-left/
 *
 * Instruction Video: https://www.loom.com/share/b6c46aebf0ff483496faf9994e36083e
 *
 * Installation instructions: https://gravitywiz.com/documentation/how-do-i-install-a-snippet/
 */
add_filter( 'gform_shortcode_entries_left', 'gwiz_entries_left_shortcode', 10, 2 );
function gwiz_entries_left_shortcode( $output, $atts ) {
	
	//Mark's terrible code
$event_id = get_the_ID(); 
$fields = tribe_get_custom_fields( $event_id );
 
if ( ! empty( $fields['Form ID'] ) ) :
      $projectformid = ( $fields['Form ID'] );
endif;
	
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( shortcode_atts( array(
		'id'     => false,
		'format' => false, // should be 'comma', 'decimal'
	), $atts ) );

	if ( ! $projectformid ) {
		$projectformid = ( $id );
	}

	$form = GFAPI::get_form( $projectformid );
	if ( ! rgar( $form, 'limitEntries' ) || ! rgar( $form, 'limitEntriesCount' ) ) {
		return '';
	}

	$entry_count = GFAPI::count_entries( $form['id'], array(
		'status' => 'active',
	) );

	$entries_left = rgar( $form, 'limitEntriesCount' ) - $entry_count;
	$output       = $entries_left;

	if ( $format ) {
		$format = $format == 'decimal' ? '.' : ',';
		$output = number_format( $entries_left, 0, false, $format );
	}

	return $entries_left > 0 ? $output : 0;
}

function wpb_hook_javascript() {
    ?>
        <script>
// MEMBER PASSWORD
function verify() { // I created the function, which is called onclick on the button
  if (document.getElementById('password').value.toUpperCase() === 'TRAILS2024') {
    document.getElementById('HIDDENDIV').classList.remove("hidden"); // Using class instead of inline CSS
    document.getElementById('credentials').classList.add("hidden"); // Hide the div containing the credentials
  } else {
    alert('Invalid Password!');
    password.setSelectionRange(0, password.value.length);
  }
  return false;
}
</script>
    <?php
}
add_action('wp_head', 'wpb_hook_javascript');

// WAITLIST PASSWORD
function wpb_hook_javascript_wait() {
    ?>
        <script>
function waitverify() { // I created the function, which is called onclick on the button
  if (document.getElementById('password').value.toUpperCase() === 'WAITLIST') {
    document.getElementById('HIDDENDIV').classList.remove("hidden"); // Using class instead of inline CSS
    document.getElementById('credentials').classList.add("hidden"); // Hide the div containing the credentials
  } else {
    alert('Invalid Password!');
    password.setSelectionRange(0, password.value.length);
  }
  return false;
}
</script>
    <?php
}
add_action('wp_head', 'wpb_hook_javascript_wait');

add_filter('acf/settings/remove_wp_meta_box', '__return_false');

function EventFullShortcode() {
$ita_form_id = get_field('event_form_id'); 
$ita_spots_left = do_shortcode( '[gravityforms action="entries_left" id=" ' . $ita_form_id . ' "]' );

if ($ita_spots_left == "0") {
  return " (Full)";
};
}
add_shortcode('event_full', 'EventFullShortcode');

function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [] );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 20 );

function avada_lang_setup() {
	$lang = get_stylesheet_directory() . '/languages';
	load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

add_filter( 'tribe_events_views_v2_view_repository_args', 'tec_exclude_events_category', 10, 3 );
 
function tec_exclude_events_category( $repository_args, $context, $view ) {
  // If not shortcode calendar then bail and show everything
  if ( $context->is('shortcode') ) {
    return $repository_args;
  }
   
  // List of category slugs to be excluded
  $excluded_categories = [
    'events',
  ];
  $repository_args['category_not_in'] = $excluded_categories;
 
  return $repository_args;
}


 
/*
 * Alters event's archive titles
 */
function tribe_alter_event_archive_titles ( $original_recipe_title, $depth ) {
 
  // Modify the titles here
  // Some of these include %1$s and %2$s, these will be replaced with relevant dates
  $title_upcoming =   'Volunteer Project Schedule'; // List View: Upcoming events
  $title_past =       'Past Events'; // List view: Past events
  $title_range =      'Events for %1$s - %2$s'; // List view: range of dates being viewed
  $title_month =      'Events for %1$s'; // Month View, %1$s = the name of the month
  $title_day =        'Events for %1$s'; // Day View, %1$s = the day
  $title_all =        'All events for %s'; // showing all recurrences of an event, %s = event title
  $title_week =       'Events for week of %s'; // Week view
 
  // Don't modify anything below this unless you know what it does
  global $wp_query;
  $tribe_ecp = Tribe__Events__Main::instance();
  $date_format = apply_filters( 'tribe_events_pro_page_title_date_format', tribe_get_date_format( true ) );
 
  // Default Title
  $title = $title_upcoming;
 
  // If there's a date selected in the tribe bar, show the date range of the currently showing events
  if ( isset( $_REQUEST['tribe-bar-date'] ) && $wp_query->have_posts() ) {
 
    if ( $wp_query->get( 'paged' ) > 1 ) {
      // if we're on page 1, show the selected tribe-bar-date as the first date in the range
      $first_event_date = tribe_get_start_date( $wp_query->posts[0], false );
    } else {
      //otherwise show the start date of the first event in the results
      $first_event_date = tribe_event_format_date( $_REQUEST['tribe-bar-date'], false );
    }
 
    $last_event_date = tribe_get_end_date( $wp_query->posts[ count( $wp_query->posts ) - 1 ], false );
    $title = sprintf( $title_range, $first_event_date, $last_event_date );
  } elseif ( tribe_is_past() ) {
    $title = $title_past;
  }
 
  // Month view title
  if ( tribe_is_month() ) {
    $title = sprintf(
      $title_month,
      date_i18n( tribe_get_option( 'monthAndYearFormat', 'F Y' ), strtotime( tribe_get_month_view_date() ) )
    );
  }
 
  // Day view title
  if ( tribe_is_day() ) {
    $title = sprintf(
      $title_day,
      date_i18n( tribe_get_date_format( true ), strtotime( $wp_query->get( 'start_date' ) ) )
    );
  }
 
  // All recurrences of an event
  if ( function_exists('tribe_is_showing_all') && tribe_is_showing_all() ) {
    $title = sprintf( $title_all, get_the_title() );
  }
 
  // Week view title
  if ( function_exists('tribe_is_week') && tribe_is_week() ) {
    $title = sprintf(
      $title_week,
      date_i18n( $date_format, strtotime( tribe_get_first_week_day( $wp_query->get( 'start_date' ) ) ) )
    );
  }
 
  if ( is_tax( $tribe_ecp->get_event_taxonomy() ) && $depth ) {
    $cat = get_queried_object();
    $title = '<a href="' . esc_url( tribe_get_events_link() ) . '">' . $title . '</a>';
    $title .= ' › ' . $cat->name;
  }
 
  return $title;
}
add_filter( 'tribe_get_events_title', 'tribe_alter_event_archive_titles', 11, 2 );