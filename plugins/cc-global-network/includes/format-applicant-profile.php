<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

////////////////////////////////////////////////////////////////////////////////
// Get applicant details from request (if provided)
////////////////////////////////////////////////////////////////////////////////

function ccgn_request_applicant_id () {
    $applicant_id = filter_input(
        INPUT_GET,
        'user_id',
        FILTER_VALIDATE_INT
    );
    if ( $applicant_id === false ) {
        echo _( '<br />Invalid user id.' );
    } elseif ( $applicant_id === null ) {
        echo _( '<br />No user id specified.' );
        $applicant_id = false;
    } elseif ( $applicant_id == get_current_user_id() ) {
        echo _( '<br />You cannot edit your own application status' );
        $applicant_id = false;
    } else {
        $applicant = get_user_by( 'ID', $applicant_id );
        if( $applicant === false ) {
            echo _( '<br />Invalid user specified.' );
            $applicant_id = false;
            //FIXME: Check if really autovouched, check if not and should be
        } elseif ( ccgn_user_is_autovouched( $applicant_id ) ) {
            echo '<br><h4><i>User was autovouched, no application details.</i></h4>';
            $applicant_id = false;
        }
    }
    return $applicant_id;
}

////////////////////////////////////////////////////////////////////////////////
// The details the user provided.
////////////////////////////////////////////////////////////////////////////////

// Strip tags from the string and translate newlines to html breaks.

function ccgn_vp_clean_string( $string ) {
    //FIXME: If it's an array, format as an ul
    return str_replace(
        "\r\n",
        '<br />',
        filter_var( $string, FILTER_SANITIZE_STRING )
    );
}

function ccgn_field_values ( $entry, $id ) {
    // Get an array of the keys that match "$id" or "$id.1" but not "$id1"
    $keys = array_values(
        preg_grep(
            "/^($id|$id\.\d+)$/",
            array_keys(
                $entry
            )
        )
    );
    $results = array_values(
        array_intersect_key(
            $entry,
            array_flip(
                $keys
            )
        )
    );
    // This will be formatted to <br> by ccgn_vp_clean_string, which strips tags
    return implode( "\r\n", $results ? $results : [] );
}

// Format up a field from the Applicant Details.

function ccgn_vp_format_field ( $entry, $item ) {
    $html = '';
    $value = ccgn_vp_clean_string(
        ccgn_field_values(
            $entry,
            $item[ 1 ]
        )
    );
    // Make sure the entry has a value for this item
    if( $value ) {
        $html = '<p><strong>'
              . $item[ 0 ] . '</strong><br />'
              . $value
              . '</p>';
    }
    return $html;
}

// Format the avatar image from the Applicant Details as an html IMG tag.

function ccgn_vp_format_avatar ( $entry ) {
    $img = '';
    $user_id = $entry[ 'created_by' ];
    if ( ccgn_applicant_gravatar_selected ( $user_id ) ) {
        $img = ccgn_user_gravatar_img ( $user_id, 300 );
    } else {
        // If this has been removed
        if ( ! isset( $entry[ CCGN_GF_DETAILS_AVATAR_FILE ] ) ) {
            //FIXME: get profile image url
            $img = '';
        } else {
            $img_url = $entry[ CCGN_GF_DETAILS_AVATAR_FILE ];
            $img = '<strong>Applicant Image</strong><p><img style="max-height:300px; width:auto;" src="' . $img_url . '"></p>';
        }
    }
    return $img;
}

// Format the relevant fields from the Applicant Details form as html.

function ccgn_vouching_form_profile_format( $entry, $map ) {
    $html = '<div class="ccgn-vouching-profile">';
    foreach( $map as $item ) {
         $html .= ccgn_vp_format_field( $entry, $item );
    }
    $html .= '</div>';
    return $html;
}

// Get the applicant's (latest) Applicant Details form and return them
// formatted as html.

function ccgn_vouching_form_individual_profile_text ( $applicant_id ) {
    $entry = ccgn_details_individual_form_entry( $applicant_id );
    return '<h3>Individual Applicant</h3>'
        //. ccgn_vp_format_avatar( $entry )
        . ccgn_vouching_form_profile_format(
            $entry,
            CCGN_GF_DETAILS_VOUCH_MAP
        );
}

function ccgn_vouching_form_institution_profile_text ( $applicant_id ) {
    return '<h3>Institutional Applicant</h3>'
        . ccgn_vouching_form_profile_format(
            ccgn_details_institution_form_entry ( $applicant_id ),
            CCGN_GF_INSTITUTION_DETAILS_VOUCH_MAP
        );
}

function ccgn_vouching_form_applicant_profile_text ( $applicant_id ) {
    if( ccgn_user_is_individual_applicant( $applicant_id ) ) {
        return ccgn_vouching_form_individual_profile_text( $applicant_id );
    } elseif( ccgn_user_is_institutional_applicant( $applicant_id ) ) {
        return ccgn_vouching_form_institution_profile_text(
            $applicant_id
        );
    } else {
        return "<p>Error: newbie.</p>";
    }
}

function ccgn_user_page_individual_profile_text ( $applicant_id ) {
    $entry = ccgn_details_individual_form_entry( $applicant_id );
    return '<h3>Individual Applicant</h3>'
        //. ccgn_vp_format_avatar ( $entry )
        . ccgn_applicant_display_name_formatted ( $applicant_id )
        . ccgn_vouching_form_profile_format(
            $entry,
            CCGN_GF_DETAILS_USER_PAGE_MAP
        );
}

function ccgn_user_page_institution_profile_text ( $applicant_id ) {
    return '<h3>Institutional Applicant</h3>'
        . ccgn_applicant_display_name_formatted ( $applicant_id )
        .ccgn_vouching_form_profile_format(
            ccgn_details_institution_form_entry ( $applicant_id ),
            CCGN_GF_INSTITUTION_DETAILS_USER_PAGE_MAP
        );
}

function ccgn_user_page_applicant_profile_text ( $applicant_id ) {
    if( ccgn_user_is_individual_applicant( $applicant_id ) ) {
        return ccgn_user_page_individual_profile_text( $applicant_id );
    } elseif( ccgn_user_is_institutional_applicant( $applicant_id ) ) {
        return ccgn_user_page_institution_profile_text( $applicant_id );
    } else {
        return "<p>Error: newbie.</p>";
    }
}

function ccgn_applicant_display_name ( $applicant_id ) {
    if ( ccgn_user_is_individual_applicant ( $applicant_id ) ) {
        return get_user_by( 'ID', $applicant_id)->display_name;
    } else {
        return ccgn_institutional_applicant_name ( $applicant_id );
    }
}

function ccgn_applicant_display_name_formatted ( $applicant_id ) {
    if ( ccgn_user_is_individual_applicant ( $applicant_id ) ) {
        return '<p><strong>Applicant Name</strong><br />'
            . get_user_by( 'ID', $applicant_id)->display_name
            . '</p>';
    } else {
        return '<p><strong>Institution Name</strong><br />'
            . ccgn_institutional_applicant_name ( $applicant_id )
            . '</p>';
    }
}
