<?php
	defined( 'ABSPATH' ) or die( "Direct access to this page is disabled!!!" );
	if( ! mdjm_employee_can( 'manage_packages' ) )	{
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'You do not have permission to manage equipment packages.', 'mobile-dj-manager' ) . '</p>',
			403
		);
	}
			
	?>
	<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
	<?php
	
	/* Deal with any form submissions */
	if( isset( $_POST['submit'] ) )	{ /* Form Submitted */
		if( $_POST['submit'] == 'Create Package' || $_POST['submit'] == 'Update Package' )	{ /* Add new package */
			if( !empty( $_POST['package_name'] ) )	{
				$packages = get_option( 'mdjm_packages' );
				
				if( $_POST['submit'] == 'Update Package' ) /* If Editing, delete and add again */
					unset( $packages[$_POST['slug']] );
					
				$package_id = sanitize_title_with_dashes( $_POST['package_name'] );
				if( !empty( $packages[$package_id] ) )	{
					$package_id = sanitize_title_with_dashes( $_POST['package_name'] ) . '_';
				}
				$djs_have = '';
				$i = 1;
				foreach( $_POST['djs'] as $this_dj ) {
					$djs_have .= $this_dj;
					if( $i != count( $_POST['djs'] ) ) $djs_have .= ',';
					$i++;
				}
				$equip = '';
				$i = 1;
				if( isset( $_POST['equip_id'] ) )	{
					if( !is_array( $_POST['equip_id'] ) )
						$_POST['equip_id'] = array( $_POST['equip_id'] );
					foreach( $_POST['equip_id'] as $equip_slug )	{
						$equip .= $equip_slug;
						if( $i != count( $_POST['equip_id'] ) ) $equip .= ',';
						$i++;
					}
				}
				if( !isset( $_POST['package_available'] ) || $_POST['package_available'] != 'Y' )
					$_POST['package_available'] = 'N';
				
				$packages[$package_id]['name'] = sanitize_text_field( $_POST['package_name'] );
				$packages[$package_id]['slug'] = $package_id;
				$packages[$package_id]['enabled'] = $_POST['package_available'];
				$packages[$package_id]['desc'] = sanitize_text_field( $_POST['package_desc'] );
				$packages[$package_id]['djs'] = $djs_have;
				$packages[$package_id]['equipment'] = $equip;
				$packages[$package_id]['cost'] = mdjm_format_amount( $_POST['package_cost'] );
				
				update_option( 'mdjm_packages', $packages );
				$curr_action = 'created';
				if( $_POST['submit'] == 'Update Package' ) $curr_action = 'updated';
				mdjm_update_notice( 'updated', 'Package ' . $curr_action . ' successfully' );
				unset( $_POST );
			}
			else	{
				mdjm_update_notice( 'error', 'ERROR: You need to enter a Package Name' );	
			}
		}
	}
	if( isset( $_POST['submit-delete'] ) && $_POST['submit-delete'] == 'Delete This Package' )	{ /* Delete package */
		$packages = get_option( 'mdjm_packages' );
		unset( $packages[$_POST['slug']] );
		update_option( 'mdjm_packages', $packages );
		mdjm_update_notice( 'updated', 'The selected packages have been deleted'  );
		unset( $_POST );
	}
	
	/* Display the forms */
	global $mdjm_options;
	$packages = get_option( 'mdjm_packages' );
	
	$djs = mdjm_get_employees();

	if( $packages )	{ /* Option to edit existing packages */
		asort( $packages );
		echo '<h3>Packages</h3>';
		echo '<form name="form-packages" id="form-packages" method="post">';
		echo '<table class="widefat">';
		echo '<tr>';
		echo '<td style="vertical-align:middle">';
		echo '<select name="all_packages" id="all_packages">';
		foreach( $packages as $list_package )	{
			echo '<option value="' . $list_package['slug'] . '">' . stripslashes( $list_package['name'] ) . '</option>';
		}
		echo '</select>';
		echo '&nbsp;&nbsp;&nbsp;';
		submit_button( 'Edit Package', 'primary', 'submit', false );
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '<hr />';
	}
	/* Create or edit packages */
	if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
		$title = 'Editing <span class="code">' . $packages[$_POST['all_packages']]['name'] . '</span> Equipment Package';
	}
	else	{
		$title = 'Add New Equipment Package';	
	}
	?>
    <h3><?php echo $title; ?></h3>
    <form name="form-manage-package" id="form-manage-package" method="post">
    <?php if( !empty( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' ) echo '<input type="hidden" name="slug" value="' . $packages[$_POST['all_packages']]['slug'] . '" />'; ?>
    <table>
    <tr>
    <td width="60%">
    <table class="widefat">
    <tbody>
    <tr>
    <td class="row-title" width="10%"><label for="package_name">Package Name:</label></td>
    <td>
    <?php 
	if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
	?><input type="text" name="package_name" id="package_name" class="all-options" value="<?php echo stripslashes( esc_attr( $packages[$_POST['all_packages']]['name'] ) ); ?>" /> <?php submit_button( 'Delete This Package', 'delete', 'submit-delete', false );
	}
	else	{
	?><input type="text" name="package_name" id="package_name" class="all-options" value="<?php echo ( !empty( $_POST['package_name'] ) ? stripslashes( esc_attr( $_POST['package_name'] ) ) : '' ); ?>" /><?php
    }
	?>
    </td>
    </tr>
    <tr>
    <td class="row-title" width="10%"><label for="package_available">Available?</label></td>
    <td>
    <?php 
	if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
		?><input type="checkbox" name="package_available" id="package_available" value="Y" <?php checked( $packages[$_POST['all_packages']]['enabled'], 'Y' ); ?> /><?php
	}
	else	{
		?><input type="checkbox" name="package_available" id="package_available" value="Y" checked="checked" /><?php
	}
    ?>
    </td>
    </tr>
    <tr>
    <td class="row-title" width="10%"><label for="package_desc">Description:</label></td>
    <td><textarea name="package_desc" id="package_desc" class="all-options"><?php if( !empty( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' ) { echo stripslashes( esc_textarea( $packages[$_POST['all_packages']]['desc'] ) ); } else { echo ( !empty( $_POST['package_desc'] ) ? stripslashes( esc_textarea( $_POST['package_desc'] ) ) : '' ); } ?></textarea></td>
	</tr>
     <?php
		if ( MDJM_MULTI == true )	{
		?>
            <tr>
            <td class="row-title"><label for="djs">DJs with this Package:</label></th>
            <td><select name="djs[]" multiple="multiple" id="djs" width="250" style="width: 250px">
				<?php
				if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
					$djs_have = explode( ',', $packages[$_POST['all_packages']]['djs'] );
					foreach( $djs as $dj )	{
						echo '<option value="' . $dj->ID . '"';
						foreach( $djs_have as $dj_in_list )	{
							if( $dj->ID == $dj_in_list )
								echo ' selected="selected"';
						}
						echo '>';
						echo $dj->display_name . '</option>';	
					}
				}
				else	{
					foreach( $djs as $dj )	{
						echo '<option value="' . $dj->ID . '">';
						echo $dj->display_name . '</option>';
					}
				}
				?>
                </select> <span class="description">Which DJ's can provide this package?</span>
                <br />
                <font size="-2">Hold CTRL & click to select multiple entries</font></td>
            </tr>
        <?php
		}
		else	{
			echo '<input type="hidden" name="djs[]" value="' . get_current_user_id() . '"';	
		}
		?>
    <tr>
    <td class="row-title">Package Price:</td>
    <td>
    <?php 
	if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
		?><input type="text" name="package_cost" id="package_cost" class="all-options" value="<?php echo esc_attr( $packages[$_POST['all_packages']]['cost'] ); ?>" /> <span class="description"><?php echo sprintf( __( 'No %s symbol needed', 'mobile-dj-manager' ), mdjm_currency_symbol() ); ?></span><?php
	}
	else	{
		?><input type="text" name="package_cost" id="package_cost" class="all-options" value="<?php echo ( !empty( $_POST['package_cost'] ) ? $_POST['package_cost'] : '' ); ?>" /> <span class="description"><?php echo sprintf( __( 'No %s symbol needed', 'mobile-dj-manager' ), mdjm_currency_symbol() ); ?></span><?php
	}
    ?>   
    </td>
    </tr>
    <tr>
    <td>
	<?php
		if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
			submit_button( 'Update Package', 'primary', 'submit', false );
		}
		else	{
			submit_button( 'Create Package', 'primary', 'submit', false );	
		}
	?>
	</td>
    <td><?php if( isset( $_POST['submit'] ) && $_POST['submit'] != 'Edit Package' )
		?> <a class="button-secondary" href="<?php echo $_SERVER['HTTP_REFERER']; ?>" title="<?php _e( 'Cancel' ); ?>"><?php _e( 'Cancel' ); ?></a></td>
    </tr>
    </tbody>
	</table>
    </td>
    <td valign="top">
    <table class="widefat">
    <tr>
    <td class="row-title">Equipment Included:</td>
    </tr>
    <tr>
    <td>
    <?php
		$equipment = get_option( 'mdjm_equipment' );
		if( !empty( $equipment ) )
			asort( $equipment );
			
		$cats = get_option( 'mdjm_cats' );
		if( !empty( $cats ) )
			asort( $cats );
			
		if( isset( $_POST['submit'] ) && $_POST['submit'] == 'Edit Package' )	{
			foreach( $cats as $cat_key => $cat_value )	{
				echo '<strong>' . $cat_value . '</strong>';
				echo '<br />';
				foreach( $equipment as $equip_list )	{
					if( $equip_list[5] == $cat_key )	{
						$all_equip_in_package = explode( ',', $packages[$_POST['all_packages']]['equipment'] );
						echo '<input type="checkbox" name="equip_id[]" id="equip_id[]" value="' . $equip_list[1] . '"';
						foreach( $all_equip_in_package as $equip_in_package )	{
							if( $equip_list[1] == $equip_in_package )
								echo ' checked="checked"';	
						}
						echo ' />&nbsp;';
						echo stripslashes( esc_attr( $equip_list[0] ) );

						if( esc_attr( $equip_list[2] ) > 1 )
							echo ' x ' . esc_attr( $equip_list[2] );
						?>
						<br />
						<?php
					}
				}
				echo '<br />';
			}
		}
		else	{
			foreach( $cats as $cat_key => $cat_value )	{
				echo '<strong>' . $cat_value . '</strong>';
				echo '<br />';
				foreach( $equipment as $equip_list )	{
					if( $equip_list[5] == $cat_key )	{
						?>
						<input type="checkbox" name="equip_id[]" id="equip_id[]" value="<?php echo $equip_list[1]; ?>" />&nbsp;
						<?php echo stripslashes( esc_attr( $equip_list[0] ) ); ?>
						<?php
						if( esc_attr( $equip_list[2] ) > 1 )
							echo ' x ' . esc_attr( $equip_list[2] );
						?>
						<br />
						<?php
					}
				}
				echo '<br />';
			}	
		}
	?>
    </td>
    </tr>
    </table>
    </form>
	</div>