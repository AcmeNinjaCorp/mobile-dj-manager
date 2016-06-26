<?php
/**
 * Contains all travel related functions
 *
 * @package		MDJM
 * @subpackage	Venues
 * @since		1.3.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Calculate the travel distance
 *
 * @since	1.3.8
 * @param	int|obj		$event		The event ID or the event MDJM_Event class object.
 * @param	int			$venue_id	The venue ID.
 * @return	str			The distance to the event venue or an empty string
 */
function mdjm_travel_get_distance( $event = '', $venue_id = '' )	{

	if ( ! empty( $event ) )	{
		if ( ! is_object( $event ) )	{
			$mdjm_event = new MDJM_Event( $event );
		} else	{
			$mdjm_event = $event;
		}
	}

	$start       = mdjm_travel_get_start( $mdjm_event );
	$destination = mdjm_travel_get_destination( $mdjm_event, $venue_id );

	if ( empty( $start ) || empty( $destination ) )	{
		return false;
	}

	$query = mdjm_travel_build_url( $start, $destination );

	$response = wp_remote_get( $query );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$travel_data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( empty( $travel_data ) || $travel_data->status != 'OK' )	{
		return false;
	}

	if ( empty( $travel_data->rows ) )	{
		return false;
	}

	if ( empty( $travel_data->origin_addresses[0] ) || empty( $travel_data->destination_addresses[0] ) )	{
		return false;
	}

	if ( empty( $travel_data->rows[0]->elements[0]->distance->value ) || empty( $travel_data->rows[0]->elements[0]->duration->value ) )	{
		return false;
	}

	$return = array(
		'origin'      => $travel_data->origin_addresses[0],
		'destination' => $travel_data->destination_addresses[0],
		'duration'    => $travel_data->rows[0]->elements[0]->duration->value,
		'distance'    => str_replace(
			array( 'km', 'mi' ),
			array( '', '' ),
			$travel_data->rows[0]->elements[0]->distance->text
		),
	);

	return apply_filters( 'mdjm_travel_get_distance', $return, $event );

} // mdjm_travel_get_distance

/**
 * Calculate the travel cost.
 *
 * @since	1.3.8
 * @param	str			$distance		The distance of travel.
 * @return	str|int		The cost of travel.
 */
function mdjm_get_travel_cost( $distance )	{

	$min       = mdjm_get_option( 'travel_min_distance' );
	$unit_cost = mdjm_get_option( 'cost_per_unit' );
	$round     = mdjm_get_option( 'travel_cost_round' );

	if ( intval( $distance ) < $min )	{
		return '0.00';
	}

	$cost = $distance * $unit_cost;

	if ( $round )	{

		$nearest = mdjm_get_option( 'travel_round_to' );
	
		if ( intval( $cost ) == $cost && ! is_float( intval( $cost ) / $nearest ) )	{
			$cost = intval( $cost );
		} else	{
			if ( $round == 'up' )	{
				$cost = round( ( $cost + $nearest / 2 ) / $nearest ) * $nearest;
			} else	{
				$cost = floor( ( $cost + $nearest / 2 ) / $nearest ) * $nearest;
			}
		}

	} else	{
		$cost = $cost;
	}

	return apply_filters( 'mdjm_get_travel_cost', $cost, $distance );

} // mdjm_get_travel_cost

/**
 * Build the URL to retrieve the distance.
 *
 * @since	1.3.8
 * @param	str			$start			The travel start address.
 * @return	str			$destination	The travel destination address.
 */
function mdjm_travel_build_url( $start, $destination )	{

	$api_key = mdjm_travel_get_api_key();
	$prefix  = 'https://maps.googleapis.com/maps/api/distancematrix/json';
	$mode    = 'driving';
	$units   = mdjm_get_option( 'travel_units' );

	$url = add_query_arg( array(
		'units'        => $units,
		'origins'      => str_replace( '%2C', ',', urlencode( $start ) ),
		'destinations' => str_replace( '%2C', ',', urlencode( $destination ) ),
		'mode'         => $mode,
		//'key'          => $api_key
		),
		$prefix
	);

	return apply_filters( 'mdjm_travel_build_url', $url );

} // mdjm_travel_build_url

/**
 * Retrieve the Google API key.
 *
 * @since	1.3.8
 * @param
 * @return	str			The API key.
 */
function mdjm_travel_get_api_key()	{
	return '617372114575-g846rsgcm715pkmhkokrho9c75ii3cne.apps.googleusercontent.com';
} // mdjm_travel_get_api_key

/**
 * Retrieves the travel starting point.
 *
 * @since	1.3.8
 * @param	int|obj		$event	The event ID or the event MDJM_Event class object.
 * @return	str
 */
function mdjm_travel_get_start( $event = '' )	{

	if ( ! empty( $event ) )	{
		if ( ! is_object( $event ) )	{
			$mdjm_event = new MDJM_Event( $event );
		} else	{
			$mdjm_event = $event;
		}
	}

	$start = isset( $mdjm_event ) ? mdjm_get_employee_address( $mdjm_event->get_employee() ) : mdjm_get_option( 'travel_primary' );

	if ( empty( $start ) )	{
		return;
	}

	if ( is_array( $start ) )	{
		$start = implode( ',', $start );
	}

	return apply_filters( 'mdjm_travel_get_start', $start );

} // mdjm_travel_get_start

/**
 * Retrieves the travel destination address.
 *
 * @since	1.3.8
 * @param	int|obj		$event	The event ID or the event MDJM_Event class object.
 * @return	str
 */
function mdjm_travel_get_destination( $event, $venue_id = '' )	{

	if ( ! is_object( $event ) )	{
		$mdjm_event = new MDJM_Event( $event );
	} else	{
		$mdjm_event = $event;
	}

	$venue = ! empty( $venue_id ) ? $venue_id : $mdjm_event->get_venue_id();

	$destination = mdjm_get_event_venue_meta( $venue, 'address' );

	if ( ! $destination )	{
		return;
	}

	if ( is_array( $destination ) )	{
		$destination = implode( ',', $destination );
	}

	return apply_filters( 'mdjm_travel_get_destination', $destination );

} // mdjm_travel_get_destination

/**
 * Returns the label for the selected measurement unit.
 *
 * @since	1.3.8
 * @param	bool	$singular	Whether to return a singular (true) or plural (false) value.
 * @param	bool	$lowercase	True to return a lowercase label, otherwise false.
 * @return	str
 */
function mdjm_travel_unit_label( $singular = false, $lowercase = true )	{
	$units = array(
		'singular' => array(
			'imperial' => 'Mile',
			'metric'   => 'Kilometer'
		),
		'plural'   => array(
			'imperial' => 'Miles',
			'metric'   => 'Kilometers'
		)
	);

	$type = 'singular';

	if ( ! $singular )	{
		$type = 'plural';
	}

	$return = $units[ $type ][ mdjm_get_option( 'travel_units', 'imperial' ) ];

	if ( $lowercase )	{
		$return = strtolower( $return );
	}

	return apply_filters( 'mdjm_travel_unit_label', $return );

} // mdjm_travel_unit_label

/**
 * Adds the travel cost to the event.
 *
 * @since	1.3.8
 * @param	arr		$data			Array of meta values being updated.
 * @param	arr		$current_meta	Array of existing meta values.
 * @param	int		$event_id		The event ID.
 * @return	arr		$data			Filtered array of meta values being updated.
 */
function mdjm_add_travel_cost_to_event( $data, $current_meta, $event_id )	{

	if ( ! mdjm_get_option( 'travel_add_cost' ) )	{
		return $data;
	}

	$travel_status = mdjm_get_option( 'travel_status' );

	if ( empty( $travel_status ) )	{
		return $data;
	}

	$mdjm_event = new MDJM_Event( $event_id );

	if ( ! is_array( $travel_status ) || ! in_array( $mdjm_event->post_status, $travel_status ) )	{
		return $data;
	}

	if ( isset( $current_meta['_mdjm_event_venue_id'] ) )	{
		$current_venue = $current_meta['_mdjm_event_venue_id'][0];
	} else	{
		$current_venue = $mdjm_event->get_venue_id();
	}

	if ( isset( $current_meta['_mdjm_event_travel_cost'] ) )	{
		$travel = $current_meta['_mdjm_event_travel_cost'][0];
	} else	{
		$travel = '0.00';
	}

	$venue_id = isset( $data['_mdjm_event_venue_id'] ) ? $data['_mdjm_event_venue_id'] : false;
	$price    = isset( $data['_mdjm_event_cost'] )     ? $data['_mdjm_event_cost']     : '0.00';

	if ( $current_venue == $venue_id && isset( $current_meta['_mdjm_event_travel_cost'] ) )	{
		if ( 'manual' != strtolower( $venue_id ) )	{
			return $data;
		}
	}

	// If an existing travel cost exists, subtract from the event cost.
	$price = $price - $travel;

	if ( $venue_id )	{
		$travel_data = mdjm_travel_get_distance( $event_id, $venue_id );
	}

	$travel = ! empty( $travel_data ) ? mdjm_get_travel_cost( $travel_data['distance'] ) : '0.00';
	error_log( $travel, 0 );

	$price = $price + $travel;

	// Filter and return values
	$data['_mdjm_event_cost']        = $price;
	$data['_mdjm_event_deposit']     = mdjm_calculate_deposit( $price );
	$data['_mdjm_event_travel_cost'] = $travel;

	return $data;

} // mdjm_add_travel_cost_to_event
add_filter( 'mdjm_update_event_meta_data', 'mdjm_add_travel_cost_to_event', 5, 3 );
