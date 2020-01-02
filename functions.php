<?php
//Add thumbnail, automatic feed links and title tag support ##
add_theme_support('post-thumbnails');
add_theme_support('automatic-feed-links');
add_theme_support('title-tag');
add_theme_support('html5', array('search-form'));
//Add custom image size for content listing
add_image_size('list', 800, 400, array('left', 'center'));
add_image_size('splash', 2000, 800, array('center', 'center'));
add_image_size('full_banner', 2000, 800, array('center', 'center'));
add_image_size('gallery_crunch', 999999, 800, false);
//Add content width (desktop default)
if (!isset($content_width))
{
	$content_width = 768;
}

//Add menu support and register main menu
if (function_exists('register_nav_menus'))
{
	register_nav_menus(
		array(
			'main_menu' => 'Main Menu'
		)
	);
}


// filter the Gravity Forms button type
add_filter('gform_submit_button', 'form_submit_button', 10, 2);
function form_submit_button($button, $form)
{
	return "<button class='button btn' id='gform_submit_button_{$form["id"]}'><span>{$form['button']['text']}</span></button>";
}

// Register sidebar
add_action('widgets_init', 'theme_register_sidebar');
function theme_register_sidebar()
{
	if (function_exists('register_sidebar'))
	{
		register_sidebar(array(
			'id'            => 'sidebar-1',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4>',
			'after_title'   => '</h4>',
		));
	}
}

// Bootstrap_Walker_Nav_Menu setup

add_action('after_setup_theme', 'bootstrap_setup');

if (!function_exists('bootstrap_setup')):

	function bootstrap_setup()
	{

		add_action('init', 'register_menu');

		function register_menu()
		{
			register_nav_menu('top-bar', 'Bootstrap Top Menu');
		}

		class Bootstrap_Walker_Nav_Menu extends Walker_Nav_Menu {


			function start_lvl(&$output, $depth = 0, $args = array())
			{

				$indent = str_repeat("\t", $depth);
				$output .= "\n$indent<ul class=\"dropdown-menu\">\n";

			}

			function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
			{

				if (!is_object($args))
				{
					return; // menu has not been configured
				}

				$indent = ($depth) ? str_repeat("\t", $depth) : '';

				$li_attributes = '';
				$class_names = $value = '';

				$classes = empty($item->classes) ? array() : (array) $item->classes;
				$classes[] = ($args->has_children) ? 'dropdown' : '';
				$classes[] = ($item->current || $item->current_item_ancestor) ? 'active' : '';
				$classes[] = 'menu-item-' . $item->ID;


				$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
				$class_names = ' class="' . esc_attr($class_names) . '"';

				$id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
				$id = strlen($id) ? ' id="' . esc_attr($id) . '"' : '';

				$output .= $indent . '<li' . $id . $value . $class_names . $li_attributes . '>';

				$attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
				$attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
				$attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
				$attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
				$attributes .= ($args->has_children) ? ' class="dropdown-toggle" data-toggle="dropdown"' : '';

				$item_output = $args->before;
				$item_output .= '<a' . $attributes . '>';
				$item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
				$item_output .= ($args->has_children) ? ' <b class="caret"></b></a>' : '</a>';
				$item_output .= $args->after;

				$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
			}

			function display_element($element, &$children_elements, $max_depth, $depth = 0, $args, &$output)
			{

				if (!$element)
					return;

				$id_field = $this->db_fields['id'];

				//display this element
				if (is_array($args[0]))
					$args[0]['has_children'] = !empty($children_elements[$element->$id_field]);
				else if (is_object($args[0]))
					$args[0]->has_children = !empty($children_elements[$element->$id_field]);
				$cb_args = array_merge(array(&$output, $element, $depth), $args);
				call_user_func_array(array(&$this, 'start_el'), $cb_args);

				$id = $element->$id_field;

				// descend only when the depth is right and there are childrens for this element
				if (($max_depth == 0 || $max_depth > $depth + 1) && isset($children_elements[$id]))
				{

					foreach ($children_elements[$id] as $child)
					{

						if (!isset($newlevel))
						{
							$newlevel = true;
							//start the child delimiter
							$cb_args = array_merge(array(&$output, $depth), $args);
							call_user_func_array(array(&$this, 'start_lvl'), $cb_args);
						}
						$this->display_element($child, $children_elements, $max_depth, $depth + 1, $args, $output);
					}
					unset($children_elements[$id]);
				}

				if (isset($newlevel) && $newlevel)
				{
					//end the child delimiter
					$cb_args = array_merge(array(&$output, $depth), $args);
					call_user_func_array(array(&$this, 'end_lvl'), $cb_args);
				}

				//end this element
				$cb_args = array_merge(array(&$output, $element, $depth), $args);
				call_user_func_array(array(&$this, 'end_el'), $cb_args);
			}
		}
	}
endif;


/**
 * Load site scripts.
 */
function bootstrap_theme_enqueue_scripts()
{
	$template_url = get_template_directory_uri();
	/**************************************************************************************/
	//JQUERY, JQUERY UI DATEPICKER & JQUERY UI SLIDER ARE ALL INCLUDED AS PART OF WORDPRESS 
	/**************************************************************************************/
	// jQuery.
	wp_enqueue_script('jquery');

	function wpse_206140_enqueue_script()
	{
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-slider');
		wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	}

	add_action('wp_enqueue_scripts', 'wpse_206140_enqueue_script');
	wp_enqueue_style('timepiceker-style', $template_url . '/css/timepicker.css');
	//wp_enqueue_script( 'timepicker-script', $template_url . '/js/timepicker.js', array( 'jquery' ), null, true );
	// Bootstrap
	wp_enqueue_script('bootstrap-script', '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js', array('jquery'), null, true);
	wp_enqueue_style('bootstrap-style', '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css');

	//font and icons
	wp_enqueue_style('font', $template_url . '/css/font.css');
	wp_enqueue_style('icon', $template_url . '/css/icon.css');

	//Theme Javascript and CSS
	if(defined('WP_ENV') && WP_ENV == 'development'){
		//Javascript
		wp_enqueue_script( 'gazette-script', $template_url . '/js/gazette.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'gazette-navbar-script', $template_url . '/js/navbar.js', array( 'jquery' ), null, true ); //navbar
		//CSS
		wp_enqueue_style('main-style', get_stylesheet_uri());
	}
	else{
		//Javascript
		wp_enqueue_script('gazette-navbar-script', $template_url . '/js/gazette-combo.min.js', array('jquery'), null, true);
		//CSS
		wp_enqueue_style('main-style', $template_url . '/css/gazette.min.css');
	}

	//Feedback Form
	wp_enqueue_style('feedback', $template_url . '/includes/feedback/feedback.min.css');
	wp_enqueue_style('feedback-fonts', $template_url . '/includes/feedback/fonts.css');

	//Print style
	wp_enqueue_style('print-style', $template_url . '/css/print.css', null, null, 'print');

	// Load Thread comments WordPress script.
	if (is_singular() && get_option('thread_comments'))
	{
		wp_enqueue_script('comment-reply');
	}
}

add_action('wp_enqueue_scripts', 'bootstrap_theme_enqueue_scripts', 1);


add_action('admin_enqueue_scripts', 'gaz_load_admin_script');
function gaz_load_admin_script($hook)
{
	wp_enqueue_script(
		'gaz_admin_script', //unique handle
		get_template_directory_uri() . '/js/admin-scripts.js', //location
		array('jquery')  //dependencies
	);
}

//----hide weight from contributors
if (is_admin()) :
	add_action('admin_enqueue_scripts', 'gaz_load_contrib_script');
	function gaz_load_contrib_script($hook)
	{
		if (!current_user_can('edit_others_posts'))
		{
			wp_enqueue_script(
				'gaz_contrib_script', //unique handle
				get_template_directory_uri() . '/js/contrib-scripts.js', //location
				array('jquery')  //dependencies
			);
		}
	}
endif;

//////// CREATE GUID

function guid($opt = true)
{       //  Set to true/false as your default way to do this.

	if (function_exists('com_create_guid'))
	{
		if ($opt)
		{
			return com_create_guid();
		} else
		{
			return trim(com_create_guid(), '{}');
		}
	} else
	{
		mt_srand((double) microtime() * 10000);    // optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);    // "-"
		$left_curly = $opt ? chr(123) : "";     //  "{"
		$right_curly = $opt ? chr(125) : "";    //  "}"
		$uuid = $left_curly
			. substr($charid, 0, 8) . $hyphen
			. substr($charid, 8, 4) . $hyphen
			. substr($charid, 12, 4) . $hyphen
			. substr($charid, 16, 4) . $hyphen
			. substr($charid, 20, 12)
			. $right_curly;

		return $uuid;
	}
}

/*-------- ADD POST TYPE SUPPORT ----------*/
add_theme_support('post-formats', array('gallery', 'video', 'audio'));
add_post_type_support('post', 'post-formats');

/*-------- END ADD POST TYPE SUPPORT ----------*/
/*-------- ADD DASHICONS ______*/
add_action('wp_enqueue_scripts', 'gz_load_dashicons');
function gz_load_dashicons()
{
	wp_enqueue_style('dashicons');
}

/*-------- END ADD DASHICONS ______*/

add_action('admin_enqueue_scripts', 'gz_tinymce_admin_style');
/**
 * Add stylesheet to the admin pages -- used for tinyMCE buttons
 */
function gz_tinymce_admin_style()
{
	wp_enqueue_style('custom-mce-style', get_template_directory_uri() . '/css/admin-style.css');
}

/*------- ADD POSITION TO PROFILE ---*/
function add_profile_fields($profile_fields)
{
	$profile_fields['gazette_position'] = 'Gazette Position';

	return $profile_fields;
}

add_filter('user_contactmethods', 'add_profile_fields');

/*------- ADD SECONDARY CATEGORIES ------*/
add_action('init', 'add_theme_group', 0);

function add_theme_group()
{
	$sc_tax = 'theme_group';
	$sc_type = 'post';
	$sc_args = array(
		'label'             => 'Theme Group',
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_admin_column' => false,
		'rewrite'           => array( 'slug' => 'special-features' )
	);
	register_taxonomy($sc_tax, $sc_type, $sc_args);
	register_taxonomy_for_object_type('theme_group', 'post');

}

/*------- ADD SECONDARY CATEGORIES ------*/
add_action('init', 'add_secondary_cat', 0);

function add_secondary_cat()
{
	$sc_tax = 'secondary_category';
	$sc_type = 'post';
	$sc_args = array(
		'label'             => ' Secondary Categories',
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_admin_column' => true
	);
	register_taxonomy($sc_tax, $sc_type, $sc_args);
	register_taxonomy_for_object_type('secondary_category', 'post');

}

/*------ ADD CONTRIBUTOR TAXONOMY ---------*/
add_action('init', 'add_contributors', 0);

function add_contributors()
{
	$sc_tax = 'contributors';
	$sc_type = 'post';
	$sc_args = array(
		'label'             => 'Contributors',
		'hierarchical'      => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'contributor' )

	);
	register_taxonomy($sc_tax, $sc_type, $sc_args);
	register_taxonomy_for_object_type('contributors', 'post');
}

add_action('contributors_add_form_fields', 'add_email_field', 10, 2);
add_action('contributors_add_form_fields', 'add_position_field', 10, 2);
add_action('contributors_add_form_fields', 'add_dept_field', 10, 2);
add_action('contributors_add_form_fields', 'add_guest_field', 10, 2);

function add_email_field($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="contributor-email">Email</label>
		<input type="text" name="contributor-email">
	</div>

	<?php
}

function add_position_field($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="contributor-position">Position</label>
		<input type="text" name="contributor-position">
	</div>

	<?php
}

function add_dept_field($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="contributor-department">Department</label>
		<input type="text" name="contributor-department">
	</div>

	<?php
}

function add_guest_field($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="contributor-guest">Guest Contributor?</label>
		<input type="radio" name="contributor-guest" value="true">True</br>
		<input type="radio" name="contributor-guest" value="false" checked="checked">False</br>
	</div>

	<?php
}

add_action('created_contributors', 'save_email_meta', 10, 2);
add_action('created_contributors', 'save_guest_meta', 10, 2);
add_action('created_contributors', 'save_position_meta', 10, 2);
add_action('created_contributors', 'save_department_meta', 10, 2);

function save_email_meta($term_id, $tt_id)
{
	if (isset($_POST['contributor-email']) && '' !== $_POST['contributor-email'])
	{
		$email = sanitize_email($_POST['contributor-email']);
		add_term_meta($term_id, 'contributor-email', $email, true);
	}
}

function save_position_meta($term_id, $tt_id)
{
	if (isset($_POST['contributor-position']) && '' !== $_POST['contributor-position'])
	{
		$position = sanitize_text_field($_POST['contributor-position']);
		add_term_meta($term_id, 'contributor-position', $position, true);
	}
}

function save_department_meta($term_id, $tt_id)
{
	if (isset($_POST['contributor-department']) && '' !== $_POST['contributor-department'])
	{
		$position = sanitize_text_field($_POST['contributor-department']);
		add_term_meta($term_id, 'contributor-department', $position, true);
	}
}

function save_guest_meta($term_id, $tt_id)
{
	if (isset($_POST['contributor-guest']) && '' !== $_POST['contributor-guest'])
	{
		$guest = filter_var($_POST['contributor-guest'], FILTER_SANITIZE_STRING);
		add_term_meta($term_id, 'contributor-guest', $guest, true);
	}
}

add_action('contributors_edit_form_fields', 'edit_email_meta', 10, 2);
add_action('contributors_edit_form_fields', 'edit_position_meta', 10, 2);
add_action('contributors_edit_form_fields', 'edit_department_meta', 10, 2);
add_action('contributors_edit_form_fields', 'edit_guest_meta', 10, 2);


function edit_email_meta($term, $taxonomy)
{

	$c_email = get_term_meta($term->term_id, 'contributor-email', true);

	?>
	<tr class="form-field term-group-wrap">
	<th scope="row"><label for="contributor-email">Email</label></th>
	<td><input type="text" value="<?php echo $c_email; ?>" name="contributor-email"></td>
	</tr>
	<?php
}

function edit_position_meta($term, $taxonomy)
{

	$c_position = get_term_meta($term->term_id, 'contributor-position', true);

	?>
	<tr class="form-field term-group-wrap">
	<th scope="row"><label for="contributor-position">Position</label></th>
	<td><input type="text" value="<?php echo $c_position; ?>" name="contributor-position"></td>
	</tr>
	<?php
}

function edit_department_meta($term, $taxonomy)
{

	$c_department = get_term_meta($term->term_id, 'contributor-department', true);

	?>
	<tr class="form-field term-group-wrap">
	<th scope="row"><label for="contributor-department">Department</label></th>
	<td><input type="text" value="<?php echo $c_department; ?>" name="contributor-department"></td>
	</tr>
	<?php
}

function edit_guest_meta($term, $taxonomy)
{

	$c_guest = get_term_meta($term->term_id, 'contributor-guest', true);

	?>
	<tr class="form-field term-group-wrap">
	<th scope="row"><label for="contributor-guest">Guest Contributor?</label></th>
	<td>
		<input type="radio" name="contributor-guest" value="true"
			<?php if ($c_guest == 'true')
			{
				echo "checked='checked'";
			} ?>
		>True</br>
		<input type="radio" name="contributor-guest" value="false"
			<?php if ($c_guest == 'false')
			{
				echo "checked='checked'";
			} ?>
		>False</br>
	</td>
	</tr>
	<?php
}

add_action('edited_contributors', 'update_email_meta', 10, 2);
add_action('edited_contributors', 'update_position_meta', 10, 2);
add_action('edited_contributors', 'update_department_meta', 10, 2);
add_action('edited_contributors', 'update_guest_meta', 10, 2);

function update_email_meta($term_id, $tt_id)
{

	if (isset($_POST['contributor-email']) && '' !== $_POST['contributor-email'])
	{
		$email = sanitize_email($_POST['contributor-email']);
		update_term_meta($term_id, 'contributor-email', $email);
	}
}

function update_position_meta($term_id, $tt_id)
{

	if (isset($_POST['contributor-position']) && '' !== $_POST['contributor-position'])
	{
		$position = sanitize_text_field($_POST['contributor-position']);
		update_term_meta($term_id, 'contributor-position', $position);
	}
}

function update_department_meta($term_id, $tt_id)
{

	if (isset($_POST['contributor-department']) && '' !== $_POST['contributor-department'])
	{
		$department = sanitize_text_field($_POST['contributor-department']);
		update_term_meta($term_id, 'contributor-department', $department);
	}
}

function update_guest_meta($term_id, $tt_id)
{

	if (isset($_POST['contributor-guest']) && '' !== $_POST['contributor-guest'])
	{
		$guest = filter_var($_POST['contributor-guest'], FILTER_SANITIZE_STRING);
		update_term_meta($term_id, 'contributor-guest', $guest);
	}
}
// Register Taxonomy Yaffle Profile
// Taxonomy Key: yaffleprofile
function create_yaffleprofile_tax() {

	$labels = array(
		'name'              => _x( 'Yaffle Profiles', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Yaffle Profile', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Yaffle Profiles', 'textdomain' ),
		'all_items'         => __( 'All Yaffle Profiles', 'textdomain' ),
		'parent_item'       => __( 'Parent Yaffle Profile', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Yaffle Profile:', 'textdomain' ),
		'edit_item'         => __( 'Edit Yaffle Profile', 'textdomain' ),
		'update_item'       => __( 'Update Yaffle Profile', 'textdomain' ),
		'add_new_item'      => __( 'Add New Yaffle Profile', 'textdomain' ),
		'new_item_name'     => __( 'New Yaffle Profile Name', 'textdomain' ),
		'menu_name'         => __( 'Yaffle Profile', 'textdomain' ),
	);
	$args = array(
		'labels' => $labels,
		'description' => __( 'Yaffle Profile Information', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => false,
		'show_tagcloud' => false,
		'show_in_quick_edit' => false,
		'show_admin_column' => false,
	);
	register_taxonomy( 'yaffleprofile', array('post', ), $args );

}
add_action( 'init', 'create_yaffleprofile_tax' );

/* YAFFLE PROFILE - POSITION */
add_action('yaffleprofile_add_form_fields', 'add_position_field_yp', 10, 2);
add_action('created_yaffleprofile', 'save_position_meta_yp', 10, 2);
add_action('yaffleprofile_edit_form_fields', 'edit_position_meta_yp', 10, 2);
add_action('edited_yaffleprofile', 'update_position_meta_yp', 10, 2);

function add_position_field_yp($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="yaffleprofile-position">Position</label>
		<input type="text" name="yaffleprofile-position">
	</div>

	<?php
}

function save_position_meta_yp($term_id, $tt_id)
{
	if (isset($_POST['yaffleprofile-position']) && '' !== $_POST['yaffleprofile-position'])
	{
		$yp_position = sanitize_text_field($_POST['yaffleprofile-position']);
		add_term_meta($term_id, 'yaffleprofile-position', $yp_position, true);
	}
}

function edit_position_meta_yp($term, $taxonomy)
{

	$yp_position = get_term_meta($term->term_id, 'yaffleprofile-position', true);

	?>
	<tr class="form-field term-group-wrap">
		<th scope="row"><label for="yaffleprofile-position">Position</label></th>
		<td><input type="text" value="<?php echo $yp_position; ?>" name="yaffleprofile-position"></td>
	</tr>
	<?php
}
function update_position_meta_yp($term_id, $tt_id)
{

	if (isset($_POST['yaffleprofile-position']) && '' !== $_POST['yaffleprofile-position'])
	{
		$yp_position = sanitize_text_field($_POST['yaffleprofile-position']);
		update_term_meta($term_id, 'yaffleprofile-position', $yp_position);
	}
}

/* YAFFLE PROFILE - DEPARTMENT */

add_action('yaffleprofile_add_form_fields', 'add_department_field_yp', 10, 2);
add_action('created_yaffleprofile', 'save_department_meta_yp', 10, 2);
add_action('yaffleprofile_edit_form_fields', 'edit_department_meta_yp', 10, 2);
add_action('edited_yaffleprofile', 'update_department_meta_yp', 10, 2);

function add_department_field_yp($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="yaffleprofile-department">Department</label>
		<input type="text" name="yaffleprofile-department">
	</div>

	<?php
}

function save_department_meta_yp($term_id, $tt_id)
{
	if (isset($_POST['yaffleprofile-department']) && '' !== $_POST['yaffleprofile-department'])
	{
		$yp_department = sanitize_text_field($_POST['yaffleprofile-department']);
		add_term_meta($term_id, 'yaffleprofile-department', $yp_department, true);
	}
}

function edit_department_meta_yp($term, $taxonomy)
{

	$yp_department = get_term_meta($term->term_id, 'yaffleprofile-department', true);

	?>
	<tr class="form-field term-group-wrap">
		<th scope="row"><label for="yaffleprofile-department">Department</label></th>
		<td><input type="text" value="<?php echo $yp_department; ?>" name="yaffleprofile-department"></td>
	</tr>
	<?php
}
function update_department_meta_yp($term_id, $tt_id)
{

	if (isset($_POST['yaffleprofile-department']) && '' !== $_POST['yaffleprofile-department'])
	{
		$yp_department = sanitize_text_field($_POST['yaffleprofile-department']);
		update_term_meta($term_id, 'yaffleprofile-department', $yp_department);
	}
}
/* YAFFLE PROFILE - YAFFLE URL */

add_action('yaffleprofile_add_form_fields', 'add_url_field_yp', 10, 2);
add_action('created_yaffleprofile', 'save_url_meta_yp', 10, 2);
add_action('yaffleprofile_edit_form_fields', 'edit_url_meta_yp', 10, 2);
add_action('edited_yaffleprofile', 'update_url_meta_yp', 10, 2);

function add_url_field_yp($taxonomy)
{
	?>
	<div class="form-field term-group">
		<label for="yaffleprofile-url">URL</label>
		<input type="text" name="yaffleprofile-url">
	</div>

	<?php
}

function save_url_meta_yp($term_id, $tt_id)
{
	if (isset($_POST['yaffleprofile-url']) && '' !== $_POST['yaffleprofile-url'])
	{
		$yp_url = sanitize_text_field($_POST['yaffleprofile-url']);
		add_term_meta($term_id, 'yaffleprofile-url', $yp_url, true);
	}
}

function edit_url_meta_yp($term, $taxonomy)
{

	$yp_url = get_term_meta($term->term_id, 'yaffleprofile-url', true);

	?>
	<tr class="form-field term-group-wrap">
		<th scope="row"><label for="yaffleprofile-url">URL</label></th>
		<td><input type="text" value="<?php echo $yp_url; ?>" name="yaffleprofile-url"></td>
	</tr>
	<?php
}
function update_url_meta_yp($term_id, $tt_id)
{

	if (isset($_POST['yaffleprofile-url']) && '' !== $_POST['yaffleprofile-url'])
	{
		$yp_url = sanitize_text_field($_POST['yaffleprofile-url']);
		update_term_meta($term_id, 'yaffleprofile-url', $yp_url);
	}
}
/* ----------------------------- END YAFFLE PROFILE --------------*/

/*------ ADD GOALS TAXONOMY ---------*/
add_action('init', 'add_goals', 0);

function add_goals()
{
	$sc_tax = 'goals';
	$sc_type = 'post';
	$sc_args = array(
		'label'             => 'Goals',
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'capabilities'      => array(
			'manage_terms' => 'edit_others_posts',
			'edit_terms'   => 'edit_others_posts',
			'delete_terms' => 'edit_others_posts',
			'assign_terms' => 'edit_others_posts'
		),
	);
	register_taxonomy($sc_tax, $sc_type, $sc_args);
	register_taxonomy_for_object_type('goals', 'post');
}


/*------- CUSTOM POST TYPES ------------*/
add_action('init', 'cptui_register_my_cpts');
function cptui_register_my_cpts()
{
	$labels = array(
		"name"          => "External News Stories",
		"singular_name" => "External News Story",
		"add_new"       => "Add External News Story",
		"edit_item"     => "Edit External News Story",
		"new_item"      => "Add New External News Story",
		"add_new_item"  => "Add New External News Story",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => false,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => true,
		//"capability_type" => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "exnewsstory", "with_front" => true),
		"query_var"           => true,

		"supports"     => array("title", "custom-fields"),
		"taxonomies"   => array("source"),
		'capabilities' => array(
			'edit_post'          => 'edit_exnewsstory',
			'read_post'          => 'read_exnewsstory',
			'delete_post'        => 'delete_exnewsstory',
			'edit_posts'         => 'edit_exnewsstorys',
			'edit_others_posts'  => 'edit_others_exnewsstory',
			'publish_posts'      => 'publish_exnewsstory',
			'read_private_posts' => 'read_private_exnewsstory',
			'create_posts'       => 'create_exnewsstory',)
	);
	register_post_type("exnewsstory", $args);

	function add_exnewsstory_admin_caps()
	{
		// gets the administrator role
		$admins = get_role('administrator');

		$admins->add_cap('edit_exnewsstory');
		$admins->add_cap('edit_exnewsstorys');
		$admins->add_cap('edit_others_exnewsstory');
		$admins->add_cap('publish_exnewsstory');
		$admins->add_cap('read_exnewsstory');
		$admins->add_cap('read_private_exnewsstory');
		$admins->add_cap('delete_exnewsstory');
		$admins->add_cap('create_exnewsstory');
	}

	function add_exnewsstory_editor_caps()
	{
		// gets the administrator role
		$admins = get_role('editor');

		$admins->add_cap('edit_exnewsstory');
		$admins->add_cap('edit_exnewsstorys');
		$admins->add_cap('edit_others_exnewsstory');
		$admins->add_cap('publish_exnewsstory');
		$admins->add_cap('read_exnewsstory');
		$admins->add_cap('read_private_exnewsstory');
		$admins->add_cap('delete_exnewsstory');
		$admins->add_cap('create_exnewsstory');
	}

	add_action('admin_init', 'add_exnewsstory_admin_caps');
	add_action('admin_init', 'add_exnewsstory_editor_caps');

	$labels = array(
		"name"          => "Books at Memorial Posts",
		"singular_name" => "Books at Memorial Post",
		"add_new"       => "Add Books at Memorial Post",
		"edit_item"     => "Edit Books at Memorial Post",
		"new_item"      => "Add New Books at Memorial Post",
		"add_new_item"  => "Add New Books at Memorial Post",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => true,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => false,
		//"capability_type"     => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "research/books-at-memorial", "with_front" => true),
		"query_var"           => true,
		'taxonomies'          => array('category', 'post_tag','secondary_category'),
		"supports"            => array("title", "editor", "revisions", "thumbnail"),
	);
	register_post_type("books-at-memorial", $args);

	function add_bam_category_automatically($post_ID)
	{
		global $wpdb;
		if (!has_term('', 'category', $post_ID))
		{
			$cat = array(19);
			wp_set_object_terms($post_ID, $cat, 'category');
		}
	}

	add_action('publish_books-at-memorial', 'add_bam_category_automatically');

	function add_bam_admin_caps()
	{
		// gets the administrator role
		$admins = get_role('administrator');

		$admins->add_cap('edit_books-at-memorial');
		$admins->add_cap('edit_books-at-memorials');
		$admins->add_cap('edit_others_books-at-memorial');
		$admins->add_cap('publish_books-at-memorial');
		$admins->add_cap('read_books-at-memorial');
		$admins->add_cap('read_books-at-memorial');
		$admins->add_cap('delete_books-at-memorial');
		$admins->add_cap('create_books-at-memorial');
	}

	function add_bam_editor_caps()
	{
		// gets the administrator role
		$admins = get_role('editor');

		$admins->add_cap('edit_books-at-memorial');
		$admins->add_cap('edit_books-at-memorials');
		$admins->add_cap('edit_others_books-at-memorial');
		$admins->add_cap('publish_books-at-memorial');
		$admins->add_cap('read_books-at-memorial');
		$admins->add_cap('read_books-at-memorial');
		$admins->add_cap('delete_books-at-memorial');
		$admins->add_cap('create_books-at-memorial');
	}

	add_action('admin_init', 'add_bam_admin_caps');
	add_action('admin_init', 'add_bam_editor_caps');

	$labels = array(
		"name"          => "Obituaries",
		"singular_name" => "Obituary",
		"add_new"       => "Add Obituary",
		"edit_item"     => "Edit Obituary",
		"new_item"      => "Add New Obituary",
		"add_new_item"  => "Add New Obituary",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => true,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => false,
		//"capability_type" => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "campus-and-community/obituaries", "with_front" => true),
		"query_var"           => true,
		'taxonomies'          => array('category'),
		"supports"            => array("title", "editor"),
		'capabilities'        => array(
			'edit_post'          => 'edit_obituary',
			'read_post'          => 'read_obituary',
			'delete_post'        => 'delete_obituary',
			'edit_posts'         => 'edit_obituarys',
			'edit_others_posts'  => 'edit_others_obituary',
			'publish_posts'      => 'publish_obituary',
			'read_private_posts' => 'read_private_obituary',
			'create_posts'       => 'create_obituary',)
	);
	register_post_type("obituary", $args);

	function add_obituary_admin_caps()
	{
		// gets the administrator role
		$admins = get_role('administrator');

		$admins->add_cap('edit_obituary');
		$admins->add_cap('edit_obituarys');
		$admins->add_cap('edit_others_obituary');
		$admins->add_cap('publish_obituary');
		$admins->add_cap('read_obituary');
		$admins->add_cap('read_private_obituary');
		$admins->add_cap('delete_obituary');
		$admins->add_cap('create_obituary');
	}

	function add_obituary_editor_caps()
	{
		// gets the administrator role
		$admins = get_role('editor');

		$admins->add_cap('edit_obituary');
		$admins->add_cap('edit_obituarys');
		$admins->add_cap('edit_others_obituary');
		$admins->add_cap('publish_obituary');
		$admins->add_cap('read_obituary');
		$admins->add_cap('read_private_obituary');
		$admins->add_cap('delete_obituary');
		$admins->add_cap('create_obituary');
	}

	add_action('admin_init', 'add_obituary_admin_caps');
	add_action('admin_init', 'add_obituary_editor_caps');

	function add_obit_category_automatically($post_ID)
	{
		global $wpdb;
		if (!has_term('', 'category', $post_ID))
		{
			$cat = array(15);
			wp_set_object_terms($post_ID, $cat, 'category');
		}
	}

	add_action('publish_obituary', 'add_obit_category_automatically');


	$labels = array(
		"name"          => "Notable Posts",
		"singular_name" => "Notable Post",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => true,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => false,
		//"capability_type"     => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "campus-and-community/notable", "with_front" => true),
		"query_var"           => true,
		'taxonomies'          => array('category'),
		"supports"            => array("title", "editor", "custom-fields"),
	);
	register_post_type("notable", $args);

	function add_notable_category_automatically($post_ID)
	{
		global $wpdb;
		if (!has_term('', 'category', $post_ID))
		{
			$cat = array(15);
			wp_set_object_terms($post_ID, $cat, 'category');
		}
	}

	add_action('publish_notable', 'add_notable_category_automatically');

	$labels = array(
		"name"          => "Papers & Presentations",
		"singular_name" => "Papers & Presentations Post",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => true,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => false,
		//"capability_type"     => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "campus-and-community/papers-and-presentations", "with_front" => true),
		"query_var"           => true,
		'taxonomies'          => array('category'),
		"supports"            => array("title", "editor", "custom-fields"),
	);
	register_post_type("papers-presentations", $args);
	function add_pp_category_automatically($post_ID)
	{
		global $wpdb;
		if (!has_term('', 'category', $post_ID))
		{
			$cat = array(15);
			wp_set_object_terms($post_ID, $cat, 'category');
		}
	}

	add_action('publish_papers-presentations', 'add_pp_category_automatically');

	$labels = array(
		"name"          => "Events",
		"singular_name" => "Event",
		"add_new"       => "Add Event",
		"edit_item"     => "Edit Event",
		"new_item"      => "Add New Event",
		"add_new_item"  => "Add New Event",
	);

	$args = array(
		"labels"              => $labels,
		"description"         => "",
		"public"              => true,
		"show_ui"             => true,
		"has_archive"         => false,
		"show_in_menu"        => true,
		"exclude_from_search" => true,
		"capability_type"     => "post",
		"map_meta_cap"        => true,
		"hierarchical"        => false,
		"rewrite"             => array("slug" => "events", "with_front" => true),
		"query_var"           => true,
		//"supports" => array( "title", "editor", "thumbnail" ),	
		"supports"            => array("title", "thumbnail"),
	);
	register_post_type("event", $args);
}


/*-------- SPECIAL FEATURES ----------*/
$labels = array(
	"name"          => "Special Features",
	"singular_name" => "Special Feature",
	"add_new"       => "Add Special Feature",
	"edit_item"     => "Edit Special Feature",
	"new_item"      => "Add New Special Feature",
	"add_new_item"  => "Add New Special Feature",
);

$args = array(
	"labels"              => $labels,
	"description"         => "",
	"public"              => true,
	"show_ui"             => true,
	"has_archive"         => false,
	"show_in_menu"        => true,
	"exclude_from_search" => false,
	"capability_type" => "post",
	"map_meta_cap"        => true,
	"hierarchical"        => false,
	"rewrite"             => array("slug" => "special-features", "with_front" => true),
	"query_var"           => true,
	'taxonomies'          => array('category'),
	"supports"            => array("title","thumbnail","excerpt","page-attributes"),
	'capabilities'        => array(
		'edit_post'          => 'edit_special-feature',
		'read_post'          => 'read_special-feature',
		'delete_post'        => 'delete_special-feature',
		'edit_posts'         => 'edit_special-features',
		'edit_others_posts'  => 'edit_others_special-feature',
		'publish_posts'      => 'publish_special-feature',
		'read_private_posts' => 'read_private_special-feature',
		'create_posts'       => 'create_special-feature',)
);
register_post_type("special-feature", $args);

function add_special_feature_admin_caps()
{
	// gets the administrator role
$admins = get_role('administrator');

		$admins->add_cap('edit_special-feature');
		$admins->add_cap('edit_special-features');
		$admins->add_cap('edit_others_special-feature');
		$admins->add_cap('publish_special-feature');
		$admins->add_cap('read_special-feature');
		$admins->add_cap('read_private_special-feature');
		$admins->add_cap('delete_special-feature');
		$admins->add_cap('create_special-feature');
	}

	function add_special_feature_editor_caps()
{
	// gets the administrator role
$admins = get_role('editor');

		$admins->add_cap('edit_special-feature');
		$admins->add_cap('edit_special-features');
		$admins->add_cap('edit_others_special-feature');
		$admins->add_cap('publish_special-feature');
		$admins->add_cap('read_special-feature');
		$admins->add_cap('read_private_special-feature');
		$admins->add_cap('delete_special-feature');
		$admins->add_cap('create_special-feature');
	}

	add_action('admin_init', 'add_special_feature_admin_caps');
	add_action('admin_init', 'add_special_feature_editor_caps');
/*----------- PHOTO CREDIT ---------*/

function add_image_attachment_fields_to_edit($form_fields, $post)
{
	$caption_field='';
	$image_url_field='';

	// Add Caption before Credit field 
	$form_fields['post_excerpt'] = $caption_field;

	// Add a Credit field
	$form_fields["credit_text"] = array(
		"label" => __("Credit"),
		"input" => "Submitted", // this is default if "input" is omitted
		"value" => esc_attr(get_post_meta($post->ID, "_credit_text", true)),
		"helps" => __("The author of the image."),
	);


	// Add Caption before Credit field 
	$form_fields['image_url'] = $image_url_field;

	return $form_fields;
}

add_filter("attachment_fields_to_edit", "add_image_attachment_fields_to_edit", null, 2);

/**
 * Save custom media metadata fields
 *
 * Be sure to validate your data before saving it
 * http://codex.wordpress.org/Data_Validation
 *
 * @param $post The $post data for the attachment
 * @param $attachment The $attachment part of the form $_POST ($_POST[attachments][postID])
 *
 * @return $post
 */

function add_image_attachment_fields_to_save($post, $attachment)
{
	if (isset($attachment['credit_text']))
	{
		update_post_meta($post['ID'], '_credit_text', esc_attr($attachment['credit_text']));
	}
	else{
		update_post_meta($post['ID'], '_credit_text', 'Submitted');
	}


	return $post;
}

add_filter("attachment_fields_to_save", "add_image_attachment_fields_to_save", null, 2);


/*CAPTION SHORTCODE */
function gazette_caption($val, $attr, $content = null)
{
	extract(shortcode_atts(array(
		'id'      => '',
		'align'   => '',
		'width'   => '',
		'caption' => ''
	), $attr));

	if (1 > (int) $width || empty($caption))
		return $val;
	$cid = str_replace('attachment_', '', $id);
	if (get_post_meta($cid, '_credit_text')){
	$credit = get_post_meta($cid, '_credit_text');

		if ($credit[0] != '')
		{
			$re_credit = "<div class='photo-credit'><span class='icon-camera'></span> Photo: " . $credit[0] . "</div>";
		}
		else {
			$re_credit = '';
		}
	}else {
		$re_credit = '';
	}


	$capid = '';
	if ($id)
	{
		$id = esc_attr($id);
		$capid = 'id="figcaption_' . $id . '" ';
		$id = 'id="' . $id . '" aria-labelledby="figcaption_' . $id . '" ';
	}

	return '<figure ' . $id . 'class="wp-caption ' . esc_attr($align) . '" style="width: '
	. ((int) $width) . 'px;max-width:100%;">' . do_shortcode($content) . '<figcaption ' . $capid
	. 'class="wp-caption-text">' . $caption . " " . $re_credit . '</figcaption></figure>';


}
add_filter('img_caption_shortcode', 'gazette_caption', 10, 3 );

/*----------- EDITOR CUSTOMIZATIONS ----*/

/* Filter */
add_filter( 'tiny_mce_before_init', 'my_wpeditor_formats_options' );

/**
 * Add Dropcap option but keep the defaults.
 */
function my_wpeditor_formats_options( $settings ){

    /* Default Style Formats */
    $default_style_formats = array(
    	array(
                    'title'   => 'Section Heading 1',
                    'format'  => 'h3',
                ),
    	array(
                    'title'   => 'Section Heading 2',
                    'format'  => 'h4',
                ),
    	array(
                    'title'   => 'Paragraph',
                    'format'  => 'p',
                ),

    );

    /* Our Own Custom Options */
    $custom_style_formats = array(
      array(
			'title'   => 'Name Highlight',
			'inline'  => 'span',
			'classes' => 'name-highlight',
			'wrapper' => true,

		),
		array(
			'title'   => 'Headline Text',
			'inline'  => 'span',
			'classes' => 'headline-text',
			'wrapper' => true,

		),
		array(
			'title'   => 'Blockquote Author',
			'inline'  => 'span',
			'classes' => 'blockquote-author',
			'wrapper' => true,

		)
    );

    /* Merge It */
    $new_style_formats = array_merge( $default_style_formats, $custom_style_formats );

    /* Add it in tinymce config as json data */
    $settings['style_formats'] = json_encode( $new_style_formats );
    return $settings;
}


// ADD EDITOR STYLESHEET

function my_theme_add_editor_styles()
{
	add_editor_style('custom-editor-style.css');
}

add_action('init', 'my_theme_add_editor_styles');


/*
 * Modifying TinyMCE editor to remove unused items.
 */
function customformatTinyMCE($init) {
	// Add block format elements you want to show in dropdown
	$init['theme_advanced_blockformats'] = 'p,h3,h4';

	return $init;
}

// Modify Tiny_MCE init
add_filter('tiny_mce_before_init', 'customformatTinyMCE' );
/*----------- END EDITOR CUSTOMIZATIONS ----*/

/*--------PULL QUOTE BUTTON --------*/

add_action('admin_init', 'my_tinymce_button');

function my_tinymce_button()
{
	if (current_user_can('edit_posts'))
	{
		add_filter('mce_buttons', 'my_register_tinymce_button');
		add_filter('mce_external_plugins', 'my_add_tinymce_button');
	}
}

function my_register_tinymce_button($buttons)
{
	// array_push( $buttons, "button_add_pullquote" );
	array_push($buttons, "button_wrap_image_info");
	array_push($buttons, "button_add_photoessay");

	return $buttons;
}

function my_add_tinymce_button($plugin_array)
{

	$plugin_array['my_button_script'] = get_template_directory_uri() . '/js/tinyButtons.js';

	return $plugin_array;
}


/*--------END PULL QUOTE BUTTON --------*/

/*---------TURN OFF WP IMAGE CAPTIONS ______*/
/*function no_caption($deprecated, $attr, $content) { return $content; };
add_filter('img_caption_shortcode', 'no_caption', 10, 3);*/
/*---------END TURN OFF WP IMAGE CAPTIONS------*/


/*------- DISABLE READ MORE BUTTON FROM TINYMCE */

function myplugin_tinymce_buttons($buttons)
{
	//Remove the text color selector
	$remove = 'wp_more';

	//Find the array key and then unset
	if (($key = array_search($remove, $buttons)) !== false)
		unset($buttons[$key]);

	return $buttons;
}

add_filter('mce_buttons', 'myplugin_tinymce_buttons');


/*--------------- REPLACE POSTS LABELS WITH "STORIES" ----------- */
function change_post_menu_label()
{
	global $menu;
	global $submenu;
	$menu[5][0] = 'Stories';
	$submenu['edit.php'][5][0] = 'Stories';
	$submenu['edit.php'][10][0] = 'Add New';
	echo '';
}

function change_post_object_label()
{
	global $wp_post_types;
	$labels = &$wp_post_types['post']->labels;
	$labels->name = 'Stories';
	$labels->singular_name = 'Story';
	$labels->add_new = 'Add Story';
	$labels->add_new_item = 'Add Story';
	$labels->edit_item = 'Edit Story';
	$labels->new_item = 'Story';
	$labels->view_item = 'View Story';
	$labels->search_items = 'Search Stories';
	$labels->not_found = 'No Stories found';
	$labels->not_found_in_trash = 'No Stories found in Trash';
}

add_action('init', 'change_post_object_label');
add_action('admin_menu', 'change_post_menu_label');


/*------ LIMIT CONTRIBUTORS TO OWN POSTS ---------*/

function posts_for_current_author($query)
{
	global $pagenow;

	if ('edit.php' != $pagenow || !$query->is_admin)
		return $query;

	if (!current_user_can('edit_others_posts'))
	{
		global $user_ID;
		$query->set('author', $user_ID);
	}

	return $query;
}

add_filter('pre_get_posts', 'posts_for_current_author');

/*-------------------- HIDE META BOXES ---------------*/

if (is_admin()) :
	function my_remove_meta_boxes()
	{
		if (!current_user_can('edit_others_posts'))
		{
			remove_meta_box('ef_editorial_meta', 'post', 'normal');
			remove_meta_box('tagsdiv-goals', 'post', 'normal');
			remove_meta_box('expirationdatediv', 'post', 'normal');
			remove_meta_box('post_status_widget', 'dashboard', 'normal');
			remove_meta_box('submissions_pending_review_dashboard_widget', 'dashboard', 'normal');

		}
		remove_meta_box('ef_editorial_meta', 'post', 'normal');
		remove_meta_box('theme_groupdiv', 'post', 'normal');
		remove_meta_box('categorydiv', 'post', 'normal');
		remove_meta_box('categorydiv', 'books-at-memorial', 'normal');
		remove_meta_box('categorydiv', 'papers-presentations', 'normal');
		remove_meta_box('categorydiv', 'obituary', 'normal');
		remove_meta_box('categorydiv', 'notable', 'normal');
		remove_meta_box('tagsdiv-post_tag', 'post', 'normal');
		remove_meta_box('tagsdiv-post_tag', 'books-at-memorial', 'normal');
		remove_meta_box('authordiv', 'post', 'normal');
		remove_meta_box('tagsdiv-contributors', 'post', 'normal');
		remove_meta_box('tagsdiv-yaffleprofile', 'post', 'normal');
		remove_meta_box('secondary_categorydiv', 'post', 'normal');
	}


	add_action('admin_menu', 'my_remove_meta_boxes');
endif;

/*--------- REMOVE QUICK EDIT FOR NON ADMIN USERS -------*/

function remove_quick_edit($actions)
{
	unset($actions['inline hide-if-no-js']);

	return $actions;
}

if (!(current_user_can('edit_others_posts')))
{
	add_filter('post_row_actions', 'remove_quick_edit', 10, 1);
}

//Allow Contributors to Add Media
if (current_user_can('contributor') && !current_user_can('upload_files')):
	add_action('admin_init', 'allow_contributor_uploads');

	function allow_contributor_uploads()
	{
		$contributor = get_role('contributor');
		$contributor->add_cap('upload_files');
	}
endif;

//Redirect contributor after submission
if (!current_user_can('edit_pages') && (isset($_POST['save']) || isset($_POST['publish'])))
{
	add_filter('redirect_post_location', 'redirect_to_new_post');
	function redirect_to_new_post()
	{
		wp_redirect('edit.php');
	}
}


function add_custom_query_var($vars)
{
	/*$vars[] = "year";
	$vars[] = "month";*/
	$vars[] = "obit_year";
	$vars[] = "obit_month";
	$vars[] = "notable_year";
	$vars[] = "notable_month";
	$vars[] = "pp_year";
	$vars[] = "pp_month";
	$vars[] = "archive_date";
	$vars[] = "archive_cat";

	//$vars[] = "archive_month";
	return $vars;
}

add_filter('query_vars', 'add_custom_query_var');


/**
 * API
 */
function set_custom_controller_path()
{
	return get_stylesheet_directory() . "/apicustom.php";
}

add_filter('json_api_custom_controller_path', 'set_custom_controller_path');


/*------ EDIT DATE --------*/

function edit_date($datestr)
{
	$reg = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",);
	$rep = array("Jan.", "Feb.", "March", "April", "May", "June", "July", "Aug.", "Sept.", "Oct.", "Nov.", "Dec.");
	$newdate = str_replace($reg, $rep, $datestr);
	echo $newdate;
}
function event_edit_date($datestr)
{
	$reg = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",);
	$rep = array("Jan.", "Feb.", "March", "April", "May", "June", "July", "Aug.", "Sept.", "Oct.", "Nov.", "Dec.");
	$newdate = str_replace($reg, $rep, $datestr);
	return $newdate;
}

function edit_time($start, $end)
{
	$spm = strpos($start, 'pm');
	$epm = strpos($end, 'pm');
	$sam = strpos($start, 'am');
	$eam = strpos($end, 'am');
	$nspace=0;
	if (!($spm === false) && !($epm === false))
	{
		$e_start = str_replace('pm','', $start);
		$e_end = str_replace('pm', 'p.m.', $end);
		$nspace = 1;
	} elseif (!($sam === false) && !($eam === false))
	{

		$e_start = str_replace('am','', $start);
		$e_end = str_replace('am','a.m.', $end);
		$nspace = 1;
	} else
	{
		$f = array('am','pm');
		$r = array('a.m.','p.m.');

		$e_start = str_replace($f, $r, $start);
		$e_end = str_replace($f, $r, $end);
	}
	$e_start = str_replace(':00', '', $e_start);
	$e_end = str_replace(':00', '', $e_end);
	if($nspace == 1){
		$ret = trim($e_start) . '-' . trim($e_end);

	}
	else
	{
		$ret = $e_start . '-' . $e_end;
		}

	return $ret;
}

/**
 * Submission Pending Review Dashboard Widget
 * editors see form submissions from website pending review on dashboard
 */
function submissions_pending_review_dashboard_widget() {
	wp_add_dashboard_widget(
		'submissions_pending_review_dashboard_widget',         // Widget slug.
		'Submissions Pending Review',         // Title.
		'submissions_pending_review_widget_function' // Display function.
	);
}
add_action( 'wp_dashboard_setup', 'submissions_pending_review_dashboard_widget');
function submissions_pending_review_widget_function() {
	$pending_submissions = array(
		'event' => array(
			'num' => wp_count_posts('event')->pending,
			'label' => 'Events'
		),
		'papers-presentations' => array(
			'num' => wp_count_posts('papers-presentations')->pending,
			'label' => 'Papers & Presentations'
		),
		'notable' => array(
			'num' => wp_count_posts('notable')->pending,
			'label' => 'Notable Posts'
		)
	);
	?>
	<table id="pending-review-table" cellpadding="2">
		<tbody>
		<?php
		foreach($pending_submissions as $key=>$p){
		?>
			<tr>
				<td class="b">
					<a href="<?php echo site_url(); ?>/wp-admin/edit.php?post_status=pending&post_type=<?php echo $key; ?>"><?php echo $p['num'] ?></a>
				</td>
				<td>
					<a href="<?php echo site_url(); ?>/wp-admin/edit.php?post_status=pending&post_type=<?php echo $key; ?>"><?php echo $p['label'] ?></a>
				</td>
			</tr>
		<?php
		} ?>
		</tbody>
	</table>
	<?php
}


/*------- disable srcset--- */

// disable srcset on frontend
add_filter('max_srcset_image_width', create_function('', 'return 1;'));

// disable 768px image generation
function shapeSpace_customize_image_sizes($sizes) {
	unset($sizes['medium_large']);
	return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'shapeSpace_customize_image_sizes');

/**
 * Change author metatag
 */
function add_author_meta() {
	if (is_single()) {
		global $post;
		$author = get_the_author_meta('user_nicename', $post->post_author);

		if ($contributor = wp_get_post_terms($post->ID, 'contributors')) {
			//echo "<pre>"; var_dump($contributor); echo "</pre>";
			if(count($contributor) > 1){
				$contributors = array();
				for($i=0; $i<count($contributor); $i++){
					$contributors[]['name'] = $contributor[$i]->name;
				}
				//echo "<pre>"; var_dump($contributors); echo "</pre>";
				$separator = (count($contributors) > 2) ? ', ' : ' and ';
				$contributors_meta ='';
				for($i=0;$i<count($contributors);$i++){
					$contributors_meta .= $contributors[$i]['name'];
					$contributors_meta .= ($i < count($contributors)-1) ? $separator : '';
				}
				echo "<meta name=\"author\" content=\"" . $contributors_meta . "\">";
			}
			else{
				echo "<meta name=\"author\" content=\"" . $contributor[0]->name . "\">";
			}
		}
		else{
			echo "<meta name=\"author\" content=\"$author\">";
		}
	}
}
add_action( 'wp_enqueue_scripts', 'add_author_meta' );





function acf_set_featured_image( $value, $post_id, $field  ){

	if($value != ''){
		//Add the value which is the image ID to the _thumbnail_id meta data for the current post
		add_post_meta($post_id, '_thumbnail_id', $value);
	}

	return $value;
}

// acf/update_value/name={$field_name} - filter for a specific field based on it's name
add_filter('acf/update_value/name=feature_image', 'acf_set_featured_image', 10, 3);

function add_query_vars_filter( $vars ){
	$vars[] = "obit_year";
	$vars[] = "obit_month";
	return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

// Remove anything that looks like an archive title prefix ("Archive:", "Foo:", "Bar:").
add_filter('get_the_archive_title', function ($title) {
	return preg_replace('/^\w+: /', '', $title);
});

//JETPACK QUALITY ADJUSTMENT
add_filter('jetpack_photon_pre_args', 'jetpackme_custom_photon_compression' );
function jetpackme_custom_photon_compression( $args ) {
	$args['quality'] = 100;
	return $args;
}

//TURN OFF ACF HIDE CUSTOM FIELDS

add_filter('acf/settings/remove_wp_meta_box', '__return_false');

