<?php
/**
 * Cinematic Reveal — the wrapper only; view.js renders the section from
 * data-attributes (same component as the editor) and adds the motion.
 *
 * @var array $attributes
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$id          = wp_unique_id( 'psbReveal-' );
$psb_premium = ( function_exists( 'psIsPremium' ) && psIsPremium() ) ? 'true' : '';
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?> id='<?php echo esc_attr( $id ); ?>' data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>' data-pipecheck='<?php echo esc_attr( $psb_premium ); ?>'></div>
