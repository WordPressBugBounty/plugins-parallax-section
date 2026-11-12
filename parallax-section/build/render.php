<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$prefix = 'psbParallaxSection';
$id = wp_unique_id( "$prefix-" );

use PSB\GetCSS as GetCSS;

extract( $attributes );

// Styles 
$bgCSS = PSB\GetCSS::getBackgroundCSS($background);
$paddingCSS = PSB\GetCSS::getSpaceCSS($padding);

$mainSl = "#$id";
// Default effect: the 1920px-tall background layer must stay inside the section
// from the first paint (before view.js runs), not spill over the page.
$isDefault = empty( $selectedTheme ) || 'default' === $selectedTheme;
$clipCSS = $isDefault ? 'position: relative; overflow: hidden;' : '';
$styles = "
  $mainSl{
    min-height: $minHeight;
    $clipCSS
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

?>
<div
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is properly escaped ?>
	<?php echo get_block_wrapper_attributes(); ?>
	id='<?php echo esc_attr( $id ); ?>'
	data-attributes='<?php echo( esc_attr( wp_json_encode( $attributes) ) ); ?>'
  data-pipecheck='<?php echo (esc_attr( wp_json_encode( psIsPremium()) ) ); ?>'
>
  <style>
    <?php echo esc_html( $styles ); ?>
  </style>

  <div class='psbParallaxImg' data-speed='<?php echo esc_attr( $speed ) ?>'></div>

  <div class='psbParallaxSection'>
    <?php // One wrapper inside the flex column (as in the editor), so the blocks' margins collapse normally instead of doubling. ?>
    <div class='psbParallaxSection__content'>
    <?php
    // $content is the inner blocks, already rendered (and filtered when saved) by
    // WordPress — print it as is, like core container blocks do. Running it through
    // wp_kses() again stripped SVG (social icons), form fields, video sources, etc.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $content;
    ?>
    </div>
  </div>
</div>