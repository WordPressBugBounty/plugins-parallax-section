<?php
/**
 * Parallax Layers — server render. Each direct inner block is wrapped in a
 * .psbLayer whose data-* carry that block's "Parallax Layer" settings (stored
 * in its own psbLayer attribute). The inner blocks' HTML is untouched.
 * Mirrors utils/style.js — keep the two in sync.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'psb_layers_css_val' ) ) {
	function psb_layers_css_val( $v ) {
		return trim( str_replace( [ ';', '{', '}', '<', '>', '"' ], '', (string) $v ) );
	}

	function psb_layers_box( $b ) {
		if ( ! is_array( $b ) ) {
			return '';
		}
		$out = [];
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$v     = isset( $b[ $side ] ) ? psb_layers_css_val( $b[ $side ] ) : '';
			$out[] = '' === $v ? '0' : $v;
		}
		return implode( ' ', $out );
	}

	function psb_layers_bg_style( $bg ) {
		$bg   = is_array( $bg ) ? $bg : [];
		$type = $bg['type'] ?? 'solid';
		if ( 'gradient' === $type ) {
			return 'background-image:' . psb_layers_css_val( $bg['gradient'] ?? '' ) . ';';
		}
		$url = $bg['image']['url'] ?? '';
		if ( 'image' === $type && $url ) {
			$css  = 'background-image:url("' . esc_url( $url ) . '");';
			$css .= 'background-position:' . psb_layers_css_val( $bg['position'] ?? 'center center' ) . ';';
			$css .= 'background-size:' . ( psb_layers_css_val( $bg['size'] ?? '' ) ?: 'cover' ) . ';';
			$css .= 'background-repeat:' . ( psb_layers_css_val( $bg['repeat'] ?? '' ) ?: 'no-repeat' ) . ';';
			if ( ! empty( $bg['attachment'] ) ) {
				$css .= 'background-attachment:' . psb_layers_css_val( $bg['attachment'] ) . ';';
			}
			if ( ! empty( $bg['overlayColor'] ) ) {
				$css .= 'background-color:' . psb_layers_css_val( $bg['overlayColor'] ) . ';background-blend-mode:overlay;';
			}
			return $css;
		}
		return 'background-color:' . psb_layers_css_val( $bg['color'] ?? '' ) . ';';
	}
}

$a = $attributes;

// Same as bgOverhang() in src/utils/motion/layers.js (RANGE 240).
$psb_over = (int) ceil( abs( (float) ( $a['bgSpeed'] ?? 0.3 ) ) * 240 * ( (float) ( $a['intensity'] ?? 100 ) / 100 ) * 1.25 ) + 2;

$psb_valign = [ 'top' => 'flex-start', 'center' => 'center', 'bottom' => 'flex-end' ];
$psb_vars   = [
	'--psb-mh-d'    => psb_layers_css_val( $a['minHeight']['desktop'] ?? '' ),
	'--psb-mh-t'    => psb_layers_css_val( $a['minHeight']['tablet'] ?? '' ),
	'--psb-mh-m'    => psb_layers_css_val( $a['minHeight']['mobile'] ?? '' ),
	'--psb-pad-d'   => psb_layers_box( $a['padding']['desktop'] ?? null ),
	'--psb-pad-t'   => psb_layers_box( $a['padding']['tablet'] ?? null ),
	'--psb-pad-m'   => psb_layers_box( $a['padding']['mobile'] ?? null ),
	'--psb-gap'     => psb_layers_css_val( $a['gap'] ?? '' ),
	'--psb-max'     => 'boxed' === ( $a['contentWidth'] ?? 'boxed' ) ? ( psb_layers_css_val( $a['maxWidth'] ?? '' ) ?: '1140px' ) : 'none',
	'--psb-valign'  => $psb_valign[ $a['verticalAlign'] ?? 'center' ] ?? 'center',
	'--psb-talign'  => psb_layers_css_val( $a['textAlign'] ?? '' ) ?: 'center',
	'--psb-color'   => psb_layers_css_val( $a['textColor'] ?? '' ),
	'--psb-overlay' => psb_layers_css_val( $a['overlayColor'] ?? '' ) ?: 'transparent',
	'--psb-bg-over' => $psb_over . 'px',
];
$psb_style = '';
foreach ( $psb_vars as $k => $v ) {
	if ( '' !== $v ) {
		$psb_style .= $k . ':' . $v . ';';
	}
}

$psb_classes = trim( 'psbLayers' . ( ! empty( $a['clip'] ) ? ' has-clip' : '' ) . ( ! empty( $a['textColor'] ) ? ' has-layer-color' : '' ) );

$psb_motion = [
	'intensity'  => round( (float) ( $a['intensity'] ?? 100 ), 2 ),
	'bgSpeed'    => round( (float) ( $a['bgSpeed'] ?? 0.3 ), 3 ),
	'autoDepth'  => ! empty( $a['autoDepth'] ),
	'smooth'     => ! empty( $a['smooth'] ),
	'mobileMode' => in_array( $a['mobileMode'] ?? 'reduced', [ 'full', 'reduced', 'off' ], true ) ? $a['mobileMode'] : 'reduced',
];

// The CSS variables go in their own style attribute: get_block_wrapper_attributes()
// runs `style` through safecss_filter_attr(), which drops values like rgba()/calc().
// (This block has no style-generating supports, so there's no second style attr.)
$psb_wrapper = get_block_wrapper_attributes( [
	'class'       => $psb_classes,
	'data-motion' => wp_json_encode( $psb_motion ),
] );
?>
<div style="<?php echo esc_attr( $psb_style ); ?>" <?php echo $psb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<div class="psbLayers__backdrop" aria-hidden="true">
		<div class="psbLayers__bg" style="<?php echo esc_attr( psb_layers_bg_style( $a['background'] ?? [] ) ); ?>"></div>
		<div class="psbLayers__overlay"></div>
	</div>
	<div class="psbLayers__inner">
		<?php
		foreach ( $block->inner_blocks as $psb_inner ) {
			$psb_html = $psb_inner->render();
			if ( '' === trim( $psb_html ) ) {
				continue;
			}
			$psb_layer = $psb_inner->parsed_block['attrs']['psbLayer'] ?? [];
			$psb_speed = isset( $psb_layer['speed'] ) && is_numeric( $psb_layer['speed'] ) ? max( -1, min( 1, (float) $psb_layer['speed'] ) ) : '';
			$psb_axis  = isset( $psb_layer['axis'] ) && 'x' === $psb_layer['axis'] ? 'x' : 'y';
			printf(
				'<div class="psbLayer" data-speed="%s" data-axis="%s" data-fade="%s">%s</div>',
				esc_attr( (string) $psb_speed ),
				esc_attr( $psb_axis ),
				! empty( $psb_layer['fade'] ) ? '1' : '0',
				$psb_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block HTML.
			);
		}
		?>
	</div>
</div>
