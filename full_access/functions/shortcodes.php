<?php 

// Add the Shortcode [edd_fa_customer_passes].
add_shortcode( 'edd_fa_customer_passes', 'edd_full_access_passes');

// Add the [edd_fa_full_access] shortcode.
add_shortcode( 'edd_fa_full_access', 'edd_fa_full_access');

// Add the [edd_fa_no_access_pass] shortcode.
add_shortcode( 'edd_fa_no_access_pass',  'no_full_access');

// Add the [edd_fa_restrict_content] shortcode.
add_shortcode( 'edd_fa_restrict_content', 'full_access_restrict');

function edd_full_access_passes() {

    ob_start();
    edd_print_errors();

    // If we are viewing a single All Access Pass's details.
    if ( ! empty( $_GET['action'] ) && 'view_full_access_pass' === $_GET['action'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        edd_get_template_part( 'templify-full-access', 'view-single-pass' );
    } else {
        edd_get_template_part( 'shortcode', 'full-access-licenses' );
    }

    return ob_get_clean();

}



 function edd_fa_full_access( $atts, $content = null ) {

    global $post;

    $post_id = is_object( $post ) ? $post->ID : 0;

    $atts = shortcode_atts(
        array(
            'id'                   => $post_id,
            'price_id'             => false,
            'sku'                  => '',
            'price'                => true,
            'direct'               => '0',
            'text'                 => '',
            'style'                => edd_get_option( 'button_style', 'button' ),
            'color'                => edd_get_option( 'checkout_color', 'blue' ),
            'class'                => 'edd-submit',
            'form_id'              => '',
            'popup_login'          => true,
            'buy_instructions'     => '',
            'login_instructions'   => '',
            'login_btn_style'      => 'text',
            'preview_image'        => '',
            'success_redirect_url' => '',
            'success_text'         => '',
        ),
        $atts,
        'all_access'
    );

    $all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
    $all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

    $at_least_one_all_access_pass_is_valid = false;

    foreach ( $all_access_download_ids as $all_access_download_id ) {
        if ( false === $all_access_price_ids ) {
            if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
                $at_least_one_all_access_pass_is_valid = true;
                break;
            }
        }
        foreach ( $all_access_price_ids as $all_access_price_id ) {
            $customer_has_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id );

            if ( $customer_has_all_access_pass ) {
                $at_least_one_all_access_pass_is_valid = true;
                break;
            }
        }
    }

    $preview_area_html   = '';
    $login_purchase_area = '';
    $success_html        = '';

    // If the customer does not have access and a preview image has been provided.
    if ( ! $at_least_one_all_access_pass_is_valid && ! empty( $atts['preview_image'] ) ) {
        $preview_area_html .= '<div class="edd-aa-preview-area"><img class="edd-aa-preview-img" src="' . esc_url( $atts['preview_image'] ) . '" /></div>';
    }

    // If this customer has this Full Access License and it is valid.
    if ( $at_least_one_all_access_pass_is_valid ) {

        // If success content has been passed in use that.
        if ( ! empty( $content ) ) {
            $success_html .= do_shortcode( $content );
        } else {
            // Set up success text if it exists.
            $success_html .= empty( $atts['success_text'] ) ? __( 'You have an Full Access License for', 'templify-full-access' ) . ' ' . get_the_title( $atts['id'] ) : $atts['success_text'];
        }

        // Redirect the user if shortcode has it set.
        if ( ! empty( $atts['success_redirect_url'] ) ) {
            // Prevent redirect loops.
            if ( ! isset( $_GET['redirect-from-aa'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                // Redirect the user to the redirection page provided by the shortcode args.
                $success_html .= '<script type="text/javascript">window.location.replace("' . esc_url( add_query_arg( array( 'redirect-from-aa' => true ), $atts['success_redirect_url'] ) ) . '");</script>';
            }
        }
    } else {

        $all_access_buy_or_login_atts = array(
            'all_access_download_id' => $all_access_download_ids,
            'all_access_price_id'    => $all_access_price_ids,
            'all_access_sku'         => $atts['sku'],
            'all_access_price'       => $atts['price'],
            'all_access_direct'      => $atts['direct'],
            'all_access_btn_text'    => $atts['text'],
            'all_access_btn_style'   => $atts['style'],
            'all_access_btn_color'   => $atts['color'],
            'all_access_btn_class'   => $atts['class'],
            'all_access_form_id'     => $atts['form_id'],
            'class'                  => 'edd-aa-login-purchase-aa-only-mode',
            'popup_login'            => $atts['popup_login'],
            'buy_instructions'       => $atts['buy_instructions'],
            'login_instructions'     => $atts['login_instructions'],
            'login_btn_style'        => $atts['login_btn_style'],
            'preview_image'          => $atts['preview_image'],
        );

        // Customer does not have All Access Pass. Output buy / login form.
        $login_purchase_area = edd_all_access_buy_or_login_form( $all_access_buy_or_login_atts );
    }

    $output_array = apply_filters(
        'edd_all_access_shortcode_outputs',
        array(
            'preview_area'        => $preview_area_html,
            'login_purchase_area' => $login_purchase_area,
            'success_output'      => $success_html,
        ),
        $atts
    );

    // Set html output wrapper.
    $html_output = '<div class="edd-aa-wrapper">';

    foreach ( $output_array as $chunk_name => $output_chunk ) {

        // Make sure success_output is only shown if the customer has access.
        if ( 'success_output' === $chunk_name && ! $at_least_one_all_access_pass_is_valid ) {
            continue;
        }

        $html_output .= $output_chunk;
    }

    $html_output .= '</div>';

    return $html_output;

}

	/**
	 * Simple shortcode which can be used to show content only to people without an All Access Pass.
	 *
	 * @since    1.0.0
	 * @param    array  $atts Shortcode attributes.
	 * @param    string $content The content that should be shown if the user does not have the AA pass in question.
	 * @return   string Shortcode Output
	 */
	 function no_full_access( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'id'       => false,
				'price_id' => false,
			),
			$atts,
			'full_access'
		);

		// If no download id entered, return blank.
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
		$all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

		foreach ( $all_access_download_ids as $all_access_download_id ) {
			if ( false === $all_access_price_ids ) {
				if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
					return '';
				}
			} else {
				foreach ( $all_access_price_ids as $all_access_price_id ) {
					if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id ) ) {
						return '';
					}
				}
			}
		}

		// If they have no All Access Pass, return the content to show them.
		return do_shortcode( $content );
	}

	/**
	 * Simple shortcode which can be used to show content only to people with an All Access Pass.
	 *
	 * @since   1.0.0
	 * @param    array  $atts Shortcode attributes.
	 * @param    string $content The content that should be shown if the user does have the AA pass in question.
	 * @return  string Shortcode Output
	 */
	 function all_access_restrict( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'id'       => false,
				'price_id' => false,
			),
			$atts,
			'all_access'
		);

		// If no download id entered, return blank.
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
		$all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

		$at_least_one_all_access_pass_is_valid = false;

		foreach ( $all_access_download_ids as $all_access_download_id ) {
			if ( false === $all_access_price_ids ) {
				if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
			foreach ( $all_access_price_ids as $all_access_price_id ) {
				$customer_has_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id );

				if ( $customer_has_all_access_pass ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
		}

		// If the customer does not have the All Access Pass, this shortcode has no output.
		return ! $at_least_one_all_access_pass_is_valid ? '' : do_shortcode( $content );
	}


?>
