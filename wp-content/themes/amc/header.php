<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package AMC
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'amc' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="site-branding">
			<?php
			the_custom_logo();
			if ( is_front_page() && is_home() ) :
				?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				<?php
			else :
				?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
				<?php
			endif;
			$amc_description = get_bloginfo( 'description', 'display' );
			if ( $amc_description || is_customize_preview() ) :
				?>
				<p class="site-description"><?php echo $amc_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</div><!-- .site-branding -->

		<nav id="site-navigation" class="main-navigation">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Primary Menu', 'amc' ); ?></button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'menu_id'        => 'primary-menu',
				)
			);
			?>
		</nav><!-- #site-navigation -->

		<?php $theme_assets = get_template_directory_uri() . '/assets/'; ?>
		<div class="theme-switcher" role="radiogroup" aria-label="<?php esc_attr_e( 'Tema visual', 'amc' ); ?>">
			<span class="theme-switcher__label screen-reader-text"><?php esc_html_e( 'Tema', 'amc' ); ?></span>
			<label class="theme-switcher__option theme-switcher__option--checked">
				<input type="radio" name="theme-switcher" value="system" class="theme-switcher__radio" checked aria-label="<?php esc_attr_e( 'Sistema', 'amc' ); ?>">
				<img src="<?php echo esc_url( $theme_assets . 'theme-default.svg' ); ?>" alt="" class="theme-switcher__icon" width="24" height="24">
			</label>
			<label class="theme-switcher__option">
				<input type="radio" name="theme-switcher" value="light" class="theme-switcher__radio" aria-label="<?php esc_attr_e( 'Clar', 'amc' ); ?>">
				<img src="<?php echo esc_url( $theme_assets . 'theme-light.svg' ); ?>" alt="" class="theme-switcher__icon" width="24" height="24">
			</label>
			<label class="theme-switcher__option">
				<input type="radio" name="theme-switcher" value="dark" class="theme-switcher__radio" aria-label="<?php esc_attr_e( 'Fosc', 'amc' ); ?>">
				<img src="<?php echo esc_url( $theme_assets . 'theme-dark.svg' ); ?>" alt="" class="theme-switcher__icon" width="24" height="24">
			</label>
		</div>
	</header><!-- #masthead -->
