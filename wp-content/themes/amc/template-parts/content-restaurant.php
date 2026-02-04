<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package AMC
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		
		<?php
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;

		if ( 'post' === get_post_type() ) :
			?>
			<div class="entry-meta">
				<?php
				amc_posted_on();
				amc_posted_by();
				?>
			</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->

	<?php amc_post_thumbnail(); ?>

	<div class="entry-content">
		<?php
		the_content(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'amc' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				wp_kses_post( get_the_title() )
			)
		);
		?>

		<article class="restaurant-details">
        <h1><?php the_title(); ?></h1>
        
        <?php if (function_exists('restaurant_favorite_button')) : ?>
          <div class="restaurant-favorite-section">
            <?php echo restaurant_favorite_button(); ?>
          </div>
        <?php endif; ?>
        
        <?php if (function_exists('restaurant_private_comments')) : ?>
          <div class="restaurant-private-comments-section">
            <?php echo restaurant_private_comments(); ?>
          </div>
        <?php endif; ?>

        <?php 
        // Agafem els valors guardats als ACF
				$location = get_field('location');
        $address = $location['address']; 
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];
        // Obtenim els dies d'obertura (valor del camp)
				$opening_days = get_field('opening_days');
				// Obtenim els subcamps (estructura del grup)
				$field_object = get_field_object('opening_days');

        ?>

        <?php if (get_the_content()) : ?>
            <div class="restaurant-description">
                <h3>Descripció</h3>
                <?php the_content(); ?>
            </div>
        <?php endif; ?>

        <p><strong>Adreça:</strong> <?php echo esc_html($address); ?></p>

        <?php if (!empty($latitude) && !empty($longitude)) : ?>
            <p><strong>Coordenades:</strong> <?php echo esc_html($latitude); ?>, <?php echo esc_html($longitude); ?></p>
        <?php endif; ?>
				
				<?php

				$sub_fields = $field_object['sub_fields']; // Conté els labels

				// Creem un array per mapar nom -> etiqueta
				$days_labels = [];
				foreach ($sub_fields as $sub_field) {
						$days_labels[$sub_field['name']] = $sub_field['label'];
				}

				// Obtenir horaris d'obertura
				$opening_hours = get_field('opening_hours');
				
				// Mostrem només els dies on `is_open` és `true`
				if (!empty($opening_days)) {
						echo "<h3>Horaris d'obertura:</h3><ul>";
						foreach ($opening_days as $day => $is_open) {
								if ($is_open && isset($days_labels[$day])) {
										$day_label = $days_labels[$day];
										$hours_text = '';
										
										// Si hi ha horaris específics per aquest dia
										if (!empty($opening_hours[$day])) {
												$open_time = $opening_hours[$day]['open'] ?? '';
												$close_time = $opening_hours[$day]['close'] ?? '';
												if ($open_time && $close_time) {
														$hours_text = ' (' . esc_html($open_time) . ' - ' . esc_html($close_time) . ')';
												}
										}
										
										echo "<li><strong>" . esc_html($day_label) . "</strong>" . $hours_text . "</li>";
								}
						}
						echo "</ul>";
				} else {
						echo "<p>No s'ha especificat cap dia d'obertura.</p>";
				}
				?>

				<div id="restaurant-map"></div>


					<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
					<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
					<script>
						function initMap() {
							console.log('initMap');
                if (window.map) return; // Evita reinicialitzar el mapa
                
                window.map = L.map('restaurant-map').setView([<?php echo esc_js($latitude) ?>, <?php echo esc_js($longitude) ?>], 20);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                var markers = [];

								// $location = get_field('location', $restaurant->ID);
								<?php if (!empty($latitude) && !empty($longitude)) :
										$lat = esc_js($latitude);
										$lng = esc_js($longitude);
										$name = esc_js(get_the_title());
										// $url = esc_url(get_permalink($restaurant->ID));
										// $google_maps_url = "https://www.google.com/maps?q={$latitude},{$longitude}";
										$google_maps_url = "https://maps.google.com/maps?daddr={$latitude},{$longitude}";

										?>
										markers.push(L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
												.bindPopup('<a href="<?php echo $google_maps_url; ?>" target="_blank">Obrir a Google Maps</a>')
												.addTo(map));
								<?php endif; ?>
            }

						document.addEventListener('DOMContentLoaded', initMap);

					</script>

    </article>
	
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php amc_entry_footer(); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->
