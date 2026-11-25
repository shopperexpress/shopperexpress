<?php
/**
 * Countdown timers.
 *
 * Timers are defined in Operation Center (SOC → SEO & Content → Countdown Timers)
 * and stored in a single option. They are rendered by the `[countdown id="slug"]`
 * shortcode.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;
use DateTimeImmutable;

/**
 * Class Countdown
 *
 * @package App\Components\Base
 */
class Countdown implements Theme_Component {

	const OPTION = 'shopperexpress_countdown_timers';

	const TYPES          = array( 'fixed', 'evergreen', 'daily' );
	const UNITS          = array( 'days', 'hours', 'minutes', 'seconds' );
	const DURATION_UNITS = array( 'minutes', 'hours', 'days' );
	const EXPIRED        = array( 'hide', 'message', 'keep' );
	const STYLES         = array( 'tiles', 'inline', 'minimal' );
	const ALIGNS         = array( 'left', 'center', 'right' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'countdown', array( $this, 'shortcode' ) );	}

	/**
	 * All saved timers, keyed by slug.
	 *
	 * @return array<string, array>
	 */
	public static function get_timers(): array {
		$timers = get_option( self::OPTION, array() );

		return is_array( $timers ) ? $timers : array();
	}

	/**
	 * Single timer config or null.
	 *
	 * @param string $slug Timer slug.
	 * @return array|null
	 */
	public static function get_timer( string $slug ): ?array {
		$timers = self::get_timers();

		return $timers[ $slug ] ?? null;
	}

	/**
	 * Default timer config.
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return array(
			'name'           => '',
			'type'           => 'fixed',
			'end'            => '',
			'duration'       => 24,
			'duration_unit'  => 'hours',
			'label'          => '',
			'units'          => self::UNITS,
			'expired_action' => 'hide',
			'expired_text'   => '',
			'text_color'     => '',
			'bg_color'       => '',
			'style'          => 'tiles',
			'align'          => 'center',
		);
	}

	/**
	 * Sanitize raw (form) input into a timer config.
	 *
	 * @param array $raw Raw input.
	 * @return array
	 */
	public static function sanitize( array $raw ): array {
		$d = self::defaults();

		$end = '';
		if ( ! empty( $raw['end'] ) ) {
			$date = date_create_immutable_from_format( 'Y-m-d\TH:i', (string) $raw['end'] );
			if ( ! $date ) {
				$date = date_create_immutable_from_format( 'Y-m-d H:i:s', (string) $raw['end'] );
			}
			$end  = $date ? $date->format( 'Y-m-d H:i:s' ) : '';
		}

		if ( 'compact' === ( $raw['style'] ?? '' ) ) {
			$raw['style'] = 'inline';
		}

		$units = array_values( array_intersect( self::UNITS, array_map( 'sanitize_key', (array) ( $raw['units'] ?? array() ) ) ) );

		return array(
			'name'           => sanitize_text_field( $raw['name'] ?? '' ),
			'type'           => in_array( $raw['type'] ?? '', self::TYPES, true ) ? $raw['type'] : $d['type'],
			'end'            => $end,
			'duration'       => max( 1, absint( $raw['duration'] ?? $d['duration'] ) ),
			'duration_unit'  => in_array( $raw['duration_unit'] ?? '', self::DURATION_UNITS, true ) ? $raw['duration_unit'] : $d['duration_unit'],
			'label'          => sanitize_text_field( $raw['label'] ?? '' ),
			'units'          => ! empty( $units ) ? $units : $d['units'],
			'expired_action' => in_array( $raw['expired_action'] ?? '', self::EXPIRED, true ) ? $raw['expired_action'] : $d['expired_action'],
			'expired_text'   => sanitize_text_field( $raw['expired_text'] ?? '' ),
			'text_color'     => (string) sanitize_hex_color( $raw['text_color'] ?? '' ),
			'bg_color'       => (string) sanitize_hex_color( $raw['bg_color'] ?? '' ),
			'style'          => in_array( $raw['style'] ?? '', self::STYLES, true ) ? $raw['style'] : $d['style'],
			'align'          => in_array( $raw['align'] ?? '', self::ALIGNS, true ) ? $raw['align'] : $d['align'],
		);
	}

	/**
	 * `[countdown id="slug" label="" style="" align=""]`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'label' => null,
				'style' => '',
				'align' => '',
			),
			$atts,
			'countdown'
		);

		$html = self::render( sanitize_key( $atts['id'] ), array_filter( $atts, static fn( $v, $k ) => 'id' !== $k && null !== $v && '' !== $v, ARRAY_FILTER_USE_BOTH ) );

		if ( '' === $html && current_user_can( 'manage_options' ) ) {
			return '<!-- countdown: timer "' . esc_html( $atts['id'] ) . '" not found or not configured -->';
		}

		return $html;
	}

	/**
	 * Render a timer's markup (picked up by assets countdown.js).
	 *
	 * @param string $slug      Timer slug.
	 * @param array  $overrides Optional label/style/align overrides.
	 * @return string Empty string when the timer is missing or misconfigured.
	 */
	public static function render( string $slug, array $overrides = array() ): string {
		$timer = self::get_timer( $slug );

		if ( ! $timer ) {
			return '';
		}

		$cfg = array_merge( self::defaults(), $timer );

		if ( 'compact' === $cfg['style'] ) {
			$cfg['style'] = 'inline';
		}

		if ( isset( $overrides['label'] ) ) {
			$cfg['label'] = sanitize_text_field( $overrides['label'] );
		}
		if ( ! empty( $overrides['style'] ) && 'compact' === $overrides['style'] ) {
			$overrides['style'] = 'inline';
		}
		if ( ! empty( $overrides['style'] ) && in_array( $overrides['style'], self::STYLES, true ) ) {
			$cfg['style'] = $overrides['style'];
		}
		if ( ! empty( $overrides['align'] ) && in_array( $overrides['align'], self::ALIGNS, true ) ) {
			$cfg['align'] = $overrides['align'];
		}

		$data = array(
			'data-id'     => $slug,
			'data-type'   => $cfg['type'],
			'data-units'  => implode( ',', $cfg['units'] ),
			'data-action' => $cfg['expired_action'],
		);

		if ( 'evergreen' === $cfg['type'] ) {
			$multipliers = array(
				'minutes' => MINUTE_IN_SECONDS,
				'hours'   => HOUR_IN_SECONDS,
				'days'    => DAY_IN_SECONDS,
			);
			$duration    = (int) $cfg['duration'] * ( $multipliers[ $cfg['duration_unit'] ] ?? HOUR_IN_SECONDS );

			if ( $duration <= 0 ) {
				return '';
			}

			$data['data-duration'] = $duration;
		} elseif ( 'daily' === $cfg['type'] ) {
			$data['data-tz-offset'] = (int) wp_timezone()->getOffset( new DateTimeImmutable( 'now' ) );
		} else {
			$end = $cfg['end'] ? date_create_immutable_from_format( 'Y-m-d H:i:s', $cfg['end'], wp_timezone() ) : false;

			if ( ! $end ) {
				return '';
			}

			$data['data-end'] = $end->getTimestamp();
		}

		$labels = array(
			'days'    => __( 'Days', 'shopperexpress' ),
			'hours'   => __( 'Hours', 'shopperexpress' ),
			'minutes' => __( 'Minutes', 'shopperexpress' ),
			'seconds' => __( 'Seconds', 'shopperexpress' ),
		);

		$style = '';
		if ( $cfg['text_color'] ) {
			$style .= '--cd-color:' . $cfg['text_color'] . ';';
		}
		if ( $cfg['bg_color'] ) {
			$style .= '--cd-bg:' . $cfg['bg_color'] . ';';
		}

		$classes = 'countdown-timer countdown-timer--' . $cfg['style'] . ' countdown-timer--' . $cfg['align'];

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" role="timer" aria-live="off"
			<?php
			echo $style ? ' style="' . esc_attr( $style ) . '"' : '';
			foreach ( $data as $attr => $value ) {
				echo ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
			}
			?>
		>
			<?php if ( '' !== $cfg['label'] ) : ?>
				<p class="countdown-timer__label"><?php echo esc_html( $cfg['label'] ); ?></p>
			<?php endif; ?>
			<div class="countdown-timer__items">
				<?php foreach ( $cfg['units'] as $unit ) : ?>
					<div class="countdown-timer__item" data-unit="<?php echo esc_attr( $unit ); ?>">
						<span class="countdown-timer__value">00</span>
						<span class="countdown-timer__unit"><?php echo esc_html( $labels[ $unit ] ?? $unit ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( 'message' === $cfg['expired_action'] ) : ?>
				<p class="countdown-timer__expired" hidden><?php echo esc_html( $cfg['expired_text'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		// Collapse template whitespace so content filters can't inject <br>/<p> into the markup.
		return (string) preg_replace( '/>\s+</', '><', trim( (string) ob_get_clean() ) );
	}
}
