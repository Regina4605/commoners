<?php

function ccgn_list_render_individual_applicants ( $members ) {
    $emails = [];
    foreach ( $members as $member ) {
        $member_id = $member [ CCGN_GF_FINAL_APPROVAL_APPLICANT_ID ];
        $user = get_user_by ( 'ID', $member_id );
        if ( ccgn_member_is_individual( $member_id ) ) {
            $emails[] = $user->user_email;
            echo '<tr><td>'
                . $user->display_name
                . '</td><td>'
                . $user->user_email
                . '</td><td>'
                . bp_get_profile_field_data(
                    'field=Location&user_id=' . $member_id
                )
                . '</td><td>'
                . bp_get_profile_field_data(
                    'field=Preferred%20Country%20Chapter&user_id=' . $member_id
                )
                . '</td><td>'
                . join( ', ',
                        bp_get_profile_field_data(
                            'field=Areas%20of%20Interest&user_id=' . $member_id
                        )
                )
                . '</td><td>'
                . ccgn_application_format_vouches_yes ( $member_id )
                . '</td><td>'
                . $member[ 'date_created' ]
                . '</td></tr>';
        }
    }
    return $emails;
}

function ccgn_application_format_vouches_yes ( $applicant_id ) {
    $vouchers = [];
    foreach ( ccgn_application_vouches ( $applicant_id ) as $vouch ) {
        if (
            $vouch[ CCGN_GF_VOUCH_DO_YOU_VOUCH ]
            == CCGN_GF_VOUCH_DO_YOU_VOUCH_YES
        ) {
            $voucher_id = $vouch[ 'created_by' ];
            $vouchers[] = bp_core_get_userlink( $voucher_id );
            $user = get_userdata( $voucher_id );
        }
    }
    return join( ', ', $vouchers );
}

function ccgn_list_render_institutional_applicants ( $members ) {
    $emails = [];
    foreach ( $members as $member ) {
        $member_id = $member [ CCGN_GF_LEGAL_APPROVAL_APPLICANT_ID ];
        $user = get_user_by ( 'ID', $member_id );
        if ( ccgn_member_is_institutional( $member_id ) ) {
            $details = ccgn_details_institution_form_entry (
                $applicant_id
            );
            $contact_name_to_use = $details[
                CCGN_GF_INSTITUTION_DETAILS_REPRESENTATIVE_NAME
            ];
            $contact_email_to_use = $details[
                CCGN_GF_INSTITUTION_DETAILS_REPRESENTATIVE_EMAIL
            ];
            $emails[] = $contact_email_to_use;
            echo '<tr><td>'
                //FIXME: this won't work after scrubbing, use profile field
                . ccgn_institutional_applicant_name ( $member_id )
                . '</td><td>'
                . $contact_name_to_use
                . '</td><td>'
                . $contact_email_to_use
                . '</td><td>'
                . $member[ 'date_created' ]
                . '</td></tr>';
        }
    }
    return $emails;
}

function ccgn_list_recent_members ( $start_date, $end_date ) {
    $individuals = ccgn_new_final_approvals_since ( $start_date, $end_date );
    if ( $individuals ) {
?>
    <h2>New Individual Members</h2>
    <h3>Details</h3>
    <table id="ccgn-list-new-individuals" class="tablesorter">
      <thead align="left">
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Location</th>
          <th><span style="white-space: nowrap">Chapter of Interest</span></th>
          <th><span style="white-space: nowrap">Aread of Interest</span></th>
          <th>Vouchers</th>
          <th><span style="white-space: nowrap">Final Approval Date</span></th>
        </tr>
      </thead>
      <tbody>
<?php
       $individual_emails = ccgn_list_render_individual_applicants (
           $individuals
       );
?>
      </tbody>
    </table>
    <h3>Email List</h3>
<?php
       echo join( ', ', $individual_emails );
    } else {
?>
    <h2>No Individual Members matched the specified dates</h2>
<?php
    }
    $institutions = ccgn_new_legal_approvals_since ( $start_date, $end_date );
    if ( $institutions ) {
?>
    <h2>New Institutional Members</h2>
    <h3>Details</h3>
    <table id="ccgn-list-new-institutions" class="tablesorter">
      <thead align="left">
        <tr>
          <th>Organization</th>
          <th>Contact Name</th>
          <th>Contact Email</th>
          <th>Legal Approval Date</th>
        </tr>
      </thead>
      <tbody>
<?php
        $institution_emails = ccgn_list_render_institutional_applicants (
            $institutions
        );
?>
      </tbody>
    </table>
    <h3>Email List</h3>
<?php
      echo join( ', ', $institution_emails );
    } else {
?>
    <h2>No Institutional Members matched the specified dates</h2>
<?php
    }
}

function ccgn_list_members_admin_page () {
?>
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
<?php
    $start_date = '';
    if ( isset( $_GET['start_date'] ) ) {
        $start_date = filter_var(
            $_GET[ 'start_date' ],
            FILTER_SANITIZE_STRING
        );
    }
    $end_date = '';
    if ( isset( $_GET['end_date'] ) ) {
        $end_date = filter_var(
            $_GET[ 'end_date' ],
            FILTER_SANITIZE_STRING
        );
    }
    ccgn_list_recent_members( $start_date, $end_date );
?>
  <form method="get" action="<?php
     echo esc_html( admin_url( 'admin.php?page=global-network-list-users' ) );
?>">
    <input type="hidden" name="page" value="global-network-list-users" />
    <div class="options">
      <p>
        <label>Start date</label>
        <br />
        <input type="text" name="start_date" id="ccgn-list-members-date-from"
          value="<?php echo $start_date; ?>" placeholder="YYYY-MM-DD" />
      </p>
      <p>
        <label>End date</label>
        <br />
        <input type="text" name="end_date" id="ccgn-list-members-date-from"
          value="<?php echo $end_date; ?>" placeholder="YYYY-MM-DD" />
      </p>
    </div>
<?php
    submit_button('List');
?>
  </form>
<?php
}

////////////////////////////////////////////////////////////////////////////////
// Admin UI hooks
////////////////////////////////////////////////////////////////////////////////

// This shouldn't really have "application" in it but it would look out of place

function ccgn_application_list_members_menu () {
    add_submenu_page(
        'global-network-application-approval',
        'List Members',
        'List Members',
        'ccgn_pre_approve',
        'global-network-list-users',
        'ccgn_list_members_admin_page'
    );
}
