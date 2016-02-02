<?php
/**
 * Start output buffering
 * This is needed for redirecting to post when a new post has made
 * @since 0.8
 */
function auiu_buffer_start() {
    ob_start();
}add_action( 'init', 'auiu_buffer_start' );
/**
 * Format error message
 *
 * @param array $error_msg
 * @return string
 */
function auiu_error_msg( $error_msg ) {
    $msg_string = '';
    foreach ($error_msg as $value) {
        if ( !empty( $value ) ) {
            $msg_string = $msg_string . '<div class="error">' . $msg_string = $value . '</div>';
        }
    }
    return $msg_string;
}// for the price field to make only numbers, periods, and commas
function auiu_clean_tags( $string ) {
    $string = preg_replace( '/\s*,\s*/', ',', rtrim( trim( $string ), ' ,' ) );
    return $string;
}/**
 * Validates any integer variable and sanitize
 *
 * @param int $int
 * @return intger
 */
function auiu_is_valid_int( $int ) {
    $int = isset( $int ) ? intval( $int ) : 0;
    return $int;
}/**
 * Notify the admin for new post
 *
 * @param object $userdata
 * @param int $post_id
 */
function auiu_notify_post_mail( $user, $post_id ) {
    $blogname = get_bloginfo( 'name' );
    $to = get_bloginfo( 'admin_email' );
    $headers = sprintf( "From: %s <%s>\r\n", $blogname, $to );
    $subject = sprintf( __( '[%s] New Post Submission' ), $blogname );
    $msg = sprintf( __( 'A new post has been submitted on %s' ), $blogname ) . "\r\n\r\n";
    $msg .= sprintf( __( 'Title: %s' ), get_the_title( $post_id ) ) . "\r\n";
	if ( auiu_get_option( 'post_status', 'auiu_frontend_posting', 'publish' ) == 'publish' ) {
        $permalink = get_permalink( $post_id );
		$msg .= sprintf( __( 'Permalink: %s' ), $permalink ) . "\r\n";
    }
	else {
		$pendstatus = 'Post is available under "Posts" for your approval.';
		$msg .= sprintf( __( 'Post Status: %s' ), $pendstatus ) . "\r\n";
	}
	if ( $_POST['cf_uploadername'] ) {
		$uploadername = $_POST['cf_uploadername'];
		$msg .= sprintf( __( 'Uploader Name: %s' ), $uploadername ) . "\r\n";
	}
	if ( $_POST['cf_uploaderemail'] ) {
		$uploaderemail = $_POST['cf_uploaderemail'];
		$msg .= sprintf( __( 'Uploader Email: %s' ), $uploaderemail ) . "\r\n";
	}
    //plugin api
    $to = apply_filters( 'auiu_notify_to', $to );
    $subject = apply_filters( 'auiu_notify_subject', $subject );
    $msg = apply_filters( 'auiu_notify_message', $msg );
    wp_mail( $to, $subject, $msg, $headers );
}/**
 * Adds/Removes mime types to wordpress
 *
 * @param array $mime original mime types
 * @return array modified mime types
 */
function auiu_mime( $mime ) {
    $unset = array('exe', 'swf', 'tsv', 'wp|wpd', 'onetoc|onetoc2|onetmp|onepkg', 'class', 'htm|html', 'mdb', 'mpp');
    foreach ($unset as $val) {
        unset( $mime[$val] );
    }
    return $mime;
}add_filter( 'upload_mimes', 'auiu_mime' );
/**
 * Upload the files to the post as attachemnt
 *
 * @param <type> $post_id
 */
function auiu_upload_attachment( $post_id ) {
    if ( !isset( $_FILES['auiu_post_attachments'] ) ) {
        return false;
    }
    $fields = (int) auiu_get_option( 'attachment_num', 'auiu_frontend_posting' );
    for ($i = 0; $i < $fields; $i++) {
        $file_name = basename( $_FILES['auiu_post_attachments']['name'][$i] );
        if ( $file_name ) {
            if ( $file_name ) {
                $upload = array(
                    'name' => $_FILES['auiu_post_attachments']['name'][$i],
                    'type' => $_FILES['auiu_post_attachments']['type'][$i],
                    'tmp_name' => $_FILES['auiu_post_attachments']['tmp_name'][$i],
                    'error' => $_FILES['auiu_post_attachments']['error'][$i],
                    'size' => $_FILES['auiu_post_attachments']['size'][$i]
                );
                auiu_upload_file( $upload );
            }//file exists
        }// end for
    }
}/**
 * Generic function to upload a file
 *
 * @since 0.8
 * @param string $field_name file input field name
 * @return bool|int attachment id on success, bool false instead
 */
function auiu_upload_file( $upload_data ) {
	$uploaded_file = wp_handle_upload( $upload_data, array('test_form' => false) );
	// If the wp_handle_upload call returned a local path for the image
    if ( isset( $uploaded_file['file'] ) ) {
        $file_loc = $uploaded_file['file'];
        $file_name = basename( $upload_data['name'] );
        $file_type = wp_check_filetype( $file_name );
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
            'post_content' => '',
            'post_status' => 'inherit'
        );
	    $attach_id = wp_insert_attachment( $attachment, $file_loc );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_loc );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return $attach_id;
    }
    return false;
}/**
 * Checks the submitted files if has any errors
 *
 * @return array error list
 */
function auiu_check_upload() {
    $errors = array();
    $mime = get_allowed_mime_types();
    $size_limit = (int) (auiu_get_option( 'attachment_max_size', 'auiu_frontend_posting' ) * 1024);
    $fields = (int) auiu_get_option( 'attachment_num', 'auiu_frontend_posting' );
    for ($i = 0; $i < $fields; $i++) {
        $tmp_name = basename( $_FILES['auiu_post_attachments']['tmp_name'][$i] );
        $file_name = basename( $_FILES['auiu_post_attachments']['name'][$i] );
        //if file is uploaded
        if ( $file_name ) {
            $attach_type = wp_check_filetype( $file_name );
            $attach_size = $_FILES['auiu_post_attachments']['size'][$i];
            //check file size
            if ( $attach_size > $size_limit ) {
                $errors[] = __( "Attachment file is too big" );
            }
            //check file type
            if ( !in_array( $attach_type['type'], $mime ) ) {
                $errors[] = __( "Invalid attachment file type" );
            }
        } // if $filename
    }// endfor
    return $errors;
}/**
 * Get the attachments of a post
 *
 * @param int $post_id
 * @return array attachment list
 */
function auiu_get_attachments( $post_id ) {
    $att_list = array();
    $args = array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => null,
        'post_parent' => $post_id,
        'order' => 'ASC',
        'orderby' => 'menu_order'
    );
    $attachments = get_posts( $args );
    foreach ($attachments as $attachment) {
        $att_list[] = array(
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'url' => wp_get_attachment_url( $attachment->ID ),
            'mime' => $attachment->post_mime_type
        );
    }
    return $att_list;
}
/**
 * Attachments preview on edit page
 *
 * @param int $post_id
 */
function auiu_edit_attachment( $post_id ) {
    $attach = auiu_get_attachments( $post_id );
    if ( $attach ) {
        $count = 1;
        foreach ($attach as $a) {
            echo 'Attachment ' . $count . ': <a href="' . $a['url'] . '">' . $a['title'] . '</a>';
            echo "<form name=\"auiu_edit_attachment\" id=\"auiu_edit_attachment_{$post_id}\" action=\"\" method=\"POST\">";
            echo "<input type=\"hidden\" name=\"attach_id\" value=\"{$a['id']}\" />";
            echo "<input type=\"hidden\" name=\"action\" value=\"del\" />";
            wp_nonce_field( 'auiu_attach_del' );
            echo '<input class="auiu_attachment_delete" type="submit" name="auiu_attachment_delete" value="delete" onclick="return confirm(\'Are you sure to delete this attachment?\');">';
            echo "</form>";
            echo "<br>";
            $count++;
        }
    }
}
function auiu_attachment_fields( $edit = false, $post_id = false ) {
    if ( auiu_get_option( 'allow_attachment', 'auiu_frontend_posting', 'no' ) == 'yes' ) {
        $fields = (int) auiu_get_option( 'attachment_num', 'auiu_frontend_posting', 0 );
        if ( $edit && $post_id ) {
            $fields = abs( $fields - count( auiu_get_attachments( $post_id ) ) );
        }
        for ($i = 0; $i < $fields; $i++) {
            ?>
            <li>
                <label for="auiu_post_attachments">
                    Attachment <?php echo $i + 1; ?>:
                </label>
                <input type="file" name="auiu_post_attachments[]">
                <div class="clear"></div>
            </li>
            <?php
        }
    }
}
/**
 * Remove the mdedia upload tabs from subscribers
 *
 * @package WP User Frontend
 * @author Tareq Hasan
 */
function auiu_unset_media_tab( $list ) {
    if ( !current_user_can( 'edit_posts' ) ) {
        unset( $list['library'] );
        unset( $list['gallery'] );
    }
    return $list;
}
add_filter( 'media_upload_tabs', 'auiu_unset_media_tab' );
/**
 * Get the registered post types
 *
 * @return array
 */
function auiu_get_post_types() {
    $post_types = get_post_types();
    foreach ($post_types as $key => $val) {
        if ( $val == 'attachment' || $val == 'revision' || $val == 'nav_menu_item' ) {
            unset( $post_types[$key] );
        }
    }
    return $post_types;
}
function auiu_get_taxonomies() {
    $cats = get_taxonomies(array('public'=>true));
    return $cats;
}
function auiu_get_cats() {
	$default_taxonomy = get_option('auiu_frontend_posting');
	if ( !empty( $default_taxonomy ) ) {
		$cats = get_terms( $default_taxonomy['default_taxonomy'],array('hide_empty' => false) );
		$list = array();
		if ( $cats ) {
			foreach ($cats as $cat) {
				$list[$cat->term_id] = $cat->name;
			}
		}
		return $list;
	}	
}
/**
 * Get lists of users from database
 *
 * @return array
 */
function auiu_list_users() {
    global $wpdb;
    $users = $wpdb->get_results( "SELECT ID, user_login from $wpdb->users" );
    $list = array();
    if ( $users ) {
        foreach ($users as $user) {
            $list[$user->ID] = $user->user_login;
        }
    }
    return $list;
}
/**
 * Find the string that starts with defined word
 *
 * @param string $string
 * @param string $starts
 * @return boolean
 */
function auiu_starts_with( $string, $starts ) {
    $flag = strncmp( $string, $starts, strlen( $starts ) );
    if ( $flag == 0 ) {
        return true;
    } else {
        return false;
    }
}
/**
 * Retrieve or display list of posts as a dropdown (select list).
 *
 * @return string HTML content, if not displaying.
 */
function auiu_get_pages() {
    global $wpdb;
    $array = array();
    $pages = get_pages();
    if ( $pages ) {
        foreach ($pages as $page) {
            $array[$page->ID] = $page->post_title;
        }
    }
    return $array;
}
/**
 * Edit post link for frontend
 *
 * @since 0.7
 * @param string $url url of the original post edit link
 * @param int $post_id
 * @return string url of the current edit post page
 */
function auiu_edit_post_link( $url, $post_id ) {
    if ( is_admin() ) {
        return $url;
    }
    $override = auiu_get_option( 'override_editlink', 'auiu_others', 'no' );
    if ( $override == 'yes' ) {
        $url = '';
        if ( auiu_get_option( 'enable_post_edit', 'auiu_others', 'yes' ) == 'yes' ) {
            $edit_page = (int) auiu_get_option( 'edit_page_id', 'auiu_others' );
            $url = get_permalink( $edit_page );
            $url = wp_nonce_url( $url . '?pid=' . $post_id, 'auiu_edit' );
        }
    }
    return $url;
}
add_filter( 'get_edit_post_link', 'auiu_edit_post_link', 10, 2 );
/**
 * Add two custom fields when custom fields are enabled.
 */
function auiu_uploadername () {
	global $wpdb;
	$auiu_table = $wpdb->prefix . 'auiu_customfields';
	$results = $wpdb->get_row( "SELECT * FROM $auiu_table WHERE field = 'cf_uploadername'", 0, 0);
    if ( empty( $results ) ) {
		$wpdb->insert ( $auiu_table,
			array(
				'id' => 1,
				'field' => 'cf_uploadername',
				'type' => 'text',
				'values' => '',
				'label' => 'Your Name',
				'desc' => 'Please fill in your name',
				'required' => 'yes',
				'region' => 'top',
				'order' => 0
			)
		);
	}	
}	
function auiu_uploaderemail () {
	global $wpdb;
	$auiu_table = $wpdb->prefix . 'auiu_customfields';
	$results = $wpdb->get_row( "SELECT * FROM $auiu_table WHERE field = 'cf_uploaderemail'", 0, 0);
	if ( empty( $results ) ) {
		$wpdb->insert ( $auiu_table,
			array(
				'id' => 2,
				'field' => 'cf_uploaderemail',
				'type' => 'text',
				'values' => '',
				'label' => 'Your Email',
				'desc' => 'Please fill in your Email address',
				'required' => 'yes',
				'region' => 'top',
				'order' => 1
			)
		);
	}	
}		   

function auiu_uploaderagree () {
	global $wpdb;
	$auiu_table = $wpdb->prefix . 'auiu_customfields';
    $results = $wpdb->get_row( "SELECT * FROM $auiu_table WHERE field = 'cf_agreed'", 0, 0);
    if ( empty( $results ) ) {
		$wpdb->insert ( $auiu_table,
			array(
				'id' => 3,
				'field' => 'cf_agreed',
				'type' => 'checkbox',
				'values' => 'Yes',
				'label' => 'Yes I Agree',
				'desc' => 'I certify that by submitting the above information and photograph that I have first read and hereby agree to be bound by the Term of Use Agreement.',
				'required' => 'yes',
				'region' => 'bottom',
				'order' => 0,
			)
		);
	}	
}		   		
function auiu_delete_uploadername () {
	global $wpdb;
 	$auiu_table = $wpdb->prefix . 'auiu_customfields';
	$wpdb->delete ( $auiu_table,
		array(
			'id' => 1,
		)
	);
}
function auiu_delete_uploaderemail () {	
	global $wpdb;
 	$auiu_table = $wpdb->prefix . 'auiu_customfields';
	$wpdb->delete ( $auiu_table,
		array(
			'id' => 2,
		)
	);
}
function auiu_delete_uploaderagree () {	
	global $wpdb;
 	$auiu_table = $wpdb->prefix . 'auiu_customfields';
	$wpdb->delete ( $auiu_table,
		array(
			'id' => 3,
		)
	);
}
/**
 * Shows the custom field data and attachments to the post
 *
 * @global object $wpdb
 * @global object $post
 * @param string $content
 * @return string
 */
function auiu_show_meta_front( $content ) {
    global $wpdb, $post;
    //check, if custom field is enabled
    $enabled = auiu_get_option( 'enable_custom_field', 'auiu_frontend_posting' );
    $show_custom = auiu_get_option( 'cf_show_front', 'auiu_others' );
    $show_attachment = auiu_get_option( 'att_show_front', 'auiu_others' );
    if ( $enabled == 'on' && $show_custom == 'on' ) {
		
        $extra = '';
        $fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}auiu_customfields ORDER BY `region` DESC", OBJECT );
        if ( $wpdb->num_rows > 0 ) {
            $extra .= '<ul class="auiu_customs">';
            foreach ($fields as $field) {
                $meta = get_post_meta( $post->ID, $field->field, true );
                if ( $meta ) {
                    $extra .= sprintf( '<li><label>%s</label> : %s</li>', $field->label, make_clickable( $meta ) );
                }
            }
            $extra .= '<ul>';
            $content .= $extra;
        }
    }
    if ( $show_attachment == 'on' ) {
        $attach = '';
        $attachments = auiu_get_attachments( $post->ID );
        if ( $attachments ) {
            $attach = '<ul class="auiu-attachments">';
            foreach ($attachments as $file) {
                //if the attachment is image, show the image. else show the link
                if ( auiu_is_file_image( $file['url'], $file['mime'] ) ) {
                    $thumb = wp_get_attachment_image_src( $file['id'] );
                    $attach .= sprintf( '<li><a href="%s"><img src="%s" alt="%s" /></a></li>', $file['url'], $thumb[0], esc_attr( $file['title'] ) );
                } else {
                    $attach .= sprintf( '<li><a href="%s" title="%s">%s</a></li>', $file['url'], esc_attr( $file['title'] ), $file['title'] );
                }
            }
            $attach .= '</ul>';
        }
        if ( $attach ) {
            $content .= $attach;
        }
    }
    return $content;
}
add_filter( 'the_content', 'auiu_show_meta_front' );
/**
 * Check if the file is a image
 *
 * @param string $file url of the file to check
 * @param string $mime mime type of the file
 * @return bool
 */
function auiu_is_file_image( $file, $mime ) {
    $ext = preg_match( '/\.([^.]+)$/', $file, $matches ) ? strtolower( $matches[1] ) : false;
    $image_exts = array('jpg', 'jpeg', 'gif', 'png');
    if ( 'image/' == substr( $mime, 0, 6 ) || $ext && 'import' == $mime && in_array( $ext, $image_exts ) ) {
        return true;
    }
    return false;
}
/**
 * Displays attachment information upon upload as featured image
 *
 * @param int $attach_id attachment id
 * @return string
 */
function auiu_feat_img_html( $attach_id ) {
    $image = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
    $post = get_post( $attach_id );
    $html = sprintf( '<div class="auiu-item" id="attachment-%d">', $attach_id );
    $html .= sprintf( '<img src="%s" alt="%s" />', $image[0], esc_attr( $post->post_title ) );
    $html .= sprintf( '<a class="auiu-del-ft-image button" href="#" data-id="%d"><button>%s</button></a> ', $attach_id, __( 'Remove Image', 'auiu' ) );
    $html .= sprintf( '<input type="hidden" name="auiu_featured_img" value="%d" />', $attach_id );
    $html .= '</div>';
    return $html;
}
/**
 * Category checklist walker
 *
 * @since 0.8
 */
class AUIU_Walker_Category_Checklist extends Walker {
    var $tree_type = 'category';
    var $db_fields = array('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this
    function start_lvl( &$output, $depth, $args ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent<ul class='children'>\n";
    }
    function end_lvl( &$output, $depth, $args ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent</ul>\n";
    }
    function start_el( &$output, $category, $depth, $args ) {
        extract( $args );
        if ( empty( $taxonomy ) )
            $taxonomy = 'category';
        if ( $taxonomy == 'category' )
            $name = 'category';
        else
            $name = 'tax_input[' . $taxonomy . ']';
        $class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
        $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $name . '[]" id="in-' . $taxonomy . '-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
    }
    function end_el( &$output, $category, $depth, $args ) {
        $output .= "</li>\n";
    }
}/**
 * Displays checklist of a taxonomy
 *
 * @since 0.8
 * @param int $post_id
 * @param array $selected_cats
 */
function auiu_category_checklist( $post_id = 0, $selected_cats = false, $tax = 'category', $exclude = false ) {
    require_once ABSPATH . '/wp-admin/includes/template.php';
    $walker = new auiu_Walker_Category_Checklist();
    //exclude categories from checklist
    if ( $exclude ) {
        add_filter( 'list_terms_exclusions', 'auiu_category_checklist_exclusions' );
    }
    echo '<ul class="auiu-category-checklist">';
    wp_terms_checklist( $post_id, array(
        'taxonomy' => $tax,
        'descendants_and_self' => 0,
        'selected_cats' => $selected_cats,
        'popular_cats' => false,
        'walker' => $walker,
        'checked_ontop' => false
    ) );
    echo '</ul>';
}/**
 * Exclude categories from checklist
 *
 * @param string $exclusions
 * @return string
 */
function auiu_category_checklist_exclusions( $exclusions ) {
    //calling auiu_get_option generates a recursion fatal error
    //thats why exclue category values picked up manually
    $opt = get_option( 'auiu_frontend_posting' );
    if ( isset( $opt['exclude_cats'] ) && !empty( $opt['exclude_cats'] ) ) {
        $exclusions = " AND t.term_id NOT IN({$opt['exclude_cats']})";
    }
    return $exclusions;
}// display msg if permalinks aren't setup correctly
function auiu_permalink_nag() {
    if ( current_user_can( 'manage_options' ) )
        $msg = sprintf( __( 'You need to set your <a href="%1$s">permalink custom structure</a> to at least contain <b>/&#37;postname&#37;/</b> before AFB Visitor Image Upload will work properly.', 'auiu' ), 'options-permalink.php' );
    echo "<div class='error fade'><p>$msg</p></div>";
}//if not found %postname%, shows a error msg at admin panel
if ( !stristr( get_option( 'permalink_structure' ), '%postname%' ) ) {
    add_action( 'admin_notices', 'auiu_permalink_nag', 3 );
}function auiu_option_values() {
    global $custom_fields;
    auiu_value_travarse( $custom_fields );
}function auiu_value_travarse( $param ) {
    foreach ($param as $key => $value) {
        if ( $value['name'] ) {
            echo '"' . $value['name'] . '" => "' . get_option( $value['name'] ) . '"<br>';
        }
    }
}//auiu_option_values();
function auiu_get_custom_fields() {
    global $wpdb;
    $data = array();
    $fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}auiu_customfields", OBJECT );
    if ( $wpdb->num_rows > 0 ) {
        foreach ($fields as $f) {
            $data[] = array(
                'label' => $f->label,
                'field' => $f->field,
                'type' => $f->required
            );
        }
        return $data;
    }
    return false;
}/**
 * Adds notices on add post form if any
 *
 * @param string $text
 * @return string
 */
function auiu_addpost_notice( $text ) {
    $user = wp_get_current_user();
    if ( is_user_logged_in() ) {
        $lock = ( $user->auiu_postlock == 'yes' ) ? 'yes' : 'no';
        if ( $lock == 'yes' ) {
            return $user->auiu_lock_cause;
        }
        $force_pack = auiu_get_option( 'force_pack', 'auiu_payment' );
        $post_count = (isset( $user->auiu_sub_pcount )) ? intval( $user->auiu_sub_pcount ) : 0;
        if ( $force_pack == 'yes' && $post_count == 0 ) {
            return __( 'You must purchase a pack before posting', 'auiu' );
        }
    }
    return $text;
}add_filter( 'auiu_addpost_notice', 'auiu_addpost_notice' );
/**
 * Adds the filter to the add post form if the user can post or not
 *
 * @param string $perm permission type. "yes" or "no"
 * @return string permission type. "yes" or "no"
 */
function auiu_can_post( $perm ) {
    $user = wp_get_current_user();
    if ( is_user_logged_in() ) {
        $lock = ( $user->auiu_postlock == 'yes' ) ? 'yes' : 'no';
        if ( $lock == 'yes' ) {
            return 'no';
        }
        $force_pack = auiu_get_option( 'force_pack', 'auiu_payment' );
        $post_count = (isset( $user->auiu_sub_pcount )) ? intval( $user->auiu_sub_pcount ) : 0;
        if ( $force_pack == 'yes' && $post_count == 0 ) {
            return 'no';
        }
    }
    return $perm;
}add_filter( 'auiu_can_post', 'auiu_can_post' );
/**
 * Get all the image sizes
 *
 * @return array image sizes
 */
function auiu_get_image_sizes() {
    $image_sizes_orig = get_intermediate_image_sizes();
    $image_sizes_orig[] = 'full';
    $image_sizes = array();
    foreach ($image_sizes_orig as $size) {
        $image_sizes[$size] = $size;
    }
    return $image_sizes;
}/**
 * Get the value of a settings field
 *
 * @param string $option settings field name
 * @param string $section the section name this field belongs to
 * @param string $default default text if it's not found
 * @return mixed
 */
function auiu_get_option( $option, $section, $default = '' ) {
    $options = get_option( $section );
    if ( isset( $options[$option] ) ) {
        return $options[$option];
    }
    return $default;
}/**
 * check the current post for the existence of a short code
 *
 * @link http://wp.tutsplus.com/articles/quick-tip-improving-shortcodes-with-the-has_shortcode-function/
 * @param string $shortcode
 * @return boolean
 */
function auiu_has_shortcode( $shortcode = '', $post_id = false ) {
    global $post;
    if ( !$post ) {
        return false;
    }
     $post_to_check = ( $post_id == false ) ? get_post( get_the_ID() ) : get_post( $post_id );
    if ( !$post_to_check ) {
        return false;
    }
    // false because we have to search through the post content first
    $found = false;
    // if no short code was provided, return false
    if ( !$shortcode ) {
        return $found;
    }
    // check the post content for the short code
    if ( stripos( $post_to_check->post_content, '[' . $shortcode ) !== false ) {
        // we have found the short code
        $found = true;
    }
    return $found;
}//Enable Custom Direct - default is home_url
function custom_redirect( $url ) {
    global $post;
	$post_redirect = (int) (auiu_get_option( 'post_redirect', 'auiu_others' ));
	if ($post_redirect) {
		return get_permalink( $post->ID=$post_redirect );
	}
	else
	{
		return home_url();
	}	
}add_filter( 'auiu_after_post_redirect', 'custom_redirect' );
//Enable Custom Styles - Upload Form
$enable_styles = auiu_get_option( 'enable_styles', 'auiu_styles', 'no' );
if ( $enable_styles == 'yes' ) {
			add_action( 'wp_head', 'auiu_custom_styles', 999 );
	}
function auiu_custom_styles() {
	$button_background = auiu_get_option( 'button_background', 'auiu_styles' );
	$button_textcolor = auiu_get_option( 'button_textcolor', 'auiu_styles' );
	$button_hoverback = auiu_get_option( 'button_hoverback', 'auiu_styles' );
	$button_hovertext = auiu_get_option( 'button_hovertext', 'auiu_styles' );
	$button_radius = (int) auiu_get_option( 'button_radius', 'auiu_styles' );
	$button_font = auiu_get_option( 'button_font', 'auiu_styles' );
	$button_transform = auiu_get_option( 'button_transform', 'auiu_styles' );
	$button_size = (int) auiu_get_option( 'button_size', 'auiu_styles' );
	
	$label_size = (int) auiu_get_option( 'label_size', 'auiu_styles' );
	$label_weight = auiu_get_option( 'label_weight', 'auiu_styles' );
	$label_font = auiu_get_option( 'label_font', 'auiu_styles' );
	$description_size = (int) auiu_get_option( 'description_size', 'auiu_styles' );
	$description_font = auiu_get_option( 'description_font', 'auiu_styles' );
	$dropfile_size = auiu_get_option( 'dropfile_size', 'auiu_styles' );
	$dropfile_font = auiu_get_option( 'dropfile_font', 'auiu_styles' );
	$category_select_size = (int) auiu_get_option( 'category_select_size', 'auiu_styles' );
	$category_select_font = auiu_get_option( 'category_select_font', 'auiu_styles' );
	
	?>
	<style type="text/css">
		a#auiu-ft-upload-pickfiles, #auiu-ft-upload-filelist .button { 
			background: none repeat scroll 0 0 <?php echo $button_background; ?>;
			border: none; 
			color: <?php echo $button_textcolor; ?>;
			border-radius: <?php echo $button_radius; ?>px;
			font-family: <?php echo $button_font; ?>;
			text-transform: <?php echo $button_transform; ?>;
			font-size: <?php echo $button_size; ?>px;
		}
		a#auiu-ft-upload-pickfiles:hover, #auiu-ft-upload-filelist .button:hover { 
			background: none repeat scroll 0 0 <?php echo $button_hoverback; ?>;
			border: none; 
			color: <?php echo $button_hovertext; ?>;
		}
		.auiu-post-form input[type="submit"] { 
			background: none repeat scroll 0 0 <?php echo $button_back; ?>;
			border: none; 
			color: <?php echo $button_textcolor; ?>;
			border-radius: <?php echo $button_radius; ?>px;
			font-family: <?php echo $button_font; ?>;
			text-transform: <?php echo $button_transform; ?>;
			font-size: <?php echo $button_size; ?>px;			
		}	
		.auiu-post-form input[type="submit"]:hover { 
			background: none repeat scroll 0 0 <?php echo $button_hover_back; ?>;
			border: none; 
			color: <?php echo $button_hovertext; ?>;
		}
		.auiu-post-form label {
			font-size: <?php echo $label_size; ?>px;
			font-weight: <?php echo $label_weight; ?>;
			font-family: <?php echo $label_font; ?>;
		}	
		.auiu-post-form p.description {
			font-size: <?php echo $description_size; ?>px;
			font-family: <?php echo $description_font; ?>;
		}
		.auiu-dropfile-text {
			font-size: <?php echo $dropfile_size; ?>px;
			font-family: <?php echo $dropfile_font; ?>;
		}	
		.auiu-post-form .category-wrap select {
			font-size: <?php echo $category_select_size; ?>px;
			font-family: <?php echo $category_select_font; ?>;		
		}
	</style>
<?php
}