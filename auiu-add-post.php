<?php
/**
* Add Post form class
*
* @author Tareq Hasan & AFB * @package AFB Visitor Image Upload */
class AUIU_Add_Post {
    function __construct() {
		add_shortcode( 'afb_uploadform', array($this, 'shortcode') );    
	}
	/**
	* Handles the add post shortcode
	*
* @param $atts
*/
	function shortcode( $atts ) {
        extract( shortcode_atts( array('post_type' => 'post'), $atts ) );
        ob_start();
 		$this->post_form ($post_type);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    /**
     * Add posting main form
     *
     * @param $post_type
     */
    function post_form( $post_type ) {
 
        if ( isset( $_POST['auiu_post_new_submit'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];

			if ( !wp_verify_nonce( $nonce, 'auiu-add-post' ) ) {
                wp_die( __( 'Cheating?' ) );
            }

			$this->submit_post();
        }

        $featured_image = auiu_get_option( 'enable_featured_image', 'auiu_frontend_posting', 'no' );
		$uploader_name = auiu_get_option( 'enable_uploadername', 'auiu_enablecustom', 'no' );
		$uploader_email = auiu_get_option( 'enable_uploaderemail', 'auiu_enablecustom', 'no' );
		$uploader_agree = auiu_get_option( 'enable_uploaderagree', 'auiu_enablecustom', 'no' );
		
		if ( $uploader_name == 'yes' ) {
			auiu_uploadername ();
		}
		else
		{
			auiu_delete_uploadername ();
		}
		if ( $uploader_email == 'yes' ) {
			auiu_uploaderemail ();
		}
		else
		{
			auiu_delete_uploaderemail ();
		}
		if ( $uploader_agree == 'yes' ) {
			auiu_uploaderagree ();
		}
		else
		{
			auiu_delete_uploaderagree ();
		}
        $title = isset( $_POST['auiu_post_title'] ) ? esc_attr( $_POST['auiu_post_title'] ) : '';
        $description = isset( $_POST['auiu_post_content'] ) ? $_POST['auiu_post_content'] : '';
		$size_limit = (int) (auiu_get_option( 'attachment_max_size', 'auiu_frontend_posting' ));
		$max_before = auiu_get_option( 'maxsize_text_before', 'auiu_frontend_posting' );
		$max_after = auiu_get_option( 'maxsize_text_after', 'auiu_frontend_posting' );

        ?>
        <div id="auiu-post-area">
			<form id="auiu_new_post_form" name="auiu_new_post_form" action="" enctype="multipart/form-data" method="POST">
                <?php wp_nonce_field( 'auiu-add-post' ) ?>
                <ul class="auiu-post-form">
                    <?php do_action( 'auiu_add_post_form_top', $post_type ); //plugin hook   ?>
                    <?php auiu_build_custom_field_form( 'top' ); ?>
                    <?php if ( $featured_image == 'yes' ) { ?>
                        <?php if ( current_theme_supports( 'post-thumbnails' ) ) { 	?>
                            <li>
                                <label for="post-thumbnail"><?php echo auiu_get_option( 'ft_image_label', 'auiu_labels', 'Your Picture' ); ?></label>
                                <div id="auiu-ft-upload-container">
                                    <div id="auiu-ft-upload-filelist"></div>
                                        <a id="auiu-ft-upload-pickfiles" href="#"><?php echo auiu_get_option( 'ft_image_btn_label', 'auiu_labels', 'Upload Image' ); ?></a>
										<span class="auiu-dropfile-text"><?php echo auiu_get_option( 'dropzone_label', 'auiu_labels', 'Click Button or Drop File here' ); ?></span>
								</div>
                                <div class="clear"></div>
								<p class="description">
									<?php echo $max_before . ' ' . $size_limit . ' KB ' . $max_after; ?>
								</p>
							</li>
							<?php } else { ?>
                                <div class="info"><?php _e( 'Your theme doesn\'t support featured image', 'auiu' ) ?></div>
                            <?php } ?>
                    <?php } ?>
					<li>
						<label for="new-post-title">
                            <?php echo auiu_get_option( 'title_label', 'auiu_labels', 'Post Title'  ); ?> <span class="required">*</span>
						</label>
						<input class="requiredField" type="text" value="<?php echo $title; ?>" name="auiu_post_title" id="new-post-title" minlength="2" placeholder="<?php echo stripslashes( auiu_get_option( 'title_help', 'auiu_labels' ) ); ?>">
						<div class="clear"></div>
					</li>
							
                <?php if ( auiu_get_option( 'allow_cats', 'auiu_frontend_posting', 'on' ) == 'on' ) { ?>
                    <li>
                        <label for="new-post-cat">
                            <?php echo auiu_get_option( 'cat_label', 'auiu_labels', 'Category' ); ?> <span class="required">*</span>
                        </label>
                        <div class="category-wrap" style="float:left;">
                            <div id="lvl0">
                                <?php
                                $exclude = auiu_get_option( 'exclude_cats', 'auiu_frontend_posting' );
								$cat_type = auiu_get_option( 'cat_type', 'auiu_frontend_posting', 'normal' );
								$default_taxonomy = get_option('auiu_frontend_posting');
								$default_taxonomy = $default_taxonomy['default_taxonomy'];
                                if ( $cat_type == 'normal' ) {
                                    wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'auiu' ) . '&taxonomy='.$default_taxonomy.'&hierarchical=1&hide_empty=0&orderby=name&name=category[]&id=cat&show_count=0&title_li=&use_desc_for_title=1&class=cat requiredField&selected=152&exclude=' . $exclude );
                                } else if ( $cat_type == 'ajax' ) {
									wp_dropdown_categories( 'show_option_none=' . __( '-- Select --', 'auiu' ) . '&taxonomy='.$default_taxonomy.'&hierarchical=1&hide_empty=0&orderby=name&name=category[]&id=cat-ajax&show_count=0&title_li=&use_desc_for_title=1&class=cat requiredField&selected=152&depth=1&exclude=' . $exclude );
                                } else {
                                    auiu_category_checklist(0, false, $default_taxonomy, $exclude);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="loading"></div>
                            <div class="clear"></div>
                                <p class="description"><?php echo stripslashes( auiu_get_option( 'cat_help', 'auiu_labels' ) ); ?></p>
                </li>
                <?php } ?>
                <?php do_action( 'auiu_add_post_form_description', $post_type ); ?>
                <?php auiu_build_custom_field_form( 'description' ); ?>
                <li>
					<label for="new-post-desc">
                        <?php echo auiu_get_option( 'desc_label', 'auiu_labels', 'Post Content' ); ?> <span class="required">*</span>
                    </label>
					<?php
                    $editor = auiu_get_option( 'editor_type', 'auiu_frontend_posting' );
                    if ( $editor == 'full' ) {
                    ?>
					<div style="float:left;">
						<?php wp_editor( $description, 'new-post-desc', array('textarea_name' => 'auiu_post_content', 'editor_class' => 'requiredField', 'teeny' => false, 'textarea_rows' => 8) ); ?>
					</div>
                    <?php } else if ( $editor == 'rich' ) { ?>
                    <div style="float:left;">
                        <?php wp_editor( $description, 'new-post-desc', array('textarea_name' => 'auiu_post_content', 'editor_class' => 'requiredField', 'teeny' => true, 'textarea_rows' => 8) ); ?>
                    </div>
                    <?php } else { ?>
                        <textarea name="auiu_post_content" class="requiredField" id="new-post-desc" cols="60" rows="3" placeholder="<?php echo stripslashes( auiu_get_option( 'desc_help', 'auiu_labels' ) ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
					<?php } ?>
                    <div class="clear"></div>
 				</li>
                <?php
                do_action( 'auiu_add_post_form_after_description', $post_type );
                auiu_build_custom_field_form( 'tag' );
                if ( auiu_get_option( 'allow_tags', 'auiu_frontend_posting', 'on' ) == 'on' ) {
                ?>
				<li>
					<label for="new-post-tags">
                        <?php echo auiu_get_option( 'tag_label', 'auiu_labels', 'Tags' ); ?>
                    </label>
                    <input type="text" name="auiu_post_tags" id="new-post-tags" class="new-post-tags" placeholder="<?php echo stripslashes( auiu_get_option( 'tag_help', 'auiu_labels' ) ); ?>">
                     <div class="clear"></div>
                </li>
				<?php }
				do_action( 'auiu_add_post_form_tags', $post_type );
				auiu_build_custom_field_form( 'bottom' );
				$enabled_recaptcha = auiu_get_option( 'enable_recaptcha', 'auiu_others', 'no' );
				if ( $enabled_recaptcha == 'yes' ) {
				?>
				<li>
					<label><?php echo stripslashes( auiu_get_option( 'recaptcha_label', 'auiu_others' ) ); ?></label>
					<div class="g-recaptcha" data-callback="recaptchaCallback" data-sitekey="6LfbIRYTAAAAAJVjlDmlVuKVsOitnFT69rohv-Mq"></div>
					<script type="text/javascript">
						var recaptchaCallback = function() {
							jQuery( '.auiu-submit' ).prop( 'disabled', false );
						};
					</script>
					<input class="auiu-submit" type="submit" name="auiu_new_post_submit" value="<?php echo esc_attr( auiu_get_option( 'submit_label', 'auiu_labels', 'Submit Image!' ) ); ?>" disabled>
					<input type="hidden" name="auiu_post_type" value="<?php echo $post_type; ?>" />
                    <input type="hidden" name="auiu_post_new_submit" value="yes" />
                </li>
				<?php 
				} else {
				?>	
				<li>
                   	<input class="auiu-submit" type="submit" name="auiu_new_post_submit" value="<?php echo esc_attr( auiu_get_option( 'submit_label', 'auiu_labels', 'Submit Image!' ) ); ?>">
                    <input type="hidden" name="auiu_post_type" value="<?php echo $post_type; ?>" />
                    <input type="hidden" name="auiu_post_new_submit" value="yes" />
                </li>					
				<?php }
				?>
                <?php do_action( 'auiu_add_post_form_bottom', $post_type ); ?>
            </ul>
		</form>
    </div>
    <?php
    }
    /**
     * Validate the post submit data
     *
     * @global type $userdata
     * @param type $post_type
     */

    function submit_post() {
		//I moved the initialization of the errors array here so it can catch any captcha problems
		$errors = array();
		$enabled_captcha = auiu_get_option( 'enable_recaptcha', 'auiu_others', 'no' );
		if ( $enabled_captcha == 'yes' ) {
			require_once('lib/recaptchalib.php');
			$response = null;
			$privatekey = auiu_get_option( 'captcha_private_key', 'auiu_others' );
			// check secret key
			$reCaptcha = new ReCaptcha($privatekey);
			if ($_POST["g-recaptcha-response"]) {
				$response = $reCaptcha->verifyResponse(
				$_SERVER["REMOTE_ADDR"],
				$_POST["g-recaptcha-response"]
				);
			}			
			if ($response == null || !$response->success) {
   					$errors[] = __( 'You did not check the CAPTCHA. Please try again.', 'auiu' );
			}
		}	
		global $userdata;
		//die( var_dump ( $_POST ));

		//if there is some attachement, validate them
        if ( !empty( $_FILES['auiu_post_attachments'] ) ) {
            $errors = auiu_check_upload();
        }
	    $title = trim( $_POST['auiu_post_title'] );
        $content = trim( $_POST['auiu_post_content'] );
        $tags = '';

        if ( isset( $_POST['auiu_post_tags'] ) ) {
            $tags = auiu_clean_tags( $_POST['auiu_post_tags'] );
        }
        //validate title
        if ( empty( $title ) ) {
            $errors[] = __( 'Empty post title', 'auiu' );
        } else {
            $title = trim( strip_tags( $title ) );
        }
        //validate cat

        if ( auiu_get_option( 'allow_cats', 'auiu_frontend_posting', 'on' ) == 'on' ) {
            $cat_type = auiu_get_option( 'cat_type', 'auiu_frontend_posting', 'normal' );
            if ( !isset( $_POST['category'] ) ) {
                $errors[] = __( 'Please choose a category', 'auiu' );
            } else if ( $cat_type == 'normal' && $_POST['category'][0] == '-1' ) {
                $errors[] = __( 'Please choose a category', 'auiu' );
            } else {
                if ( count( $_POST['category'] ) < 1 ) {
                    $errors[] = __( 'Please choose a category', 'auiu' );
                }
            }
        }
        //validate post content
        if ( empty( $content ) ) {
            $errors[] = __( 'Empty post content', 'auiu' );
        } else {
            $content = trim( $content );
        }
        //process tags
        if ( !empty( $tags ) ) {
            $tags = explode( ',', $tags );
        }
        //post attachment
		$attach_id = isset( $_POST['auiu_featured_img'] ) ? intval( $_POST['auiu_featured_img'] ) : 0;
 	    //post type
        $post_type = trim( strip_tags( $_POST['auiu_post_type'] ) );
        //process the custom fields
        $custom_fields = array();
        $fields = auiu_get_custom_fields();
        if ( is_array( $fields ) ) {
            foreach ($fields as $cf) {
                if ( array_key_exists( $cf['field'], $_POST ) ) {
                    if ( is_array( $_POST[$cf['field']] ) ) {
                        $temp = implode(',', $_POST[$cf['field']]);
                    } else {
                        $temp = trim( strip_tags( $_POST[$cf['field']] ) );
                    }
                    //var_dump($temp, $cf);
					if ( ( $cf['type'] == 'yes' ) && !$temp ) {
                        $errors[] = sprintf( __( '"%s" is missing', 'auiu' ), $cf['label'] );
                    } else {
                        $custom_fields[$cf['field']] = $temp;
                    }
                } //array_key_exists
            } //foreach
        } //is_array

	$errors = apply_filters( 'auiu_add_post_validation', $errors );
        //if not any errors, proceed
        if ( $errors ) {
            echo auiu_error_msg( $errors );
            return;
        }
        $post_stat = auiu_get_option( 'post_status', 'auiu_frontend_posting' );
        //users are allowed to choose category
        if ( auiu_get_option( 'allow_cats', 'auiu_frontend_posting', 'on' ) == 'on' ) {
            $post_category = $_POST['category'];
        } else {
            $post_category = array(auiu_get_option( 'default_cat', 'auiu_frontend_posting' ));
        }

        $my_post = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_stat,
            'post_category' => $post_category,
            'post_type' => $post_type,
            'tags_input' => $tags
        );

        //plugin API to extend the functionality
        $my_post = apply_filters( 'auiu_add_post_args', $my_post );
        //var_dump( $_POST, $my_post );die();
        //insert the post
        $post_id = wp_insert_post( $my_post );
        if ( $post_id ) {
		// Set taxonomy
		$default_taxonomy = get_option('auiu_frontend_posting');
			$default_taxonomy = $default_taxonomy['default_taxonomy'];
			wp_set_post_terms( $post_id, $post_category, $default_taxonomy );
            //upload attachment to the post
            auiu_upload_attachment( $post_id );
			//send mail notification
            if ( auiu_get_option( 'post_notification', 'auiu_others', 'yes' ) == 'yes' ) {
                auiu_notify_post_mail( $userdata, $post_id );
            }
            //add the custom fields
            if ( $custom_fields ) {
                foreach ($custom_fields as $key => $val) {
                    add_post_meta( $post_id, $key, $val, true );
                }
            }
            //set post thumbnail if has any
            if ( $attach_id ) {
				set_post_thumbnail( $post_id, $attach_id );
            }
            //plugin API to extend the functionality
            do_action( 'auiu_add_post_after_insert', $post_id );
            //echo '<div class="success">' . __('Post published successfully', 'auiu') . '</div>';
            if ( $post_id ) {
               $redirect = apply_filters( 'auiu_after_post_redirect', get_permalink( $post_id ), $post_id );
               wp_redirect( $redirect );
				//wp_redirect( home_url() );
				exit;
			}
        }
	}
}
$auiu_postform = new AUIU_Add_Post();