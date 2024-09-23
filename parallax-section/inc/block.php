<?php
class PSBBlock{
	function __construct(){
		add_action( 'init', [$this, 'onInit'] );
	}

	function onInit() {
		wp_register_style( 'psb-parallax-style', PSB_DIR_URL . 'dist/style.css', [], PSB_VERSION ); // Style
		wp_register_style( 'psb-parallax-editor-style', PSB_DIR_URL . 'dist/editor.css', [ 'psb-parallax-style' ], PSB_VERSION ); // Backend Style

		register_block_type( __DIR__, [
			'editor_style'		=> 'psb-parallax-editor-style',
			'render_callback'	=> [$this, 'render']
		] ); // Register Block

		wp_set_script_translations( 'psb-parallax-editor-script', 'parallax-section', PSB_DIR_PATH . 'languages' );
	}

	function render( $attributes, $content ){
		extract( $attributes );

		wp_enqueue_style( 'psb-parallax-style' );
		wp_enqueue_script( 'psb-parallax-script', PSB_DIR_URL . 'dist/script.js', [], PSB_VERSION, true );
		wp_set_script_translations( 'psb-parallax-script', 'parallax-section', PSB_DIR_PATH . 'languages' );

		$className = $className ?? '';
		$blockClassName = "wp-block-psb-parallax $className align$align";

		// Styles
		$bgCSS = $this->getBackgroundCSS($background);
		$paddingCSS = $this->getSpaceCSS($padding);

		$mainSl = "#psbParallaxSection-$cId";
		$styles = "
			$mainSl{
				min-height: $minHeight;
			}
			$mainSl .psbParallaxSection{
				justify-content: $verticalAlign;
				text-align: $textAlign;
				min-height: $minHeight;
				padding: $paddingCSS;
			}
			$mainSl .psbParallaxImg{
				$bgCSS
			}
		";
		
		// Style disappearing problem
		global $allowedposttags;
		$allowed_html = wp_parse_args( ['style' => [], 'iframe' => [
			'allowfullscreen' => true,
			'allowpaymentrequest' => true,
			'height' => true,
			'loading' => true,
			'name' => true,
			'referrerpolicy' => true,
			'sandbox' => true,
			'src' => true,
			'srcdoc' => true,
			'width' => true,
			'aria-controls' => true,
            'aria-current' => true,
            'aria-describedby' => true,
            'aria-details' => true,
            'aria-expanded' => true,
            'aria-hidden' => true,
            'aria-label' => true,
            'aria-labelledby' => true,
            'aria-live' => true,
            'class' => true,
            'data-*' => true,
            'dir' => true,
            'hidden' => true,
            'id' => true,
            'lang' => true,
            'style' => true,
            'title' => true,
            'role' => true,
            'xml:lang' => true
		] ], $allowedposttags );

		ob_start(); ?>
		<div class='<?php echo esc_attr( $blockClassName ); ?>' id='psbParallaxSection-<?php echo esc_attr( $cId ) ?>'>
			<style>
				<?php echo esc_html( $styles ); ?>
			</style>

			<div class='psbParallaxImg' data-speed='<?php echo esc_attr( $speed ) ?>'></div>

			<div class='psbParallaxSection'>
				<?php echo wp_kses( $content, $allowed_html ); ?>
			</div>
		</div>

		<?php return ob_get_clean();
	} // Render

	function getBackgroundCSS( $bg, $isSolid = true, $isGradient = true, $isImage = true ) {
		extract( $bg );
		$type = $type ?? 'solid';
		$color = $color ?? '#000000b3';
		$gradient = $gradient ?? 'linear-gradient(135deg, #4527a4, #8344c5)';
		$image = $image ?? [];
		$position = $position ?? 'center center';
		$attachment = $attachment ?? 'initial';
		$repeat = $repeat ?? 'no-repeat';
		$size = $size ?? 'cover';
		$overlayColor = $overlayColor ?? '#000000b3';
	
		$gradientCSS = $isGradient ? "background: $gradient;" : '';
	
		$imgUrl = $image['url'] ?? '';
		$imageCSS = $isImage ? "background: url($imgUrl); background-color: $overlayColor; background-position: $position; background-size: $size; background-repeat: $repeat; background-attachment: $attachment; background-blend-mode: overlay;" : '';
	
		$solidCSS = $isSolid ? "background: $color;" : '';
	
		$styles = 'gradient' === $type ? $gradientCSS : ( 'image' === $type ? $imageCSS : $solidCSS );
	
		return $styles;
	}

	function getSpaceCSS( $space ) {
		extract( $space );
		$side = $side ?? 2;
		$vertical = $vertical ?? '0px';
		$horizontal = $horizontal ?? '0px';
		$top = $top ?? '0px';
		$right = $right ?? '0px';
		$bottom = $bottom ?? '0px';
		$left = $left ?? '0px';
	
		$styles = ( 2 === $side ) ? "$vertical $horizontal" : "$top $right $bottom $left";

		return $styles;
	}
}
new PSBBlock;