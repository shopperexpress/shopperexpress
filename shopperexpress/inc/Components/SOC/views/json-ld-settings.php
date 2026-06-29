<?php
/**
 * SOC JSON-LD Schema Builder view.
 *
 * @package Shopperexpress
 * @var array $data  Merged json_ld_field_config from JSON_LD::get_config().
 */

defined( 'ABSPATH' ) || exit;

$mode          = $data['mode'] ?? 'legacy';
$post_types    = $data['post_types'] ?? array();
$archive_limit = (int) ( $data['archive_limit'] ?? 24 );
$vcfg          = $data['vehicle_listings'] ?? $data['vehicle'] ?? array();
$vcfg_used     = $data['vehicle_used_listings'] ?? $data['vehicle'] ?? array();

$all_post_types = array(
	'listings'           => 'listings (New)',
	'used-listings'      => 'used-listings',
	'lease-offers'       => 'lease-offers',
	'finance-offers'     => 'finance-offers',
	'conditional-offers' => 'conditional-offers',
	'research'           => 'research',
);

/* Known ACF field keys for vehicle post types */
$acf_field_options = array(
	'make',
	'model',
	'trim',
	'year',
	'body_style',
	'vin',
	'stock_number',
	'condition',
	'engine',
	'fuel_type',
	'transmission',
	'drive_type',
	'mileage',
	'exterior_color',
	'interior_color',
	'doors',
	'cylinders',
	'price',
	'msrp',
	'invoice',
	'internet_price',
	'dealer_name',
	'dealer_url',
	'ai_vdp_description',
	'description',
	'mpg_city',
	'mpg_highway',
	'certified',
	'features_items',
	'seating_capacity',
	'known_damages',
	'emission_standard',
	'gears',
);

/* Known Intice Nexus API response keys */
$api_field_options = array(
	'make',
	'model',
	'trim',
	'year',
	'body_style',
	'vin',
	'stock',
	'condition',
	'engine',
	'fuel_type',
	'transmission',
	'drive_type',
	'mileage',
	'exterior_color',
	'interior_color',
	'doors',
	'cylinders',
	'price',
	'msrp',
	'internet_price',
	'dealer_name',
	'description',
	'images',
	'features',
	'mpg_city',
	'mpg_highway',
	'certified',
	'status',
	'seating_capacity',
	'known_damages',
	'emission_standard',
	'gears',
);

/* Custom property key suggestions (schema.org/Vehicle props not in the main list) */
$custom_key_options = array(
	'numberOfPreviousOwners',
	'productionDate',
	'dateVehicleFirstRegistered',
	'cargoVolume',
	'weightTotal',
	'payload',
	'speed',
	'emissionsCO2',
	'accelerationTime',
	'tongueWeight',
	'purchaseDate',
	'vehicleModelDate',
);

$custom_properties = $data['custom_properties'] ?? array();

/* Canonical property definition list for the Vehicle schema builder */
$vehicle_props = array(
	'identity'       => array(
		'label' => 'Identity',
		'props' => array(
			array(
				'key'      => 'name',
				'schema'   => 'name',
				'type'     => 'String',
				'source'   => 'WP: post_title',
				'required' => true,
			),
			array(
				'key'         => 'description',
				'schema'      => 'description',
				'type'        => 'String',
				'source'      => 'ACF: ai_vdp_description / API: seo_desc',
				'recommended' => true,
			),
			array(
				'key'         => 'image',
				'schema'      => 'image',
				'type'        => 'String',
				'source'      => 'WP: post_thumbnail / API: images[0]',
				'recommended' => true,
			),
		),
	),
	'classification' => array(
		'label' => 'Classification',
		'props' => array(
			array(
				'key'         => 'brand',
				'schema'      => 'brand → Brand',
				'type'        => 'Object',
				'source'      => 'ACF: make',
				'recommended' => true,
			),
			array(
				'key'    => 'model',
				'schema' => 'model',
				'type'   => 'String',
				'source' => 'ACF: model',
			),
			array(
				'key'    => 'vehicleConfiguration',
				'schema' => 'vehicleConfiguration',
				'type'   => 'String',
				'source' => 'ACF: trim',
			),
			array(
				'key'    => 'vehicleModelDate',
				'schema' => 'vehicleModelDate',
				'type'   => 'String',
				'source' => 'ACF: year',
			),
			array(
				'key'    => 'bodyType',
				'schema' => 'bodyType',
				'type'   => 'String',
				'source' => 'ACF: body_style',
			),
		),
	),
	'technical'      => array(
		'label' => 'Technical Specs',
		'props' => array(
			array(
				'key'         => 'vehicleIdentificationNumber',
				'schema'      => 'vehicleIdentificationNumber',
				'type'        => 'String',
				'source'      => 'ACF: vin',
				'recommended' => true,
			),
			array(
				'key'    => 'vehicleEngine',
				'schema' => 'vehicleEngine → EngineSpecification',
				'type'   => 'Object',
				'source' => 'ACF: engine',
			),
			array(
				'key'    => 'fuelType',
				'schema' => 'fuelType',
				'type'   => 'String',
				'source' => 'ACF: fuel_type',
			),
			array(
				'key'    => 'vehicleTransmission',
				'schema' => 'vehicleTransmission',
				'type'   => 'String',
				'source' => 'ACF: transmission',
			),
			array(
				'key'    => 'mileageFromOdometer',
				'schema' => 'mileageFromOdometer → QuantitativeValue',
				'type'   => 'Object',
				'source' => 'ACF: mileage · unitCode: SMI',
			),
		),
	),
	'appearance'     => array(
		'label' => 'Appearance',
		'props' => array(
			array(
				'key'    => 'color',
				'schema' => 'color',
				'type'   => 'String',
				'source' => 'ACF: exterior_color',
			),
			array(
				'key'    => 'vehicleInteriorColor',
				'schema' => 'vehicleInteriorColor',
				'type'   => 'String',
				'source' => 'ACF: interior_color',
			),
		),
	),
	'pricing'        => array(
		'label' => 'Pricing & Availability',
		'props' => array(
			array(
				'key'         => 'offers',
				'schema'      => 'offers → Offer',
				'type'        => 'Object',
				'source'      => 'ACF: price · dealer_name · dealer_url · auto: post_type',
				'recommended' => true,
			),
		),
	),
	'features'       => array(
		'label' => 'Features (additionalProperty)',
		'props' => array(
			array(
				'key'    => 'additionalProperty',
				'schema' => 'additionalProperty → PropertyValue[]',
				'type'   => 'Array',
				'source' => 'ACF: features_items (sorted by ranking ↓) · Options: feature_list_chromedata',
			),
		),
	),
	'extended'       => array(
		'label' => 'Extended Specs',
		'props' => array(
			array(
				'key'    => 'numberOfDoors',
				'schema' => 'numberOfDoors',
				'type'   => 'String',
				'source' => 'ACF: doors',
			),
			array(
				'key'    => 'driveWheelConfiguration',
				'schema' => 'driveWheelConfiguration',
				'type'   => 'String',
				'source' => 'ACF: drive_type',
			),
			array(
				'key'    => 'vehicleSeatingCapacity',
				'schema' => 'vehicleSeatingCapacity',
				'type'   => 'Number',
				'source' => 'ACF: seating_capacity',
			),
			array(
				'key'    => 'fuelConsumption',
				'schema' => 'fuelConsumption → QuantitativeValue',
				'type'   => 'Object',
				'source' => 'ACF: mpg_city · unitText: mpg',
			),
			array(
				'key'    => 'knownVehicleDamages',
				'schema' => 'knownVehicleDamages',
				'type'   => 'String',
				'source' => 'ACF: known_damages',
			),
			array(
				'key'    => 'vehicleSpecialUsage',
				'schema' => 'vehicleSpecialUsage',
				'type'   => 'String',
				'source' => 'ACF: certified',
			),
			array(
				'key'    => 'meetsEmissionStandard',
				'schema' => 'meetsEmissionStandard',
				'type'   => 'String',
				'source' => 'ACF: emission_standard',
			),
			array(
				'key'    => 'numberOfForwardGears',
				'schema' => 'numberOfForwardGears',
				'type'   => 'Number',
				'source' => 'ACF: gears',
			),
		),
	),
);
?>

<style>
/* ── Builder shell (scoped inside SOC panel) ─────────────────────── */
.jl-wrap *,
.jl-wrap *::before,
.jl-wrap *::after { box-sizing: border-box; }

.jl-wrap {
	--jl-ground:      #EEF2F7;
	--jl-surface:     #FFFFFF;
	--jl-surface-2:   #F5F8FC;
	--jl-text:        #0D1B2A;
	--jl-muted:       #5A7184;
	--jl-faint:       #9BB0C2;
	--jl-accent:      #1847A8;
	--jl-accent-lt:   #E8EFFE;
	--jl-accent-mid:  #3461C8;
	--jl-enabled:     #0D9488;
	--jl-enabled-lt:  #E6F7F5;
	--jl-disabled:    #CBD5E1;
	--jl-border:      #D4DDE8;
	--jl-border-s:    #B0BDCB;
	--jl-danger:      #DC2626;
	--jl-warn:        #D97706;
	--jl-code-bg:     #0D1B2A;
	--jl-code-2:      #142030;
	--jl-code-b:      #1E3045;
	--jl-font-ui:     'Segoe UI Variable','Segoe UI',system-ui,sans-serif;
	--jl-font-mono:   ui-monospace,'Cascadia Code','Fira Code',Consolas,'Courier New',monospace;

	font-family: var(--jl-font-ui);
	font-size: 13px;
	color: var(--jl-text);
	line-height: 1.5;
}

/* ── Mode bar ── */
.jl-modebar {
	display: flex;
	align-items: center;
	gap: 14px;
	padding: 10px 18px;
	background: var(--jl-code-bg);
	border-radius: 8px;
	margin-bottom: 16px;
}
.jl-modebar-label {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	font-weight: 700;
	letter-spacing: .1em;
	text-transform: uppercase;
	color: rgba(255,255,255,.45);
}
.jl-seg {
	display: flex;
	background: var(--jl-code-2);
	border: 1px solid var(--jl-code-b);
	border-radius: 6px;
	overflow: hidden;
}
.jl-seg label {
	display: flex;
	align-items: center;
	gap: 7px;
	padding: 6px 14px;
	font-size: 12px;
	font-weight: 600;
	color: rgba(255,255,255,.4);
	cursor: pointer;
	transition: background .15s, color .15s;
	user-select: none;
}
.jl-seg label:hover { color: rgba(255,255,255,.7); }
.jl-seg input[type=radio] { display: none; }
.jl-seg input:checked + span { color: #fff; }
.jl-seg label:has(input:checked) {
	background: var(--jl-accent);
	color: #fff;
}
.jl-seg-dot {
	width: 7px; height: 7px;
	border-radius: 50%;
	background: rgba(255,255,255,.25);
	flex-shrink: 0;
}
.jl-seg label:has(input[value="builder"]:checked) .jl-seg-dot { background: #34D399; }
.jl-modebar-sep { flex: 1; }
.jl-modebar-note {
	font-size: 11px;
	color: rgba(255,255,255,.35);
	font-family: var(--jl-font-mono);
}
#jl-mode-note-builder { display: none; }

/* ── Layout: sidebar + builder + preview ── */
.jl-shell {
	display: grid;
	grid-template-columns: 200px 1fr 500px;
	gap: 0;
	border: 1px solid var(--jl-border);
	border-radius: 10px;
	overflow: hidden;
	min-height: 600px;
	background: var(--jl-ground);
}

/* ── Sidebar ── */
.jl-sidebar {
	background: var(--jl-surface);
	border-right: 1px solid var(--jl-border);
	overflow-y: auto;
	padding-bottom: 20px;
}
.jl-sb-section {
	padding: 14px 14px 6px;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: .1em;
	text-transform: uppercase;
	color: var(--jl-faint);
}
.jl-sb-item {
	display: flex;
	align-items: center;
	gap: 9px;
	padding: 8px 14px;
	cursor: pointer;
	border-left: 3px solid transparent;
	transition: background .1s, border-color .1s;
}
.jl-sb-item:hover { background: var(--jl-ground); }
.jl-sb-item.active {
	background: var(--jl-accent-lt);
	border-left-color: var(--jl-accent);
}
.jl-sb-icon {
	width: 26px; height: 26px;
	border-radius: 5px;
	display: flex; align-items: center; justify-content: center;
	font-size: 13px;
	background: var(--jl-ground);
	flex-shrink: 0;
}
.jl-sb-item.active .jl-sb-icon { background: var(--jl-accent-lt); }
.jl-sb-info { flex: 1; min-width: 0; }
.jl-sb-name {
	font-size: 12px;
	font-weight: 600;
	color: var(--jl-text);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.jl-sb-item.active .jl-sb-name { color: var(--jl-accent); }
.jl-sb-type {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	color: var(--jl-faint);
}
.jl-sb-count {
	font-size: 10px;
	color: var(--jl-muted);
	background: var(--jl-ground);
	border-radius: 100px;
	padding: 1px 6px;
	font-family: var(--jl-font-mono);
}
.jl-sb-divider { height: 1px; background: var(--jl-border); margin: 8px 14px; }
.jl-global-row {
	display: flex;
	align-items: center;
	gap: 7px;
	padding: 5px 14px;
	font-size: 12px;
	color: var(--jl-muted);
}
.jl-global-row input[type=checkbox] { accent-color: var(--jl-accent); }
.jl-num-input {
	width: 50px;
	font-family: var(--jl-font-mono);
	font-size: 12px;
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 2px 6px;
	color: var(--jl-text);
	background: var(--jl-surface);
}
.jl-num-input:focus { outline: none; border-color: var(--jl-accent); box-shadow: 0 0 0 3px var(--jl-accent-lt); }

/* ── Builder ── */
.jl-builder {
	overflow-y: auto;
	padding: 0 0 40px;
	background: var(--jl-ground);
}
/* ── Post-type schema tab switcher ── */
.jl-schema-tabs {
	display: flex;
	gap: 4px;
	padding: 12px 16px 0;
	border-bottom: 1px solid var(--jl-border);
	background: var(--jl-surface);
}
.jl-schema-tabs[style*="none"] { display: none; }
.jl-stab {
	padding: 7px 14px;
	font-size: 12px;
	font-weight: 600;
	border: none;
	border-radius: 6px 6px 0 0;
	background: transparent;
	color: var(--jl-muted);
	cursor: pointer;
	border-bottom: 2px solid transparent;
	margin-bottom: -1px;
	transition: color .15s, border-color .15s, background .15s;
}
.jl-stab:hover { color: var(--jl-text); background: var(--jl-ground); }
.jl-stab.active {
	color: var(--jl-accent);
	border-bottom-color: var(--jl-accent);
	background: var(--jl-accent-lt);
}

.jl-builder-head {
	position: sticky;
	top: 0;
	background: var(--jl-ground);
	border-bottom: 1px solid var(--jl-border);
	padding: 13px 20px;
	z-index: 10;
	display: flex;
	align-items: center;
	gap: 10px;
}
.jl-builder-title { font-size: 13px; font-weight: 700; }
.jl-builder-uri {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	color: var(--jl-muted);
	background: var(--jl-surface);
	border: 1px solid var(--jl-border);
	padding: 2px 8px;
	border-radius: 100px;
}
.jl-builder-sep { flex: 1; }
.jl-builder-stats { font-size: 11px; color: var(--jl-muted); }
.jl-builder-stats strong { color: var(--jl-enabled); }

.jl-group { margin: 18px 20px 0; }
.jl-group-label {
	font-size: 10px;
	font-weight: 700;
	letter-spacing: .1em;
	text-transform: uppercase;
	color: var(--jl-faint);
	padding: 0 0 7px;
	border-bottom: 1px solid var(--jl-border);
	margin-bottom: 2px;
}

/* ── Property card ── */
.jl-prop {
	display: grid;
	grid-template-columns: 42px 1fr auto;
	align-items: start;
	border-bottom: 1px solid var(--jl-border);
	background: var(--jl-surface);
	transition: background .1s, opacity .15s;
}
.jl-prop:first-of-type { border-radius: 8px 8px 0 0; }
.jl-prop:last-of-type  { border-radius: 0 0 8px 8px; border-bottom: none; }
.jl-prop:only-of-type  { border-radius: 8px; }
.jl-prop:hover { background: #FAFCFF; }
.jl-prop.jl-off { opacity: .5; }

.jl-toggle-cell {
	display: flex;
	align-items: flex-start;
	justify-content: center;
	padding: 13px 0 0 13px;
}
.jl-toggle {
	position: relative;
	width: 30px;
	height: 17px;
	flex-shrink: 0;
}
.jl-toggle input { opacity: 0; width: 0; height: 0; }
.jl-track {
	position: absolute; inset: 0;
	background: var(--jl-disabled);
	border-radius: 100px;
	cursor: pointer;
	transition: background .2s;
}
.jl-toggle input:checked + .jl-track { background: var(--jl-enabled); }
.jl-thumb {
	position: absolute;
	top: 2px; left: 2px;
	width: 13px; height: 13px;
	background: #fff;
	border-radius: 50%;
	transition: transform .2s;
	pointer-events: none;
	box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.jl-toggle input:checked ~ .jl-thumb { transform: translateX(13px); }

.jl-prop-body { padding: 11px 11px 11px 9px; min-width: 0; }
.jl-key-row {
	display: flex;
	align-items: center;
	gap: 7px;
	margin-bottom: 2px;
}
.jl-key {
	font-family: var(--jl-font-mono);
	font-size: 12px;
	font-weight: 600;
	color: var(--jl-accent);
}
.jl-key::before { content: '"'; color: var(--jl-faint); }
.jl-key::after  { content: '":'; color: var(--jl-faint); }
.jl-vtype {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	padding: 1px 5px;
	border-radius: 4px;
	background: var(--jl-ground);
	color: var(--jl-muted);
	border: 1px solid var(--jl-border);
}
.jl-vtype.obj   { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
.jl-vtype.array { background: #EDE9FE; color: #5B21B6; border-color: #DDD6FE; }
.jl-uri {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	color: var(--jl-faint);
	margin-bottom: 5px;
}
.jl-uri-sep { color: var(--jl-border-s); margin: 0 3px; }
.jl-source {
	display: flex;
	align-items: center;
	gap: 5px;
	flex-wrap: wrap;
	font-size: 11px;
	color: var(--jl-muted);
}
.jl-source-field {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	background: var(--jl-surface-2);
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 2px 7px;
	color: var(--jl-text);
}
.jl-feat-limit {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-top: 7px;
}
.jl-feat-limit-label {
	font-size: 10px;
	color: var(--jl-faint);
	text-transform: uppercase;
	letter-spacing: .06em;
}
.jl-feat-limit-hint { font-size: 10px; color: var(--jl-faint); font-family: var(--jl-font-mono); }

/* ── Source editor ── */
.jl-src-toggle {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	margin-top: 6px;
	padding: 2px 8px 2px 6px;
	font-size: 10px;
	font-weight: 600;
	letter-spacing: .04em;
	text-transform: uppercase;
	color: var(--jl-muted);
	background: var(--jl-ground);
	border: 1px solid var(--jl-border);
	border-radius: 100px;
	cursor: pointer;
	transition: color .12s, border-color .12s, background .12s;
}
.jl-src-toggle:hover { color: var(--jl-accent); border-color: var(--jl-accent); background: var(--jl-accent-lt); }
.jl-src-toggle[aria-expanded="true"] { color: var(--jl-accent); border-color: var(--jl-accent); background: var(--jl-accent-lt); }
.jl-src-toggle svg { flex-shrink: 0; transition: transform .15s; }
.jl-src-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }

.jl-src-editor {
	display: none;
	margin-top: 8px;
	padding: 10px 12px 12px;
	background: var(--jl-surface-2);
	border: 1px solid var(--jl-border);
	border-radius: 6px;
	gap: 8px;
}
.jl-src-editor.jl-src-open { display: flex;
	flex-direction: column;
	gap: 15px; }
.jl-src-row {
	display: grid;
	grid-template-columns: 90px 1fr;
	align-items: center;
	gap: 6px;
}
.jl-src-row label {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .07em;
	color: var(--jl-faint);
}
.jl-src-input {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 4px 8px;
	color: var(--jl-text);
	background: var(--jl-surface);
	width: 100%;
}
.jl-src-input:focus { outline: none; border-color: var(--jl-accent); box-shadow: 0 0 0 3px var(--jl-accent-lt); }
.jl-src-input::placeholder { color: var(--jl-faint); opacity: 1; }
.jl-src-hint {
	font-size: 10px;
	color: var(--jl-faint);
	font-family: var(--jl-font-mono);
	grid-column: 2;
}
.jl-src-input::-webkit-calendar-picker-indicator { opacity: .4; cursor: pointer; }
.jl-src-input::-webkit-list-button { opacity: .4; }
.jl-src-static-row { grid-template-columns: 90px 1fr; }
.jl-src-static-note {
	grid-column: 2;
	font-size: 10px;
	color: var(--jl-warn);
	font-family: var(--jl-font-mono);
	display: none;
}
.jl-src-input[data-src="static_value"]:not(:placeholder-shown) ~ .jl-src-static-note { display: block; }

/* ── Template variable chips ── */
.jl-vars-bar {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 4px;
	grid-column: 1 / -1;
	padding-top: 2px;
}
.jl-vars-label {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .07em;
	color: var(--jl-faint);
	flex-shrink: 0;
	margin-right: 2px;
}
.jl-var-chip {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	padding: 2px 7px;
	border-radius: 100px;
	border: 1px solid var(--jl-border);
	background: var(--jl-surface);
	color: var(--jl-accent);
	cursor: pointer;
	transition: background .1s, border-color .1s;
	line-height: 1.5;
}
.jl-var-chip:hover { background: var(--jl-accent-lt); border-color: var(--jl-accent); }

/* ── Static value resolved preview ── */
.jl-static-preview {
	grid-column: 2;
	font-family: var(--jl-font-mono);
	font-size: 10px;
	color: var(--jl-enabled);
	background: var(--jl-enabled-lt);
	border: 1px solid #A7F3D0;
	border-radius: 4px;
	padding: 3px 8px;
	display: none;
	word-break: break-all;
}
.jl-static-preview.jl-sp-show { display: block; }

.jl-meta-cell {
	padding: 13px 13px 0 0;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 4px;
}
.jl-required    { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--jl-danger); opacity: .7; }
.jl-recommended { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--jl-warn);   opacity: .8; }

/* ── Preview panel ── */
.jl-preview {
	background: var(--jl-code-bg);
	border-left: 1px solid var(--jl-code-b);
	display: flex;
	flex-direction: column;
	overflow: hidden;
}
.jl-preview-head {
	padding: 9px 14px;
	border-bottom: 1px solid var(--jl-code-b);
	display: flex;
	align-items: center;
	gap: 7px;
	background: var(--jl-code-2);
	flex-shrink: 0;
}
.jl-preview-dot { width: 8px; height: 8px; border-radius: 50%; }
.jl-preview-title {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	color: rgba(255,255,255,.4);
	flex: 1;
	margin-left: 6px;
	letter-spacing: .04em;
}
.jl-copy-btn {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	background: transparent;
	border: 1px solid rgba(255,255,255,.15);
	color: rgba(255,255,255,.4);
	border-radius: 4px;
	padding: 3px 8px;
	cursor: pointer;
	transition: color .15s, border-color .15s;
}
.jl-copy-btn:hover { color: rgba(255,255,255,.85); border-color: rgba(255,255,255,.35); }
.jl-copy-btn.jl-copied { color: #34D399; border-color: #34D399; }

/* ── Demo / Real toggle ── */
.jl-preview-mode-toggle {
	display: flex;
	gap: 1px;
	background: rgba(255,255,255,.07);
	border-radius: 4px;
	padding: 2px;
	margin-left: 4px;
}
.jl-pmode-btn {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	background: transparent;
	border: none;
	color: rgba(255,255,255,.4);
	padding: 2px 8px;
	border-radius: 3px;
	cursor: pointer;
	transition: background .15s, color .15s;
}
.jl-pmode-btn.jl-pmode-active {
	background: rgba(255,255,255,.12);
	color: rgba(255,255,255,.85);
}
/* ── Real picker ── */
.jl-real-picker {
	display: none;
	grid-template-columns: 1fr 1fr auto auto;
	gap: 6px;
	padding: 6px 10px;
	background: rgba(255,255,255,.04);
	border-bottom: 1px solid rgba(255,255,255,.07);
	flex-shrink: 0;
	box-sizing: border-box;
	width: 100%;
}
.jl-real-picker.jl-real-active { display: grid; }
.jl-real-select {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	background: rgba(255,255,255,.08);
	border: 1px solid rgba(255,255,255,.12);
	border-radius: 4px;
	color: rgba(255,255,255,.75);
	padding: 3px 6px;
	cursor: pointer;
	min-width: 0;
	width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.jl-real-select:focus { outline: none; border-color: var(--jl-accent); }
.jl-real-status {
	font-size: 10px;
	color: rgba(255,255,255,.4);
	font-family: var(--jl-font-mono);
	white-space: nowrap;
	align-self: center;
}
.jl-real-status.jl-rs-ok  { color: #34D399; }
.jl-real-status.jl-rs-err { color: #F87171; }
.jl-real-fetch-btn {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	background: var(--jl-accent);
	border: none;
	border-radius: 4px;
	color: #fff;
	padding: 3px 10px;
	cursor: pointer;
	flex-shrink: 0;
}
.jl-real-fetch-btn:hover { background: var(--jl-accent-mid); }

.jl-preview-body {
	flex: 1;
	overflow-y: auto;
	padding: 14px 14px 14px 10px;
	font-family: var(--jl-font-mono);
	font-size: 11px;
	line-height: 1.75;
	color: #9FBFD8;
	white-space: pre;
	tab-size: 2;
}
.jl-preview-body::-webkit-scrollbar { width: 5px; }
.jl-preview-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

/* ── JSON tree tokens ── */
.j-key    { color: #7DD3FC; }
.j-str    { color: #86EFAC; }
.j-num    { color: #FCA5A5; }
.j-bool   { color: #C084FC; }
.j-null   { color: #F9A8D4; opacity: .7; }
.j-url    { color: #FDE68A; }
.j-type   { color: #F9A8D4; }
.j-punct  { color: rgba(255,255,255,.25); }
.j-bracket{ color: rgba(255,255,255,.5); }

/* ── Collapse toggle ── */
.j-toggle {
	display: inline-block;
	width: 14px;
	text-align: center;
	cursor: pointer;
	color: rgba(255,255,255,.3);
	font-size: 9px;
	line-height: inherit;
	user-select: none;
	transition: color .1s;
	vertical-align: baseline;
}
.j-toggle:hover { color: rgba(255,255,255,.8); }

/* ── Collapsed hint: { 3 props } ── */
.j-hint {
	color: rgba(255,255,255,.3);
	font-style: italic;
}
.j-hint .j-bracket { font-style: normal; }

/* ── Collapse-all / Expand-all controls ── */
.jl-tree-ctrl {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	background: transparent;
	border: 1px solid rgba(255,255,255,.12);
	color: rgba(255,255,255,.35);
	border-radius: 4px;
	padding: 2px 7px;
	cursor: pointer;
	transition: color .12s, border-color .12s;
}
.jl-tree-ctrl:hover { color: rgba(255,255,255,.75); border-color: rgba(255,255,255,.35); }
.jl-preview-foot {
	padding: 7px 14px;
	border-top: 1px solid var(--jl-code-b);
	background: var(--jl-code-2);
	display: flex;
	align-items: center;
	gap: 12px;
	flex-shrink: 0;
}
.jl-preview-stat {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	color: rgba(255,255,255,.3);
}
.jl-preview-stat strong { color: rgba(255,255,255,.5); }
.jl-preview-validate {
	margin-left: auto;
	font-family: var(--jl-font-mono);
	font-size: 10px;
	background: transparent;
	border: 1px solid rgba(255,255,255,.1);
	color: rgba(255,255,255,.35);
	border-radius: 4px;
	padding: 3px 8px;
	cursor: pointer;
	transition: color .15s, border-color .15s;
}
.jl-preview-validate:hover { color: #FDE68A; border-color: #FDE68A; }

/* ── Custom properties ── */
.jl-custom-group { margin-bottom: 24px; }
.jl-custom-row {
	display: grid;
	grid-template-columns: 1fr 1fr auto;
	gap: 6px;
	align-items: center;
	background: var(--jl-surface);
	border-bottom: 1px solid var(--jl-border);
	padding: 8px 12px;
}
.jl-custom-row:first-child { border-radius: 8px 8px 0 0; }
.jl-custom-row:last-child  { border-radius: 0 0 8px 8px; border-bottom: none; }
.jl-custom-row:only-child  { border-radius: 8px; }
.jl-custom-key {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 5px 8px;
	color: var(--jl-accent);
	background: var(--jl-surface);
	width: 100%;
}
.jl-custom-val {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 5px 8px;
	color: var(--jl-text);
	background: var(--jl-surface);
	width: 100%;
}
.jl-custom-key:focus,
.jl-custom-val:focus { outline: none; border-color: var(--jl-accent); box-shadow: 0 0 0 3px var(--jl-accent-lt); }
.jl-custom-key::placeholder,
.jl-custom-val::placeholder { color: var(--jl-faint); opacity: 1; }
.jl-custom-remove {
	width: 24px; height: 24px;
	border-radius: 4px;
	border: 1px solid var(--jl-border);
	background: transparent;
	color: var(--jl-faint);
	font-size: 13px;
	line-height: 1;
	cursor: pointer;
	display: flex; align-items: center; justify-content: center;
	flex-shrink: 0;
	transition: color .1s, border-color .1s, background .1s;
}
.jl-custom-remove:hover { color: var(--jl-danger); border-color: var(--jl-danger); background: #FEF2F2; }
.jl-add-custom-btn {
	display: flex;
	align-items: center;
	gap: 5px;
	margin: 8px 20px 0;
	padding: 6px 14px;
	font-size: 12px;
	font-weight: 600;
	color: var(--jl-accent);
	background: var(--jl-accent-lt);
	border: 1px dashed var(--jl-accent);
	border-radius: 6px;
	cursor: pointer;
	transition: background .1s;
}
.jl-add-custom-btn:hover { background: #D4E2FB; }
.jl-custom-empty {
	padding: 10px 12px;
	font-size: 11px;
	color: var(--jl-faint);
	background: var(--jl-surface);
	border-radius: 8px;
	text-align: center;
	border: 1px dashed var(--jl-border);
}

/* ── Save button (lives inside modebar) ── */
.jl-save-btn {
	background: var(--jl-accent);
	border: none;
	color: #fff;
	font-family: var(--jl-font-ui);
	font-size: 12px;
	font-weight: 600;
	padding: 6px 18px;
	border-radius: 6px;
	cursor: pointer;
	transition: background .15s;
	flex-shrink: 0;
}
.jl-save-btn:hover  { background: var(--jl-accent-mid); }
.jl-save-btn.jl-saving { background: var(--jl-muted); cursor: wait; }
.jl-save-btn.jl-saved  { background: var(--jl-enabled); }
.jl-notice {
	font-size: 11px;
	font-family: var(--jl-font-mono);
	color: #34D399;
	display: none;
}
.jl-notice.jl-show { display: block; }

/* ── Offers sub-editor ── */
.jl-offers-sub {
	margin-top: 10px;
	padding: 10px 12px 12px;
	background: var(--jl-surface-2);
	border: 1px solid var(--jl-border);
	border-radius: 6px;
	display: grid;
	gap: 7px;
}
.jl-osub-label {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .1em;
	color: var(--jl-accent);
	margin-bottom: 2px;
	display: flex;
	align-items: center;
	gap: 6px;
}
.jl-osub-label::after {
	content: '';
	flex: 1;
	height: 1px;
	background: var(--jl-border);
}
.jl-osub-row {
	display: grid;
	grid-template-columns: 110px 1fr;
	align-items: center;
	gap: 6px;
}
.jl-osub-row label {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .07em;
	color: var(--jl-faint);
}
.jl-osub-title {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .07em;
	color: var(--jl-accent);
	margin-bottom: 8px;
}
.jl-osub-field {
	font-family: var(--jl-font-mono);
	font-size: 11px;
	border: 1px solid var(--jl-border);
	border-radius: 4px;
	padding: 4px 8px;
	color: var(--jl-text);
	background: var(--jl-surface);
	width: 100%;
}
.jl-osub-field:focus { outline: none; border-color: var(--jl-accent); box-shadow: 0 0 0 3px var(--jl-accent-lt); }
.jl-osub-field::placeholder { color: var(--jl-faint); opacity: 1; }
select.jl-osub-field { cursor: pointer; }

/* ── Sidebar info tooltip ── */
.jl-sb-hint {
	font-size: 10px;
	color: var(--jl-faint);
	padding: 3px 14px 8px;
	line-height: 1.5;
}

/* ── Schema info panel (non-vehicle selected) ── */
.jl-schema-info {
	margin: 18px 20px;
	padding: 16px 18px;
	background: var(--jl-surface);
	border: 1px solid var(--jl-border);
	border-radius: 8px;
	display: none;
}
.jl-schema-info.active { display: block; }

.jl-schema-info-title {
	font-size: 13px;
	font-weight: 700;
	margin-bottom: 6px;
	display: flex;
	align-items: center;
	gap: 8px;
}
.jl-schema-info-badge {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	padding: 2px 7px;
	border-radius: 100px;
	background: var(--jl-ground);
	border: 1px solid var(--jl-border);
	color: var(--jl-muted);
}
.jl-schema-info-desc { font-size: 12px; color: var(--jl-muted); line-height: 1.6; margin-bottom: 10px; }
.jl-schema-info-props { display: flex; flex-wrap: wrap; gap: 5px; }
.jl-schema-info-prop {
	font-family: var(--jl-font-mono);
	font-size: 10px;
	padding: 2px 8px;
	border-radius: 4px;
	background: var(--jl-accent-lt);
	color: var(--jl-accent);
	border: 1px solid #C7D8F8;
}
.jl-schema-info-soon {
	margin-top: 12px;
	padding: 8px 12px;
	background: #FEF3C7;
	border: 1px solid #FDE68A;
	border-radius: 6px;
	font-size: 11px;
	color: #92400E;
}
.jl-notice {
	font-size: 12px;
	color: var(--jl-enabled);
	display: none;
}
.jl-notice.jl-show { display: block; }

/* Builder inactive overlay */
.jl-inactive-notice {
	font-size: 11px;
	color: var(--jl-warn);
	background: #FEF3C7;
	border: 1px solid #FDE68A;
	border-radius: 6px;
	padding: 7px 12px;
	margin: 14px 20px 0;
	display: none;
}
.jl-mode-legacy .jl-inactive-notice { display: block; }
.jl-mode-legacy .jl-prop { opacity: .45; pointer-events: none; }
.jl-mode-legacy .jl-num-input { opacity: .45; pointer-events: none; }
.jl-mode-legacy .jl-global-row input { opacity: .45; pointer-events: none; }

@media (prefers-reduced-motion: reduce) {
	.jl-wrap * { transition: none !important; }
}
</style>

<!-- Datalists for source editor autocomplete -->
<datalist id="jl-acf-keys">
	<?php foreach ( $acf_field_options as $opt ) : ?>
		<option value="<?php echo esc_attr( $opt ); ?>">
	<?php endforeach; ?>
</datalist>
<datalist id="jl-api-keys">
	<?php foreach ( $api_field_options as $opt ) : ?>
		<option value="<?php echo esc_attr( $opt ); ?>">
	<?php endforeach; ?>
</datalist>
<datalist id="jl-custom-keys">
	<?php foreach ( array_merge( $acf_field_options, $custom_key_options ) as $opt ) : ?>
		<option value="<?php echo esc_attr( $opt ); ?>">
	<?php endforeach; ?>
</datalist>

<div class="jl-wrap" id="jl-root">

	<div id="soc-action-notice" class="soc-notice" role="alert"></div>

	<!-- Mode toggle bar -->
	<div class="jl-modebar">
		<span class="jl-modebar-label">Output mode</span>
		<div class="jl-seg">
			<label>
				<input type="radio" name="jl_mode" value="legacy" <?php checked( $mode, 'legacy' ); ?>>
				<div class="jl-seg-dot"></div>
				<span>Legacy (current)</span>
			</label>
			<label>
				<input type="radio" name="jl_mode" value="builder" <?php checked( $mode, 'builder' ); ?>>
				<div class="jl-seg-dot"></div>
				<span>Builder</span>
			</label>
		</div>
		<div class="jl-modebar-sep"></div>
		<span class="jl-modebar-note" id="jl-mode-note-legacy">Builder config saved but not applied to output</span>
		<span class="jl-modebar-note" id="jl-mode-note-builder">Builder config drives all JSON-LD output</span>
		<span class="jl-notice" id="jl-notice">Saved ✓</span>
		<button class="jl-save-btn" id="jl-save-btn" type="button">Save Changes</button>
	</div>

	<!-- Three-column shell -->
	<div class="jl-shell <?php echo 'builder' === $mode ? 'jl-mode-builder' : 'jl-mode-legacy'; ?>" id="jl-shell">

		<!-- Sidebar -->
		<aside class="jl-sidebar">
			<div class="jl-sb-section">Inventory Post Types</div>

			<div class="jl-sb-item active" data-pt="listings">
				<div class="jl-sb-icon">🆕</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">New Vehicles</div>
					<div class="jl-sb-type">post type: listings</div>
				</div>
			</div>
			<div class="jl-sb-item" data-pt="used-listings">
				<div class="jl-sb-icon">🔄</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Used Vehicles</div>
					<div class="jl-sb-type">post type: used-listings</div>
				</div>
			</div>

			<div class="jl-sb-divider"></div>
			<div class="jl-sb-section">Other Schema Types</div>

			<div class="jl-sb-item" data-offer="lease-offer">
				<div class="jl-sb-icon">📋</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Lease Offer</div>
					<div class="jl-sb-type">post type: lease-offers</div>
				</div>
			</div>
			<div class="jl-sb-item" data-offer="finance-offer">
				<div class="jl-sb-icon">💰</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Finance Offer</div>
					<div class="jl-sb-type">post type: finance-offers</div>
				</div>
			</div>
			<div class="jl-sb-item" data-offer="conditional-offer">
				<div class="jl-sb-icon">🏷️</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Conditional Offer</div>
					<div class="jl-sb-type">post type: conditional-offers</div>
				</div>
			</div>
			<div class="jl-sb-item" data-offer="service-offer">
				<div class="jl-sb-icon">🔧</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Service Offer</div>
					<div class="jl-sb-type">post type: service-offers</div>
				</div>
			</div>
			<div class="jl-sb-item" data-offer="research">
				<div class="jl-sb-icon">📰</div>
				<div class="jl-sb-info">
					<div class="jl-sb-name">Research</div>
					<div class="jl-sb-type">post type: research</div>
				</div>
			</div>

			<div class="jl-sb-divider"></div>
			<div class="jl-sb-section">Global Output</div>
			<div class="jl-sb-hint">Which post types get JSON-LD injected in <code>&lt;head&gt;</code></div>

			<?php foreach ( $all_post_types as $pt_slug => $pt_label ) : ?>
				<label class="jl-global-row">
					<input type="checkbox"
						name="jl_post_types[]"
						value="<?php echo esc_attr( $pt_slug ); ?>"
						<?php checked( in_array( $pt_slug, $post_types, true ) ); ?>>
					<?php echo esc_html( $pt_label ); ?>
				</label>
			<?php endforeach; ?>

			<div class="jl-sb-divider"></div>
			<div class="jl-sb-section">Archive Limit</div>
			<div class="jl-sb-hint">Max vehicles in CollectionPage JSON-LD on SRP / archive pages</div>
			<div class="jl-global-row">
				<input type="number" class="jl-num-input" id="jl-archive-limit"
					value="<?php echo esc_attr( $archive_limit ); ?>"
					min="1" max="100">
				<span style="font-size:11px;color:var(--jl-faint);">items max</span>
			</div>
		</aside>

		<!-- Builder -->
		<main class="jl-builder" id="jl-builder">

			<div class="jl-inactive-notice">
				⚠ Switch to <strong>Builder</strong> mode above to apply these settings to output.
			</div>

			<!-- Inventory tab switcher: shown when a data-pt sidebar item is active -->
			<div class="jl-schema-tabs" id="jl-schema-tabs" style="display:none">
				<button class="jl-stab active" data-tab="archive-srp" type="button">📂 Archive / SRP</button>
				<button class="jl-stab" data-tab="vehicle" type="button">🚗 Vehicle VDP</button>
			</div>

			<!-- Offer tab switcher: shown when a data-offer sidebar item is active -->
			<div class="jl-schema-tabs" id="jl-offer-tabs" style="display:none">
				<button class="jl-stab active" data-offer-tab="srp" type="button">📂 Archive / SRP</button>
				<button class="jl-stab" data-offer-tab="single" type="button">📄 Single</button>
			</div>

			<div class="jl-builder-head">
				<span class="jl-builder-title" id="jl-builder-title">Archive / SRP</span>
				<span class="jl-builder-uri" id="jl-builder-uri">schema.org/CollectionPage</span>
				<div class="jl-builder-sep"></div>
				<span class="jl-builder-stats" id="jl-stats">
					<strong id="jl-active-count">—</strong> / 25 properties active
				</span>
			</div>

			<!-- Schema info panels for non-vehicle sidebar items -->
			<?php
			$all_cfg = \App\Components\Base\JSON_LD::get_config();

			$other_schema_type_defs = array(
				'lease-offer'               => array(
					'label'       => 'Lease Offer',
					'icon'        => '📋',
					'schema_type' => 'Offer',
					'cfg_key'     => 'lease_offer',
					'desc'        => 'JSON-LD Offer block on lease-offer post pages. Indexed by Google as a rich result for financing promotions.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'WP post title',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'ACF: description',
							'recommended' => true,
						),
						array(
							'key'      => 'priceCurrency',
							'schema'   => 'priceCurrency',
							'type'     => 'String',
							'source'   => 'Static: USD',
							'required' => true,
						),
						array(
							'key'      => 'price',
							'schema'   => 'price',
							'type'     => 'Number',
							'source'   => 'ACF: payment',
							'required' => true,
						),
						array(
							'key'         => 'validThrough',
							'schema'      => 'validThrough',
							'type'        => 'Date',
							'source'      => 'ACF: end_date',
							'recommended' => true,
						),
						array(
							'key'         => 'availability',
							'schema'      => 'availability',
							'type'        => 'URL',
							'source'      => 'Static: InStock',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: permalink',
							'required' => true,
						),
						array(
							'key'         => 'seller',
							'schema'      => 'seller → AutoDealer',
							'type'        => 'Object',
							'source'      => 'ACF: dealer_name',
							'recommended' => true,
						),
					),
				),
				'finance-offer'             => array(
					'label'       => 'Finance Offer',
					'icon'        => '💰',
					'schema_type' => 'Offer',
					'cfg_key'     => 'finance_offer',
					'desc'        => 'JSON-LD Offer block on finance-offer post pages. Covers monthly payment for loan-based promotions.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'WP post title',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'ACF: description',
							'recommended' => true,
						),
						array(
							'key'      => 'priceCurrency',
							'schema'   => 'priceCurrency',
							'type'     => 'String',
							'source'   => 'Static: USD',
							'required' => true,
						),
						array(
							'key'      => 'price',
							'schema'   => 'price',
							'type'     => 'Number',
							'source'   => 'ACF: payment',
							'required' => true,
						),
						array(
							'key'         => 'validThrough',
							'schema'      => 'validThrough',
							'type'        => 'Date',
							'source'      => 'ACF: end_date',
							'recommended' => true,
						),
						array(
							'key'         => 'availability',
							'schema'      => 'availability',
							'type'        => 'URL',
							'source'      => 'Static: InStock',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: permalink',
							'required' => true,
						),
						array(
							'key'         => 'seller',
							'schema'      => 'seller → AutoDealer',
							'type'        => 'Object',
							'source'      => 'ACF: dealer_name',
							'recommended' => true,
						),
					),
				),
				'conditional-offer'         => array(
					'label'       => 'Conditional Offer',
					'icon'        => '🏷️',
					'schema_type' => 'Offer',
					'cfg_key'     => 'conditional_offer',
					'desc'        => 'JSON-LD Offer block on conditional-offer pages. For time-limited dealer promotions.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'WP post title',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'ACF: description',
							'recommended' => true,
						),
						array(
							'key'      => 'priceCurrency',
							'schema'   => 'priceCurrency',
							'type'     => 'String',
							'source'   => 'Static: USD',
							'required' => true,
						),
						array(
							'key'      => 'price',
							'schema'   => 'price',
							'type'     => 'Number',
							'source'   => 'ACF: conditional_cash',
							'required' => true,
						),
						array(
							'key'         => 'validThrough',
							'schema'      => 'validThrough',
							'type'        => 'Date',
							'source'      => 'ACF: end_date',
							'recommended' => true,
						),
						array(
							'key'         => 'availability',
							'schema'      => 'availability',
							'type'        => 'URL',
							'source'      => 'Static: InStock',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: permalink',
							'required' => true,
						),
					),
				),
				'service-offer'             => array(
					'label'       => 'Service Offer',
					'icon'        => '🔧',
					'schema_type' => 'Offer',
					'cfg_key'     => 'service_offer',
					'desc'        => 'JSON-LD Offer block on service-offer post pages. Oil changes, tire rotations, brake service.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'WP post title',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'ACF: description',
							'recommended' => true,
						),
						array(
							'key'      => 'priceCurrency',
							'schema'   => 'priceCurrency',
							'type'     => 'String',
							'source'   => 'Static: USD',
							'required' => true,
						),
						array(
							'key'      => 'price',
							'schema'   => 'price',
							'type'     => 'Number',
							'source'   => 'ACF: payment',
							'required' => true,
						),
						array(
							'key'         => 'validThrough',
							'schema'      => 'validThrough',
							'type'        => 'Date',
							'source'      => 'ACF: end_date',
							'recommended' => true,
						),
						array(
							'key'         => 'availability',
							'schema'      => 'availability',
							'type'        => 'URL',
							'source'      => 'Static: InStock',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: permalink',
							'required' => true,
						),
						array(
							'key'         => 'seller',
							'schema'      => 'seller → AutoDealer',
							'type'        => 'Object',
							'source'      => 'ACF: dealer_name',
							'recommended' => true,
						),
						array(
							'key'         => 'itemOffered',
							'schema'      => 'itemOffered → Service',
							'type'        => 'Object',
							'source'      => 'ACF: service_name',
							'recommended' => true,
						),
					),
				),
				'archive-srp-listings'      => array(
					'label'       => 'SRP — New Vehicles',
					'icon'        => '📂',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'archive_srp_listings',
					'item_stype'  => 'srp-item-listings',
					'osub_key'    => 'srp_item_offers_sub_listings',
					'pt'          => 'listings',
					'desc'        => 'CollectionPage JSON-LD on /listings/ (new vehicles). hasPart items are auto-generated up to the Archive Limit.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'Static text',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: inventory (archive limit)',
							'required' => true,
						),
					),
				),
				'archive-srp-used-listings' => array(
					'label'       => 'SRP — Used Vehicles',
					'icon'        => '📂',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'archive_srp_used_listings',
					'item_stype'  => 'srp-item-used-listings',
					'osub_key'    => 'srp_item_offers_sub_used_listings',
					'pt'          => 'used-listings',
					'desc'        => 'CollectionPage JSON-LD on /used-listings/ (pre-owned vehicles). hasPart items are auto-generated up to the Archive Limit.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'Static text',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: inventory (archive limit)',
							'required' => true,
						),
					),
				),
				'lease-offer-srp'           => array(
					'label'       => 'Lease Offer Archive',
					'icon'        => '📋',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'lease_offer_srp',
					'item_stype'  => 'lease-offer',
					'desc'        => 'CollectionPage JSON-LD on the lease-offers archive page.',
					'props'       => array(
						array(
							'key'      => 'name',
							'schema'   => 'name',
							'type'     => 'String',
							'source'   => 'Static text',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: offer posts',
							'required' => true,
						),
					),
				),
				'finance-offer-srp'         => array(
					'label'       => 'Finance Offer Archive',
					'icon'        => '💰',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'finance_offer_srp',
					'item_stype'  => 'finance-offer',
					'desc'        => 'CollectionPage JSON-LD on the finance-offers archive page.',
					'props'       => array(
						array(
							'key'         => 'name',
							'schema'      => 'name',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: offer posts',
							'required' => true,
						),
					),
				),
				'conditional-offer-srp'     => array(
					'label'       => 'Conditional Offer Archive',
					'icon'        => '🏷️',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'conditional_offer_srp',
					'item_stype'  => 'conditional-offer',
					'desc'        => 'CollectionPage JSON-LD on the conditional-offers archive page.',
					'props'       => array(
						array(
							'key'         => 'name',
							'schema'      => 'name',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: offer posts',
							'required' => true,
						),
					),
				),
				'service-offer-srp'         => array(
					'label'       => 'Service Offer Archive',
					'icon'        => '🔧',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'service_offer_srp',
					'item_stype'  => 'service-offer',
					'desc'        => 'CollectionPage JSON-LD on the service-offers archive page.',
					'props'       => array(
						array(
							'key'         => 'name',
							'schema'      => 'name',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: offer posts',
							'required' => true,
						),
					),
				),
				'research-srp'              => array(
					'label'       => 'Research Archive',
					'icon'        => '📰',
					'schema_type' => 'CollectionPage',
					'cfg_key'     => 'research_srp',
					'item_stype'  => 'research',
					'desc'        => 'CollectionPage JSON-LD on the research archive page. Lists all buying guides and editorial articles.',
					'props'       => array(
						array(
							'key'         => 'name',
							'schema'      => 'name',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'Static text',
							'recommended' => true,
						),
						array(
							'key'      => 'url',
							'schema'   => 'url',
							'type'     => 'URL',
							'source'   => 'Auto: archive URL',
							'required' => true,
						),
						array(
							'key'      => 'hasPart',
							'schema'   => 'hasPart',
							'type'     => 'Array',
							'source'   => 'Auto: research posts',
							'required' => true,
						),
					),
				),
				'research'                  => array(
					'label'       => 'Research Single',
					'icon'        => '📰',
					'schema_type' => 'Article',
					'cfg_key'     => 'research',
					'desc'        => 'Article JSON-LD on research post pages. Buying guides, model comparisons, editorial content.',
					'props'       => array(
						array(
							'key'      => 'headline',
							'schema'   => 'headline',
							'type'     => 'String',
							'source'   => 'WP post title',
							'required' => true,
						),
						array(
							'key'         => 'description',
							'schema'      => 'description',
							'type'        => 'String',
							'source'      => 'ACF: description',
							'recommended' => true,
						),
						array(
							'key'      => 'author',
							'schema'   => 'author → Organization',
							'type'     => 'Object',
							'source'   => 'Auto: site name',
							'required' => true,
						),
						array(
							'key'         => 'datePublished',
							'schema'      => 'datePublished',
							'type'        => 'Date',
							'source'      => 'Auto: post date',
							'recommended' => true,
						),
						array(
							'key'         => 'image',
							'schema'      => 'image',
							'type'        => 'URL',
							'source'      => 'ACF: featured_image',
							'recommended' => true,
						),
					),
				),
			);

			/**
			 * Render prop cards for a given schema type config.
			 *
			 * @param array  $props      Array of prop definitions.
			 * @param array  $type_cfg   Saved config for this type.
			 * @param string $stype      Schema type slug e.g. "lease-offer".
			 */
			/* Item properties for the Archive / SRP Vehicle entries */
			$srp_item_props = array(
				array(
					'key'         => 'brand',
					'schema'      => 'brand → Brand',
					'type'        => 'Object',
					'source'      => 'API: make',
					'recommended' => true,
				),
				array(
					'key'         => 'model',
					'schema'      => 'model',
					'type'        => 'String',
					'source'      => 'API: model',
					'recommended' => true,
				),
				array(
					'key'         => 'vehicleModelDate',
					'schema'      => 'vehicleModelDate',
					'type'        => 'String',
					'source'      => 'API: year',
					'recommended' => true,
				),
				array(
					'key'         => 'vehicleIdentificationNumber',
					'schema'      => 'vehicleIdentificationNumber',
					'type'        => 'String',
					'source'      => 'API: vin',
					'recommended' => true,
				),
				array(
					'key'    => 'image',
					'schema' => 'image',
					'type'   => 'String',
					'source' => 'API: thumb / image',
				),
				array(
					'key'         => 'offers',
					'schema'      => 'offers → Offer',
					'type'        => 'Object',
					'source'      => 'API: price · dealer_name · auto: condition',
					'recommended' => true,
				),
			);

			$render_prop_cards = function ( array $props, array $type_cfg, string $stype ) use ( $acf_field_options ) {
				foreach ( $props as $prop ) :
					$key         = $prop['key'];
					$raw_cfg     = $type_cfg[ $key ] ?? array( 'enabled' => true );
					$enabled     = is_array( $raw_cfg ) ? ! empty( $raw_cfg['enabled'] ) : ! empty( $raw_cfg );
					$acf_key_val = is_array( $raw_cfg ) ? ( $raw_cfg['acf_key'] ?? '' ) : '';
					$static_val  = is_array( $raw_cfg ) ? ( $raw_cfg['static_value'] ?? '' ) : '';
					$type        = $prop['type'];
					$type_cls    = strtolower( $type );
					$editor_id   = 'jl-src-' . esc_attr( $stype ) . '-' . esc_attr( $key );
					$source_text = $prop['source'] ?? '';
					$schema_str  = $prop['schema'] ?? $key;
					?>
					<div class="jl-prop <?php echo $enabled ? '' : 'jl-off'; ?>" id="jl-prop-<?php echo esc_attr( $stype . '-' . $key ); ?>">

						<div class="jl-toggle-cell">
							<label class="jl-toggle" aria-label="Toggle <?php echo esc_attr( $key ); ?>">
								<input type="checkbox"
									data-prop="<?php echo esc_attr( $key ); ?>"
									data-stype="<?php echo esc_attr( $stype ); ?>"
									<?php checked( $enabled ); ?>>
								<div class="jl-track"></div>
								<div class="jl-thumb"></div>
							</label>
						</div>

						<div class="jl-prop-body">
							<div class="jl-key-row">
								<span class="jl-key"><?php echo esc_html( $key ); ?></span>
								<span class="jl-vtype <?php echo esc_attr( $type_cls ); ?>"><?php echo esc_html( $type ); ?></span>
							</div>
							<div class="jl-uri">schema.org <span class="jl-uri-sep">›</span> <?php echo esc_html( $schema_str ); ?></div>
							<?php if ( 'offers' !== $key ) : ?>
							<div class="jl-source"><span>Source →</span><span class="jl-source-field"><?php echo esc_html( $source_text ); ?></span></div>

							<button type="button"
								class="jl-src-toggle"
								aria-expanded="<?php echo ( $acf_key_val || $static_val ) ? 'true' : 'false'; ?>"
								aria-controls="<?php echo $editor_id; ?>">
								<svg width="8" height="8" viewBox="0 0 8 8" fill="none"><path d="M2 1L6 4L2 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Edit Source
							</button>
							<?php endif; ?>

							<div class="jl-src-editor <?php echo ( $acf_key_val || $static_val ) ? 'jl-src-open' : ''; ?>"
								id="<?php echo $editor_id; ?>"
								data-for="<?php echo esc_attr( $key ); ?>">

								<div class="jl-src-row">
									<label>Key</label>
									<input type="text" class="jl-src-input"
										list="jl-acf-keys"
										data-src="acf_key"
										data-prop="<?php echo esc_attr( $key ); ?>"
										data-stype="<?php echo esc_attr( $stype ); ?>"
										value="<?php echo esc_attr( $acf_key_val ); ?>"
										placeholder="<?php echo esc_attr( $prop['acf_default'] ?? $acf_key_val ); ?>">
								</div>
								<div class="jl-src-hint">Leave blank to use the default</div>

								<div class="jl-src-row jl-src-static-row">
									<label>Static value</label>
									<input type="text" class="jl-src-input"
										data-src="static_value"
										data-prop="<?php echo esc_attr( $key ); ?>"
										data-stype="<?php echo esc_attr( $stype ); ?>"
										value="<?php echo esc_attr( $static_val ); ?>"
										placeholder="Text or {{make}} {{year}} template…">
								</div>
								<?php if ( $static_val ) : ?>
									<div class="jl-src-static-note" style="display:block;">⚠ Static value overrides source</div>
								<?php else : ?>
									<div class="jl-src-static-note">⚠ Static value overrides source</div>
								<?php endif; ?>
							</div>
						</div>

						<div class="jl-meta-cell">
							<?php if ( ! empty( $prop['required'] ) ) : ?>
								<span class="jl-required">Required</span>
							<?php elseif ( ! empty( $prop['recommended'] ) ) : ?>
								<span class="jl-recommended">Recommended</span>
							<?php endif; ?>
						</div>
					</div>
					<?php
				endforeach;
			};

			foreach ( $other_schema_type_defs as $stype_slug => $stype_def ) :
				$stype_cfg = $all_cfg[ $stype_def['cfg_key'] ] ?? array();
				?>
				<div class="jl-schema-info" id="jl-info-<?php echo esc_attr( $stype_slug ); ?>">

					<div class="jl-builder-head">
						<span class="jl-builder-title"><?php echo esc_html( $stype_def['icon'] . ' ' . $stype_def['label'] ); ?></span>
						<span class="jl-builder-uri">schema.org/<?php echo esc_html( $stype_def['schema_type'] ); ?></span>
						<div class="jl-builder-sep"></div>
						<span class="jl-builder-stats" data-stype-stats="<?php echo esc_attr( $stype_slug ); ?>">
							<strong class="jl-stype-active-count" data-total="<?php echo count( $stype_def['props'] ); ?>">—</strong> / <?php echo count( $stype_def['props'] ); ?> properties active
						</span>
					</div>

					<div class="jl-group">
						<div class="jl-group-label"><?php echo esc_html( $stype_def['label'] ); ?> Properties</div>
						<p style="font-size:11px;color:var(--jl-muted);margin:0 0 12px;line-height:1.6;"><?php echo esc_html( $stype_def['desc'] ); ?></p>
						<?php $render_prop_cards( $stype_def['props'], $stype_cfg, $stype_slug ); ?>
					</div>

					<?php
					if ( in_array( $stype_slug, array( 'archive-srp-listings', 'archive-srp-used-listings' ), true ) ) :
						$_item_stype = $stype_def['item_stype'];
						$_item_key   = 'srp_item_listings' === str_replace( 'srp-item-', 'srp_item_', str_replace( '-', '_', $_item_stype ) )
							? 'srp_item_listings' : 'srp_item_used_listings';
						$_item_key   = 'archive-srp-listings' === $stype_slug ? 'srp_item_listings' : 'srp_item_used_listings';
						$_osub_key   = $stype_def['osub_key'];
						$_pt_attr    = $stype_def['pt'];
						$_item_cfg   = $all_cfg[ $_item_key ] ?? array();
						$_srp_osub   = array_merge(
							array(
								'price_currency'  => 'USD',
								'price_key'       => 'price',
								'availability'    => 'InStock',
								'condition'       => 'archive-srp-listings' === $stype_slug ? 'NewCondition' : 'UsedCondition',
								'show_seller'     => '1',
								'seller_name_key' => 'dealer_name',
							),
							$all_cfg[ $_osub_key ] ?? array()
						);
						?>
					<div class="jl-group" style="margin-top:18px;">
						<div class="jl-group-label">
							Item Properties
							<span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--jl-muted);margin-left:6px;">— per-Vehicle fields inside itemListElement</span>
						</div>
						<p style="font-size:11px;color:var(--jl-muted);margin:0 0 12px;line-height:1.6;">Controls which fields are included in each Vehicle object within the ItemList. <code>name</code> and <code>url</code> are always output.</p>
						<?php $render_prop_cards( $srp_item_props, $_item_cfg, $_item_stype ); ?>

						<div class="jl-offers-sub" style="margin-top:14px;">
							<div class="jl-osub-label">Offer sub-fields <span style="font-weight:400;color:var(--jl-faint);text-transform:none;letter-spacing:0;font-size:10px;">(when offers is enabled)</span></div>
							<div class="jl-osub-row">
								<label>priceCurrency</label>
								<input type="text" class="jl-src-input jl-srp-osub-field" data-srp-osub="price_currency" data-pt="<?php echo esc_attr( $_pt_attr ); ?>"
									value="<?php echo esc_attr( $_srp_osub['price_currency'] ); ?>"
									placeholder="USD" style="max-width:80px;">
							</div>
							<div class="jl-osub-row">
								<label>price Key</label>
								<input type="text" class="jl-src-input jl-srp-osub-field" data-srp-osub="price_key" data-pt="<?php echo esc_attr( $_pt_attr ); ?>"
									list="jl-acf-keys"
									value="<?php echo esc_attr( $_srp_osub['price_key'] ?? $_srp_osub['price_api'] ?? 'price' ); ?>"
									placeholder="price">
							</div>
							<div class="jl-osub-row">
								<label>availability</label>
								<select class="jl-src-input jl-srp-osub-field" data-srp-osub="availability" data-pt="<?php echo esc_attr( $_pt_attr ); ?>">
									<?php foreach ( array( 'InStock', 'OutOfStock', 'PreOrder', 'SoldOut', 'Discontinued' ) as $av ) : ?>
										<option value="<?php echo esc_attr( $av ); ?>" <?php selected( $_srp_osub['availability'], $av ); ?>><?php echo esc_html( $av ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="jl-osub-row">
								<label>itemCondition</label>
								<select class="jl-src-input jl-srp-osub-field" data-srp-osub="condition" data-pt="<?php echo esc_attr( $_pt_attr ); ?>">
									<option value="auto" <?php selected( $_srp_osub['condition'], 'auto' ); ?>>auto (from post type)</option>
									<option value="NewCondition" <?php selected( $_srp_osub['condition'], 'NewCondition' ); ?>>NewCondition</option>
									<option value="UsedCondition" <?php selected( $_srp_osub['condition'], 'UsedCondition' ); ?>>UsedCondition</option>
									<option value="RefurbishedCondition" <?php selected( $_srp_osub['condition'], 'RefurbishedCondition' ); ?>>RefurbishedCondition</option>
								</select>
							</div>
							<div class="jl-osub-row">
								<label>Show seller</label>
								<label style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--jl-text);cursor:pointer;">
									<input type="checkbox" class="jl-srp-osub-field" data-srp-osub="show_seller" data-pt="<?php echo esc_attr( $_pt_attr ); ?>"
										<?php checked( ! empty( $_srp_osub['show_seller'] ) ); ?>>
									include seller (AutoDealer)
								</label>
							</div>
							<div class="jl-osub-row">
								<label>seller name Key</label>
								<input type="text" class="jl-src-input jl-srp-osub-field" data-srp-osub="seller_name_key" data-pt="<?php echo esc_attr( $_pt_attr ); ?>"
									list="jl-acf-keys"
									value="<?php echo esc_attr( $_srp_osub['seller_name_key'] ?? $_srp_osub['seller_name_api'] ?? 'dealer_name' ); ?>"
									placeholder="dealer_name">
							</div>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$_offer_srp_map = array(
						'lease-offer-srp'       => 'lease-offer',
						'finance-offer-srp'     => 'finance-offer',
						'conditional-offer-srp' => 'conditional-offer',
						'service-offer-srp'     => 'service-offer',
						'research-srp'          => 'research',
					);
					if ( isset( $_offer_srp_map[ $stype_slug ] ) ) :
						$_single_slug  = $_offer_srp_map[ $stype_slug ];
						$_single_def   = $other_schema_type_defs[ $_single_slug ] ?? array();
						$_single_cfg   = $all_cfg[ $_single_def['cfg_key'] ?? '' ] ?? array();
						$_single_props = $_single_def['props'] ?? array();
						?>
					<div class="jl-group" style="margin-top:18px;">
						<div class="jl-group-label">
							Item Properties
							<span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--jl-muted);margin-left:6px;">— per-Offer fields inside itemListElement</span>
						</div>
						<p style="font-size:11px;color:var(--jl-muted);margin:0 0 12px;line-height:1.6;">Controls which fields are included in each Offer object within the ItemList. <code>name</code> and <code>url</code> are always output.</p>
						<?php $render_prop_cards( $_single_props, $_single_cfg, $_single_slug ); ?>
					</div>
					<?php endif; ?>

				</div>
			<?php endforeach; ?>

			<?php $total_vehicle_props = array_sum( array_map( fn( $g ) => count( $g['props'] ), $vehicle_props ) ); ?>
			<div id="jl-vehicle-builder" class="jl-schema-info active">
				<div class="jl-builder-head">
					<span class="jl-builder-title">🚗 Vehicle VDP</span>
					<span class="jl-builder-uri">schema.org/Vehicle</span>
					<div class="jl-builder-sep"></div>
					<span class="jl-builder-stats" data-stype-stats="vehicle">
						<strong class="jl-stype-active-count" data-total="<?php echo (int) $total_vehicle_props; ?>">—</strong>
						/ <?php echo (int) $total_vehicle_props; ?> properties active
					</span>
				</div>
			<?php foreach ( $vehicle_props as $group_key => $group ) : ?>
				<div class="jl-group">
					<div class="jl-group-label"><?php echo esc_html( $group['label'] ); ?></div>

					<?php
					foreach ( $group['props'] as $prop ) :
						$key          = $prop['key'];
						$raw_cfg      = $vcfg[ $key ] ?? false;
						$enabled      = is_array( $raw_cfg ) ? ! empty( $raw_cfg['enabled'] ) : ! empty( $raw_cfg );
						$acf_key_val  = is_array( $raw_cfg ) ? ( $raw_cfg['acf_key'] ?? '' ) : '';
						$static_val   = is_array( $raw_cfg ) ? ( $raw_cfg['static_value'] ?? '' ) : '';
						$type         = $prop['type'];
						$type_cls     = strtolower( $type );
						$schema_parts = explode( ' › ', $prop['schema'] );
						if ( count( $schema_parts ) === 1 ) {
							$schema_parts = array( 'Vehicle', $schema_parts[0] );
						}
						$editor_id = 'jl-src-' . esc_attr( $key );
						?>
						<div class="jl-prop <?php echo $enabled ? '' : 'jl-off'; ?>" id="jl-prop-<?php echo esc_attr( $key ); ?>">

							<div class="jl-toggle-cell">
								<label class="jl-toggle" aria-label="Toggle <?php echo esc_attr( $key ); ?>">
									<input type="checkbox"
										data-prop="<?php echo esc_attr( $key ); ?>"
										data-stype="vehicle"
										<?php checked( $enabled ); ?>>
									<div class="jl-track"></div>
									<div class="jl-thumb"></div>
								</label>
							</div>

							<div class="jl-prop-body">
								<div class="jl-key-row">
									<span class="jl-key"><?php echo esc_html( $key ); ?></span>
									<span class="jl-vtype <?php echo esc_attr( $type_cls ); ?>"><?php echo esc_html( $type ); ?></span>
								</div>
								<div class="jl-uri">
									schema.org
									<?php foreach ( $schema_parts as $i => $part ) : ?>
										<?php
										if ( $i > 0 ) :
											?>
											<span class="jl-uri-sep">›</span><?php endif; ?>
										<?php echo esc_html( $part ); ?>
									<?php endforeach; ?>
								</div>
								<?php if ( 'offers' !== $key ) : ?>
								<div class="jl-source">
									<span>Source →</span>
									<span class="jl-source-field"><?php echo esc_html( $prop['source'] ); ?></span>
								</div>
								<?php endif; ?>

								<?php if ( ! in_array( $key, array( 'additionalProperty', 'offers' ), true ) ) : ?>
									<button type="button"
										class="jl-src-toggle"
										aria-expanded="<?php echo ( $acf_key_val || $static_val ) ? 'true' : 'false'; ?>"
										aria-controls="<?php echo $editor_id; ?>">
										<svg width="8" height="8" viewBox="0 0 8 8" fill="none">
											<path d="M2 1L6 4L2 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										Edit Source
									</button>

									<div class="jl-src-editor <?php echo ( $acf_key_val || $static_val ) ? 'jl-src-open' : ''; ?>"
										id="<?php echo $editor_id; ?>"
										data-for="<?php echo esc_attr( $key ); ?>">

										<div class="jl-src-row">
											<label>Key</label>
											<input type="text" class="jl-src-input"
												list="jl-acf-keys"
												data-src="acf_key"
												data-prop="<?php echo esc_attr( $key ); ?>"
												data-stype="vehicle"
												value="<?php echo esc_attr( $acf_key_val ); ?>"
												placeholder="<?php echo esc_attr( \App\Components\Base\JSON_LD::DEFAULT_SOURCES[ $key ]['acf'] ?? '' ); ?>">
										</div>
<div class="jl-src-hint">Leave blank to use the default (shown as placeholder)</div>

										<div class="jl-src-row jl-src-static-row">
											<label>Static value</label>
											<input type="text" class="jl-src-input"
												data-src="static_value"
												data-prop="<?php echo esc_attr( $key ); ?>"
												data-stype="vehicle"
												value="<?php echo esc_attr( $static_val ); ?>"
												placeholder="Text or {{make}} {{year}} template…">
										</div>
										<div class="jl-vars-bar">
											<span class="jl-vars-label">Insert →</span>
											<?php
											$tpl_vars = array( 'year', 'make', 'model', 'trim', 'vin', 'body_style', 'exterior_color', 'interior_color', 'price', 'mileage', 'condition', 'stock', 'engine', 'fuel_type', 'transmission' );
											foreach ( $tpl_vars as $tvar ) :
												?>
												<button type="button" class="jl-var-chip"
													data-tpl="{{<?php echo esc_attr( $tvar ); ?>}}"
													data-for="<?php echo esc_attr( $key ); ?>">
													{{<?php echo esc_html( $tvar ); ?>}}
												</button>
											<?php endforeach; ?>
										</div>
										<div class="jl-static-preview" id="jl-sp-<?php echo esc_attr( $key ); ?>"></div>
										<?php if ( $static_val ) : ?>
											<div class="jl-src-static-note" style="display:block;">⚠ Static value overrides ACF &amp; API sources</div>
										<?php else : ?>
											<div class="jl-src-static-note">⚠ Static value overrides ACF &amp; API sources</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php
								if ( 'offers' === $key ) :
									$osub = $vcfg['offers_sub'] ?? array();
									$osub = array_merge(
										array(
											'price_currency' => 'USD',
											'price_key'      => 'price',

											'availability' => 'InStock',
											'condition'    => 'auto',
											'seller_name_key' => 'dealer_name',

											'seller_url_key' => 'dealer_url',
										),
										$osub
									);
									?>
									<div class="jl-offers-sub">
										<div class="jl-osub-title">Offer sub-fields</div>
										<div class="jl-osub-row">
											<label>priceCurrency</label>
											<input type="text" class="jl-src-input jl-osub-field" data-osub="price_currency"
												value="<?php echo esc_attr( $osub['price_currency'] ); ?>"
												placeholder="USD" style="width:80px;">
										</div>
										<div class="jl-osub-row">
											<label>price Key</label>
											<input type="text" class="jl-src-input jl-osub-field" data-osub="price_key"
												list="jl-acf-keys"
												value="<?php echo esc_attr( $osub['price_key'] ?? $osub['price_acf'] ?? $osub['price_api'] ?? 'price' ); ?>"
												placeholder="price">
										</div>
										<div class="jl-osub-row">
											<label>availability</label>
											<select class="jl-src-input jl-osub-field" data-osub="availability">
												<?php foreach ( array( 'InStock', 'OutOfStock', 'PreOrder', 'SoldOut', 'Discontinued' ) as $av ) : ?>
													<option value="<?php echo esc_attr( $av ); ?>" <?php selected( $osub['availability'], $av ); ?>><?php echo esc_html( $av ); ?></option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="jl-osub-row">
											<label>itemCondition</label>
											<select class="jl-src-input jl-osub-field" data-osub="condition">
												<option value="auto" <?php selected( $osub['condition'], 'auto' ); ?>>auto (from post type)</option>
												<option value="NewCondition" <?php selected( $osub['condition'], 'NewCondition' ); ?>>NewCondition</option>
												<option value="UsedCondition" <?php selected( $osub['condition'], 'UsedCondition' ); ?>>UsedCondition</option>
												<option value="RefurbishedCondition" <?php selected( $osub['condition'], 'RefurbishedCondition' ); ?>>RefurbishedCondition</option>
											</select>
										</div>
										<div class="jl-osub-row">
											<label>seller name Key</label>
											<input type="text" class="jl-src-input jl-osub-field" data-osub="seller_name_key"
												list="jl-acf-keys"
												value="<?php echo esc_attr( $osub['seller_name_key'] ?? $osub['seller_name_acf'] ?? $osub['seller_name_api'] ?? 'dealer_name' ); ?>"
												placeholder="dealer_name">
										</div>
										<div class="jl-osub-row">
											<label>seller URL Key</label>
											<input type="text" class="jl-src-input jl-osub-field" data-osub="seller_url_key"
												list="jl-acf-keys"
												value="<?php echo esc_attr( $osub['seller_url_key'] ?? $osub['seller_url_acf'] ?? 'dealer_url' ); ?>"
												placeholder="dealer_url">
										</div>
									</div>
								<?php endif; ?>

								<?php if ( 'additionalProperty' === $key ) : ?>
									<div class="jl-feat-limit">
										<span class="jl-feat-limit-label">Limit</span>
										<input type="number" class="jl-num-input" id="jl-feat-limit"
											value="<?php echo esc_attr( (int) ( $vcfg['features_limit'] ?? 0 ) ); ?>"
											min="0" max="50" style="width:50px;">
										<span class="jl-feat-limit-hint">items · 0 = no limit</span>
									</div>
								<?php endif; ?>
							</div>

							<div class="jl-meta-cell">
								<?php if ( ! empty( $prop['required'] ) ) : ?>
									<span class="jl-required">Required</span>
								<?php elseif ( ! empty( $prop['recommended'] ) ) : ?>
									<span class="jl-recommended">Recommended</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			</div><!-- /jl-vehicle-builder -->

			<!-- Custom Properties -->
			<div class="jl-group jl-custom-group">
				<div class="jl-group-label">Custom Properties</div>
				<div id="jl-custom-list">
					<?php if ( empty( $custom_properties ) ) : ?>
						<div class="jl-custom-empty" id="jl-custom-empty">No custom properties yet. Click "Add Property" to add any schema.org field.</div>
					<?php else : ?>
						<?php foreach ( $custom_properties as $cp ) : ?>
							<div class="jl-custom-row">
								<input type="text" class="jl-custom-key" list="jl-custom-keys"
									value="<?php echo esc_attr( $cp['key'] ?? '' ); ?>"
									placeholder="schema key e.g. numberOfPreviousOwners">
								<input type="text" class="jl-custom-val"
									value="<?php echo esc_attr( $cp['value'] ?? '' ); ?>"
									placeholder="value or {{year}} template">
								<button type="button" class="jl-custom-remove" title="Remove">✕</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<button type="button" class="jl-add-custom-btn" id="jl-add-custom">
					+ Add Property
				</button>
			</div>

		</main>

		<!-- Live preview -->
		<aside class="jl-preview">
			<div class="jl-preview-head">
				<div class="jl-preview-dot" style="background:#FF5F56"></div>
				<div class="jl-preview-dot" style="background:#FFBD2E"></div>
				<div class="jl-preview-dot" style="background:#27C93F"></div>
				<span class="jl-preview-title">application/ld+json · preview</span>
				<div class="jl-preview-mode-toggle">
					<button class="jl-pmode-btn jl-pmode-active" id="jl-pmode-demo" type="button">demo</button>
					<button class="jl-pmode-btn" id="jl-pmode-real" type="button">real</button>
				</div>
				<button class="jl-tree-ctrl" id="jl-collapse-all" type="button">collapse all</button>
				<button class="jl-tree-ctrl" id="jl-expand-all" type="button">expand all</button>
				<button class="jl-copy-btn" id="jl-copy-btn" type="button">copy</button>
			</div>
			<div class="jl-real-picker" id="jl-real-picker">
				<select class="jl-real-select" id="jl-pick-posttype">
					<option value="listings">listings (new)</option>
					<option value="used-listings">used-listings</option>
					<option value="lease-offers">lease-offers</option>
					<option value="finance-offers">finance-offers</option>
					<option value="conditional-offers">conditional-offers</option>
					<option value="service-offers">service-offers</option>
					<option value="research">research</option>
				</select>
				<select class="jl-real-select" id="jl-pick-post">
					<option value="">— pick post —</option>
				</select>
				<button class="jl-real-fetch-btn" id="jl-real-fetch" type="button">Load</button>
				<span class="jl-real-status" id="jl-real-status"></span>
			</div>
			<div class="jl-preview-body" id="jl-preview-body" aria-live="polite">
				<span style="color:rgba(255,255,255,.2);">Loading preview…</span>
			</div>
			<div class="jl-preview-foot">
				<span class="jl-preview-stat">props: <strong id="jl-stat-props">—</strong></span>
				<span class="jl-preview-stat">bytes: <strong id="jl-stat-bytes">—</strong></span>
				<button class="jl-preview-validate" type="button"
					onclick="window.open('https://search.google.com/test/rich-results','_blank')">
					↗ Rich Results Test
				</button>
			</div>
		</aside>

	</div><!-- /jl-shell -->
</div><!-- /jl-wrap -->

<script>
(function () {
	'use strict';

	/* ── State ─────────────────────────────────────────────────── */
	const nonce = '<?php echo esc_js( wp_create_nonce( 'soc_nonce' ) ); ?>';
	const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

	/* PHP-side configs for both vehicle types — used to populate builder when switching */
	const JL_VEHICLE_CFG = {
		listings: <?php echo wp_json_encode( $all_cfg['vehicle_listings'] ?? $all_cfg['vehicle'] ?? array() ); ?>,
		'used-listings': <?php echo wp_json_encode( $all_cfg['vehicle_used_listings'] ?? $all_cfg['vehicle'] ?? array() ); ?>
	};

	/* In-memory cache for vehicle prop states; populated on first switch or save */
	const vehicleCfgCache = { listings: null, 'used-listings': null };
	let activeVehicleType = 'listings'; /* tracks which vehicle config is currently in the builder UI */

	/* ── Post-type + schema-tab state ────────────────────────── */
	let activePostType   = 'listings';    /* 'listings' | 'used-listings' */
	let activeSchemaTab  = 'archive-srp'; /* 'archive-srp' | 'vehicle' — used in pt mode */
	let isPtMode         = true;          /* true when a data-pt sidebar item is active */
	let isOfferMode      = false;         /* true when a data-offer sidebar item is active */
	let activeOfferType  = 'lease-offer'; /* 'lease-offer' | 'finance-offer' | 'conditional-offer' | 'service-offer' */
	let activeOfferTab   = 'srp';         /* 'srp' | 'single' */

	function computeActiveSchemaType() {
		if (isOfferMode) {
			return activeOfferTab === 'single' ? activeOfferType : activeOfferType + '-srp';
		}
		if (!isPtMode) return activeSchemaType;
		return activeSchemaTab === 'vehicle'
			? 'vehicle-' + activePostType
			: 'archive-srp-' + activePostType;
	}

	function applySchemaTabUI() {
		document.querySelectorAll('.jl-stab[data-tab]').forEach(btn => {
			btn.classList.toggle('active', btn.dataset.tab === activeSchemaTab);
		});
		const titleEl = document.getElementById('jl-builder-title');
		const uriEl   = document.getElementById('jl-builder-uri');
		if (activeSchemaTab === 'vehicle') {
			if (titleEl) titleEl.textContent = 'Vehicle Schema';
			if (uriEl)   uriEl.textContent   = 'schema.org/Vehicle';
		} else {
			if (titleEl) titleEl.textContent = 'Archive / SRP';
			if (uriEl)   uriEl.textContent   = 'schema.org/CollectionPage';
		}
	}

	function applyOfferTabUI() {
		document.querySelectorAll('.jl-stab[data-offer-tab]').forEach(btn => {
			btn.classList.toggle('active', btn.dataset.offerTab === activeOfferTab);
		});
		const titleEl = document.getElementById('jl-builder-title');
		const uriEl   = document.getElementById('jl-builder-uri');
		const isRes = activeOfferType === 'research';
		if (titleEl) titleEl.textContent = activeOfferTab === 'single' ? (isRes ? 'Single Article' : 'Single Offer') : 'Archive / SRP';
		if (uriEl)   uriEl.textContent   = activeOfferTab === 'single' ? (isRes ? 'schema.org/Article' : 'schema.org/Offer') : 'schema.org/CollectionPage';
	}

	function showActivePanel() {
		const vehicleBuilder = document.getElementById('jl-vehicle-builder');
		const customGroup    = document.querySelector('.jl-custom-group');
		const builderHead    = document.querySelector('.jl-builder-head');
		const ptTabs         = document.getElementById('jl-schema-tabs');
		const offerTabs      = document.getElementById('jl-offer-tabs');
		const infoPanels     = document.querySelectorAll('.jl-schema-info:not(#jl-vehicle-builder)');

		/* Reset everything */
		infoPanels.forEach(p => p.classList.remove('active'));
		vehicleBuilder.style.display = 'none';
		if (customGroup) customGroup.style.display = 'none';
		ptTabs.style.display    = 'none';
		offerTabs.style.display = 'none';
		builderHead.style.display = 'none';

		if (isPtMode) {
			/* ── Inventory (listings / used-listings) ── */
			ptTabs.style.display      = '';
			builderHead.style.display = '';
			applySchemaTabUI();
			if (activeSchemaTab === 'vehicle') {
				vehicleBuilder.style.display = '';
				if (customGroup) customGroup.style.display = '';
				if (activePostType !== activeVehicleType) {
					saveVehicleState();
					activeVehicleType = activePostType;
					loadVehicleState(activePostType);
				}
			} else {
				const panel = document.getElementById('jl-info-archive-srp-' + activePostType);
				if (panel) panel.classList.add('active');
			}
		} else if (isOfferMode) {
			/* ── Offer type (lease / finance / conditional / service) ── */
			offerTabs.style.display   = '';
			builderHead.style.display = '';
			applyOfferTabUI();
			const panelId = activeOfferTab === 'single' ? activeOfferType : activeOfferType + '-srp';
			const panel = document.getElementById('jl-info-' + panelId);
			if (panel) panel.classList.add('active');
		} else {
			/* ── Single schema type (Research, etc.) ── */
			const panel = document.getElementById('jl-info-' + activeSchemaType);
			if (panel) panel.classList.add('active');
		}
	}

	/* Save current builder UI state into vehicleCfgCache[activeVehicleType] */
	function saveVehicleState() {
		const s = {};
		document.querySelectorAll('input[data-prop][data-stype="vehicle"][type="checkbox"]').forEach(inp => {
			const k = inp.dataset.prop;
			if (!s[k]) s[k] = { enabled: false, acf_key: '', static_value: '' };
			s[k].enabled = inp.checked;
		});
		document.querySelectorAll('.jl-src-input[data-prop][data-stype="vehicle"]').forEach(inp => {
			const k = inp.dataset.prop, src = inp.dataset.src;
			if (!src || !k) return;
			if (!s[k]) s[k] = { enabled: false, acf_key: '', static_value: '' };
			s[k][src] = inp.value.trim();
		});
		s.offers_sub = {};
		document.querySelectorAll('.jl-osub-field[data-osub]').forEach(el => {
			s.offers_sub[el.dataset.osub] = el.value.trim();
		});
		vehicleCfgCache[activeVehicleType] = s;
	}

	/* Load a vehicle config into the builder UI checkboxes/inputs */
	function loadVehicleState(type) {
		const cfg = vehicleCfgCache[type] || JL_VEHICLE_CFG[type] || {};
		document.querySelectorAll('input[data-prop][data-stype="vehicle"][type="checkbox"]').forEach(inp => {
			const k = inp.dataset.prop;
			const pc = cfg[k];
			const on = pc ? (typeof pc === 'object' ? !!pc.enabled : !!pc) : true;
			inp.checked = on;
			const card = inp.closest('.jl-prop');
			if (card) card.classList.toggle('jl-off', !on);
		});
		document.querySelectorAll('.jl-src-input[data-prop][data-stype="vehicle"]').forEach(inp => {
			const k = inp.dataset.prop, src = inp.dataset.src;
			if (!src || !k) return;
			const pc = cfg[k];
			inp.value = (pc && typeof pc === 'object') ? (pc[src] || '') : '';
		});
		const osub = cfg.offers_sub || {};
		document.querySelectorAll('.jl-osub-field[data-osub]').forEach(el => {
			if (el.dataset.osub in osub) el.value = osub[el.dataset.osub];
		});
	}

	function collectStateForType(schemaType) {
		const props = {};
		document.querySelectorAll('input[data-prop][data-stype="' + schemaType + '"][type="checkbox"]').forEach(inp => {
			const k = inp.dataset.prop;
			if (!props[k]) props[k] = { enabled: false, acf_key: '', static_value: '' };
			props[k].enabled = inp.checked;
		});
		document.querySelectorAll('.jl-src-input[data-prop][data-stype="' + schemaType + '"]').forEach(inp => {
			const k   = inp.dataset.prop;
			const src = inp.dataset.src;
			if (!src) return;
			if (!props[k]) props[k] = { enabled: false, acf_key: '', static_value: '' };
			props[k][src] = inp.value.trim();
		});
		return props;
	}

	function collectState() {
		const ptInputs = document.querySelectorAll('input[name="jl_post_types[]"]');
		const postTypes = [];
		ptInputs.forEach(inp => { if (inp.checked) postTypes.push(inp.value); });

		const featLimit    = parseInt(document.getElementById('jl-feat-limit').value, 10) || 0;
		const archiveLimit = parseInt(document.getElementById('jl-archive-limit').value, 10) || 24;
		const mode         = document.querySelector('input[name="jl_mode"]:checked')?.value || 'legacy';

		/* Collect custom properties */
		const custom_properties = [];
		document.querySelectorAll('#jl-custom-list .jl-custom-row').forEach(row => {
			const key = row.querySelector('.jl-custom-key')?.value.trim() || '';
			const val = row.querySelector('.jl-custom-val')?.value.trim() || '';
			if (key) custom_properties.push({ key, value: val });
		});

		/* Ensure vehicle cache is up to date for the currently visible type (saves offers_sub too) */
		saveVehicleState();

		/* Build both vehicle configs — offers_sub is per-type (stored in cache) */
		const _featLimit = featLimit;
		const vc_listings  = vehicleCfgCache.listings  || JL_VEHICLE_CFG.listings  || {};
		const vc_used      = vehicleCfgCache['used-listings'] || JL_VEHICLE_CFG['used-listings'] || {};
		const vehicle_listings      = { ...vc_listings, features_limit: _featLimit, offers_sub: vc_listings.offers_sub  || {} };
		const vehicle_used_listings = { ...vc_used,     features_limit: _featLimit, offers_sub: vc_used.offers_sub      || {} };

		/* Collect non-vehicle schema type configs */
		const slugToKey = {
			'lease-offer':'lease_offer','finance-offer':'finance_offer',
			'conditional-offer':'conditional_offer','service-offer':'service_offer',
			'lease-offer-srp':'lease_offer_srp','finance-offer-srp':'finance_offer_srp',
			'conditional-offer-srp':'conditional_offer_srp','service-offer-srp':'service_offer_srp',
			'research-srp':'research_srp',
			'archive-srp-listings':'archive_srp_listings','archive-srp-used-listings':'archive_srp_used_listings',
			'srp-item-listings':'srp_item_listings','srp-item-used-listings':'srp_item_used_listings',
			'research':'research'
		};
		const nonVehicle = {};
		Object.entries(slugToKey).forEach(([slug, key]) => {
			const state = collectStateForType(slug);
			/* For archive_srp both slugs map to same key — merge them (listings wins if both set) */
			nonVehicle[key] = { ...(nonVehicle[key] || {}), ...state };
		});

		/* Collect per-post-type SRP item offers sub-fields */
		const srp_item_offers_sub_listings     = {};
		const srp_item_offers_sub_used_listings = {};
		document.querySelectorAll('.jl-srp-osub-field[data-srp-osub][data-pt]').forEach(el => {
			const k = el.dataset.srpOsub;
			const v = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value.trim();
			if (el.dataset.pt === 'listings') srp_item_offers_sub_listings[k] = v;
			else srp_item_offers_sub_used_listings[k] = v;
		});

		return { mode, post_types: postTypes, archive_limit: archiveLimit,
			vehicle: vehicle_listings, /* backward compat */
			vehicle_listings, vehicle_used_listings,
			custom_properties, srp_item_offers_sub_listings, srp_item_offers_sub_used_listings,
			...nonVehicle };
	}

	/* ── Sample data for live preview ──────────────────────────── */
	const SAMPLE = {
		url:'https://dealer.example.com/listings/2024-toyota-camry-xse-4t1bz1fb3ru123456/',
		id: 'https://dealer.example.com/listings/2024-toyota-camry-xse-4t1bz1fb3ru123456/#vehicle',
		name:'2024 Toyota Camry XSE',
		description:'A precision-tuned sport sedan with an 8-speed Direct-Shift automatic and sport-tuned suspension.',
		image:'https://cdn.dealer.example.com/inventory/4T1BZ1FB3RU123456/main.webp',
		make:'Toyota', model:'Camry', trim:'XSE', year:'2024', bodyType:'Sedan',
		vin:'4T1BZ1FB3RU123456', engine:'2.5L 4-Cylinder Dynamic-Force',
		fuelType:'Gasoline', transmission:'8-Speed Direct-Shift Automatic',
		mileage:0, color:'Midnight Black Metallic', interiorColor:'Black SofTex',
		price:'28990', dealer:'Springfield Toyota',
		features:['Wireless Apple CarPlay','Wireless Android Auto','Toyota Safety Sense 2.5+','8" Touchscreen','Dual-Zone Climate'],
	};

	/* Flat key→value map used for ACF/API key lookup and {{template}} resolution */
	const SAMPLE_KEYS = {
		make: 'Toyota', model: 'Camry', trim: 'XSE', year: '2024',
		body_style: 'Sedan', vin: '4T1BZ1FB3RU123456', stock: 'T12345', stock_number: 'T12345',
		condition: 'New', engine: '2.5L 4-Cylinder Dynamic-Force',
		fuel_type: 'Gasoline', transmission: '8-Speed Direct-Shift Automatic',
		drive_type: 'FWD', mileage: '0',
		exterior_color: 'Midnight Black Metallic', interior_color: 'Black SofTex',
		price: '28990', msrp: '31990', internet_price: '28990',
		dealer_name: 'Springfield Toyota',
		description: 'A precision-tuned sport sedan with sport-tuned suspension.',
		ai_vdp_description: 'A precision-tuned sport sedan with sport-tuned suspension.',
		doors: '4', cylinders: '4', mpg_city: '28', mpg_highway: '38',
		certified: '0', images: 'https://cdn.dealer.example.com/inventory/main.webp',
	};

	/* Resolve {{token}} placeholders against SAMPLE_KEYS */
	function resolveTemplate(str) {
		return str.replace(/\{\{(\w+)\}\}/g, (_, k) =>
			SAMPLE_KEYS[k] !== undefined ? SAMPLE_KEYS[k] : '{{' + k + '}}'
		);
	}

	/* resolve preview value: static_value (with template) > custom key > fallback */
	function rv(prop, fallback) {
		if (!prop || typeof prop !== 'object') return prop ? fallback : null;
		if (prop.static_value && prop.static_value.trim()) {
			return resolveTemplate(prop.static_value.trim());
		}
		/* Use custom acf_key to pull from SAMPLE_KEYS */
		const customKey = (prop.acf_key || '').trim();
		if (customKey && SAMPLE_KEYS[customKey] !== undefined) {
			return SAMPLE_KEYS[customKey];
		}
		return fallback;
	}
	function isOn(prop) {
		if (typeof prop === 'boolean') return prop;
		return prop && prop.enabled;
	}

	function buildPreviewSchema(state) {
		const v = state.vehicle || {};
		const s = {
			'@context':'https://schema.org',
			'@type':'Vehicle',
			'@id': SAMPLE.id,
			'url': SAMPLE.url,
		};
		if (isOn(v.name))                        s.name                        = rv(v.name, SAMPLE.name);
		if (isOn(v.description))                 s.description                 = rv(v.description, SAMPLE.description);
		if (isOn(v.image))                       s.image                       = rv(v.image, SAMPLE.image);
		if (isOn(v.brand))                       s.brand                       = {'@type':'Brand','name': rv(v.brand, SAMPLE.make)};
		if (isOn(v.model))                       s.model                       = rv(v.model, SAMPLE.model);
		if (isOn(v.vehicleConfiguration))        s.vehicleConfiguration        = rv(v.vehicleConfiguration, SAMPLE.trim);
		if (isOn(v.vehicleModelDate))            s.vehicleModelDate            = rv(v.vehicleModelDate, SAMPLE.year);
		if (isOn(v.bodyType))                    s.bodyType                    = rv(v.bodyType, SAMPLE.bodyType);
		if (isOn(v.vehicleIdentificationNumber)) s.vehicleIdentificationNumber = rv(v.vehicleIdentificationNumber, SAMPLE.vin);
		if (isOn(v.vehicleEngine))               s.vehicleEngine               = {'@type':'EngineSpecification','description': rv(v.vehicleEngine, SAMPLE.engine)};
		if (isOn(v.fuelType))                    s.fuelType                    = rv(v.fuelType, SAMPLE.fuelType);
		if (isOn(v.vehicleTransmission))         s.vehicleTransmission         = rv(v.vehicleTransmission, SAMPLE.transmission);
		if (isOn(v.mileageFromOdometer))         s.mileageFromOdometer         = {'@type':'QuantitativeValue','value': rv(v.mileageFromOdometer, SAMPLE.mileage),'unitCode':'SMI'};
		if (isOn(v.color))                       s.color                       = rv(v.color, SAMPLE.color);
		if (isOn(v.vehicleInteriorColor))        s.vehicleInteriorColor        = rv(v.vehicleInteriorColor, SAMPLE.interiorColor);
		if (isOn(v.offers)) {
			const os = v.offers_sub || {};
			const currency   = os.price_currency || 'USD';
			const priceKey   = os.price_key || os.price_acf || os.price_api || 'price';
			const priceVal   = SAMPLE[priceKey] ?? SAMPLE.price;
			const avail      = os.availability || 'InStock';
			const cond       = os.condition === 'auto' || !os.condition ? 'NewCondition' : os.condition;
			const sellerName = SAMPLE[os.seller_name_key || os.seller_name_acf || os.seller_name_api || 'dealer'] ?? SAMPLE.dealer;
			s.offers = {
				'@type'        : 'Offer',
				'priceCurrency': currency,
				'price'        : priceVal,
				'availability' : 'https://schema.org/' + avail,
				'itemCondition': 'https://schema.org/' + cond,
				'url'          : SAMPLE.url,
				'seller'       : {'@type':'AutoDealer','name': sellerName, 'url':'https://dealer.example.com/'},
			};
		}
		if (isOn(v.additionalProperty)) {
			const lim  = parseInt(v.features_limit, 10) || 0;
			const list = lim > 0 ? SAMPLE.features.slice(0, lim) : SAMPLE.features;
			s.additionalProperty = list.map(f => ({'@type':'PropertyValue','name':'Feature','value':f}));
		}

		/* Extended Specs */
		if (isOn(v.numberOfDoors))           s.numberOfDoors           = rv(v.numberOfDoors, '4');
		if (isOn(v.driveWheelConfiguration)) s.driveWheelConfiguration = rv(v.driveWheelConfiguration, 'FWD');
		if (isOn(v.vehicleSeatingCapacity))  s.vehicleSeatingCapacity  = rv(v.vehicleSeatingCapacity, '5');
		if (isOn(v.fuelConsumption))         s.fuelConsumption         = {'@type':'QuantitativeValue','value': rv(v.fuelConsumption, '28'),'unitText':'mpg'};
		if (isOn(v.knownVehicleDamages))     s.knownVehicleDamages     = rv(v.knownVehicleDamages, 'None');
		if (isOn(v.vehicleSpecialUsage))     s.vehicleSpecialUsage     = rv(v.vehicleSpecialUsage, 'DrivingSchoolVehicle');
		if (isOn(v.meetsEmissionStandard))   s.meetsEmissionStandard   = rv(v.meetsEmissionStandard, 'EPA Tier 3');
		if (isOn(v.numberOfForwardGears))    s.numberOfForwardGears    = rv(v.numberOfForwardGears, '8');

		/* Custom Properties */
		(state.custom_properties || []).forEach(cp => {
			if (cp.key && cp.key.trim()) {
				s[cp.key.trim()] = resolveTemplate((cp.value || '').trim());
			}
		});

		return s;
	}

	/* ── Interactive JSON tree renderer ────────────────────────── */
	let _juid = 0;
	let activeSchemaType = 'archive-srp-listings'; /* used for non-pt schema items only */

	function jEsc(s) {
		return String(s)
			.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function jNode(val, depth) {
		const ind = '  '.repeat(depth);
		const chi = '  '.repeat(depth + 1);

		if (val === null)
			return '<span class="j-null">null</span>';
		if (typeof val === 'boolean')
			return `<span class="j-bool">${val}</span>`;
		if (typeof val === 'number')
			return `<span class="j-num">${val}</span>`;
		if (typeof val === 'string') {
			const s = jEsc(val);
			return /^https?:\/\//.test(val)
				? `<span class="j-url">"${s}"</span>`
				: `<span class="j-str">"${s}"</span>`;
		}

		if (Array.isArray(val)) {
			if (!val.length) return '<span class="j-bracket">[]</span>';
			const uid  = ++_juid;
			const rows = val.map((v, i) =>
				`${chi}${jNode(v, depth + 1)}${i < val.length - 1 ? '<span class="j-punct">,</span>' : ''}`
			).join('\n');
			const label = `${val.length} item${val.length !== 1 ? 's' : ''}`;
			return (
				`<span class="j-toggle" data-uid="${uid}" title="Collapse">▾</span>` +
				`<span class="j-bracket">[</span>` +
				`<span class="j-body" id="jb${uid}">\n${rows}\n${ind}</span>` +
				`<span class="j-bracket" id="jcb${uid}">]</span>` +
				`<span class="j-hint" id="jh${uid}" style="display:none">` +
					`<span class="j-bracket"> [</span>` +
					`<span> ${label} </span>` +
					`<span class="j-bracket">]</span>` +
				`</span>`
			);
		}

		if (typeof val === 'object') {
			const keys = Object.keys(val);
			if (!keys.length) return '<span class="j-bracket">{}</span>';
			const uid  = ++_juid;
			const rows = keys.map((k, i) => {
				const kspan = k.startsWith('@')
					? `<span class="j-type">"${jEsc(k)}"</span>`
					: `<span class="j-key">"${jEsc(k)}"</span>`;
				const comma = i < keys.length - 1 ? '<span class="j-punct">,</span>' : '';
				return `${chi}${kspan}<span class="j-punct">: </span>${jNode(val[k], depth + 1)}${comma}`;
			}).join('\n');
			const label = `${keys.length} prop${keys.length !== 1 ? 's' : ''}`;
			return (
				`<span class="j-toggle" data-uid="${uid}" title="Collapse">▾</span>` +
				`<span class="j-bracket">{</span>` +
				`<span class="j-body" id="jb${uid}">\n${rows}\n${ind}</span>` +
				`<span class="j-bracket" id="jcb${uid}">}</span>` +
				`<span class="j-hint" id="jh${uid}" style="display:none">` +
					`<span class="j-bracket"> {</span>` +
					`<span> ${label} </span>` +
					`<span class="j-bracket">}</span>` +
				`</span>`
			);
		}

		return jEsc(String(val));
	}

	/* ── Render preview ─────────────────────────────────────────── */
	function renderPreview() {
		/* In real mode, only re-render demo if explicitly switching back */
		if (typeof previewMode !== 'undefined' && previewMode === 'real') return;
		_juid = 0;
		const state      = collectState();
		const effectType = computeActiveSchemaType();
		let schema;
		const isVehicle = effectType === 'vehicle-listings' || effectType === 'vehicle-used-listings';
		if (isVehicle) {
			const vState = effectType === 'vehicle-used-listings'
				? { ...state, vehicle: state.vehicle_used_listings }
				: { ...state, vehicle: state.vehicle_listings };
			schema = buildPreviewSchema(vState);
		} else {
			schema = buildSchemaForType(effectType);
		}
		const json = JSON.stringify(schema);

		document.getElementById('jl-preview-body').innerHTML = jNode(schema, 0);

		if (isVehicle) {
			const vCfg = effectType === 'vehicle-used-listings' ? state.vehicle_used_listings : state.vehicle_listings;
			const active = Object.entries(vCfg || {})
				.filter(([k, v]) => k !== 'features_limit' && k !== 'offers_sub' && isOn(v)).length;
			document.getElementById('jl-active-count').textContent = active;
			document.getElementById('jl-stat-props').textContent   = active;
		}
		const bytes = new Blob([json]).size;
		document.getElementById('jl-stat-bytes').textContent = bytes > 1024 ? (bytes/1024).toFixed(1)+'kb' : bytes+'b';
	}

	/* ── Build preview JSON for non-vehicle schema types ─────────── */
	function buildSchemaForType(schemaType) {
		const props = collectStateForType(schemaType);
		const SITE  = 'https://dealer.example.com';
		const DLRNAME = 'Springfield Auto';

		/* ── Offer types ── */
		if (['lease-offer','finance-offer','conditional-offer','service-offer'].includes(schemaType)) {
			const s = { '@context':'https://schema.org', '@type':'Offer' };
			if (isOn(props.name))          s.name          = rv(props.name,          schemaType === 'service-offer' ? 'Oil Change & Filter Service' : '2024 Toyota Camry XSE Lease');
			if (isOn(props.description))   s.description   = rv(props.description,   'Contact us for details and eligibility.');
			if (isOn(props.priceCurrency)) s.priceCurrency = rv(props.priceCurrency, 'USD');
			if (isOn(props.price)) {
				const defPrice = schemaType === 'service-offer' ? '49.95' : schemaType === 'finance-offer' ? '399' : '289';
				s.price = rv(props.price, defPrice);
			}
			if (isOn(props.validThrough))  s.validThrough  = rv(props.validThrough,  '2026-12-31');
			if (isOn(props.availability)) {
				const av = rv(props.availability, 'InStock');
				s.availability = av.startsWith('http') ? av : 'https://schema.org/' + av;
			}
			if (isOn(props.url))           s.url           = rv(props.url, SITE + '/offers/sample-offer/');
			if (isOn(props.seller))        s.seller        = { '@type':'AutoDealer', 'name': rv(props.seller, DLRNAME), 'url': SITE + '/' };
			if (schemaType === 'service-offer' && isOn(props.itemOffered))
				s.itemOffered = { '@type':'Service', 'name': rv(props.itemOffered, 'Oil Change & Filter') };
			return s;
		}

		/* ── CollectionPage ── */
		if (schemaType === 'archive-srp-listings' || schemaType === 'archive-srp-used-listings') {
			const isUsed    = schemaType === 'archive-srp-used-listings';
			const itemStype = isUsed ? 'srp-item-used-listings' : 'srp-item-listings';
			const osubPt    = isUsed ? 'used-listings' : 'listings';
			const slug      = isUsed ? 'used-listings' : 'listings';
			const lim       = parseInt(document.getElementById('jl-archive-limit').value, 10) || 24;
			const iprops    = collectStateForType(itemStype);

			/* Read offers sub-fields from the form for this post type */
			const osub = {};
			document.querySelectorAll('.jl-srp-osub-field[data-srp-osub][data-pt="' + osubPt + '"]').forEach(el => {
				osub[el.dataset.srpOsub] = el.type === 'checkbox' ? el.checked : el.value.trim();
			});
			const oCurrency   = osub.price_currency  || 'USD';
			const oAvail      = osub.availability    || 'InStock';
			const oCondRaw    = osub.condition       || (isUsed ? 'UsedCondition' : 'NewCondition');
			const oCond       = (oCondRaw === 'auto' || !oCondRaw) ? (isUsed ? 'UsedCondition' : 'NewCondition') : oCondRaw;
			const oShowSeller = osub.show_seller !== false && osub.show_seller !== '';
			const oSellerApi  = osub.seller_name_key || osub.seller_name_api || 'dealer_name';

			const NEW_SAMPLES = [
				{ name:'2026 Honda Civic Sport', make:'Honda', model:'Civic Sedan', year:'2026', vin:'2HGFE2F51TH605186', price:'27890', dealer:'Burns Honda', photo:'https://cdn.example.com/civic.jpg' },
				{ name:'2025 Toyota Camry XSE',  make:'Toyota', model:'Camry',      year:'2025', vin:'4T1BZ1FB3RU000001', price:'32990', dealer:'Burns Honda', photo:'https://cdn.example.com/camry.jpg' },
				{ name:'2024 Ford Escape ST',     make:'Ford',   model:'Escape',     year:'2024', vin:'1FMCU0GX5NUB00001', price:'29500', dealer:'Burns Honda', photo:'https://cdn.example.com/escape.jpg' },
			];
			const USED_SAMPLES = [
				{ name:'2021 Honda Accord EX', make:'Honda',  model:'Accord',   year:'2021', vin:'1HGCV1F34MA000001', price:'21990', dealer:'Burns Honda', photo:'https://cdn.example.com/accord.jpg' },
				{ name:'2020 Toyota RAV4 XLE', make:'Toyota', model:'RAV4',     year:'2020', vin:'2T3P1RFV8LW000001', price:'24990', dealer:'Burns Honda', photo:'https://cdn.example.com/rav4.jpg' },
				{ name:'2019 Ford Fusion SE',   make:'Ford',   model:'Fusion SE', year:'2019', vin:'3FA6P0HD4KR000001', price:'16490', dealer:'Burns Honda', photo:'https://cdn.example.com/fusion.jpg' },
			];
			const SAMPLES = isUsed ? USED_SAMPLES : NEW_SAMPLES;
			const count = Math.min(lim, SAMPLES.length);
			const items = [];
			const baseSlug = isUsed ? 'used-listings' : 'listings';
			for (let i = 0; i < count; i++) {
				const sv  = SAMPLES[i];
				const url = SITE + '/' + baseSlug + '/sample-' + (i + 1) + '/';
				const veh = { '@type':'Vehicle', 'name': sv.name, 'url': url };
				if (isOn(iprops.brand))                       veh.brand                       = { '@type':'Brand', 'name': rv(iprops.brand, sv.make) };
				if (isOn(iprops.model))                       veh.model                       = rv(iprops.model, sv.model);
				if (isOn(iprops.vehicleModelDate))            veh.vehicleModelDate            = rv(iprops.vehicleModelDate, sv.year);
				if (isOn(iprops.vehicleIdentificationNumber)) veh.vehicleIdentificationNumber = rv(iprops.vehicleIdentificationNumber, sv.vin);
				if (isOn(iprops.image))                       veh.image                       = rv(iprops.image, sv.photo);
				if (isOn(iprops.offers)) {
					const offer = {
						'@type'        : 'Offer',
						'priceCurrency': oCurrency,
						'price'        : sv.price,
						'availability' : 'https://schema.org/' + oAvail,
						'itemCondition': 'https://schema.org/' + oCond,
						'url'          : url,
					};
					if (oShowSeller) {
						offer.seller = { '@type':'AutoDealer', 'name': sv[oSellerApi] || sv.dealer };
					}
					veh.offers = offer;
				}
				items.push({ '@type':'ListItem', 'position': i + 1, 'url': url, 'item': veh });
			}
			if (lim > SAMPLES.length) {
				items.push({ '@type':'ListItem', 'position': SAMPLES.length + 1, 'url': SITE + '/' + baseSlug + '/', 'item': { '@type':'Vehicle', 'name':'… + ' + (lim - SAMPLES.length) + ' more vehicles' } });
			}
			const defName = isUsed ? 'Pre-Owned Vehicles' : 'New Vehicles';
			const defDesc = isUsed ? 'Browse our pre-owned vehicle inventory.' : 'Browse our new vehicle inventory.';
			const s = { '@context':'https://schema.org', '@type':'CollectionPage', '@id': SITE + '/' + baseSlug + '/#collection' };
			if (isOn(props.name))        s.name        = rv(props.name,        defName);
			if (isOn(props.description)) s.description = rv(props.description, defDesc);
			if (isOn(props.url))         s.url         = rv(props.url,         SITE + '/' + baseSlug + '/');
			s.mainEntity = { '@type':'ItemList', 'numberOfItems': lim, 'itemListElement': items };
			return s;
		}

		/* ── Offer archive (CollectionPage with Offer items) ── */
		const OFFER_SRP_MAP = {
			'lease-offer-srp':       { single: 'lease-offer',       slug: 'lease-offers',       defName: 'Lease Offers',                defDesc: 'Browse our current lease offers.' },
			'finance-offer-srp':     { single: 'finance-offer',     slug: 'finance-offers',     defName: 'Finance Offers',              defDesc: 'Browse our current finance specials.' },
			'conditional-offer-srp': { single: 'conditional-offer', slug: 'conditional-offers', defName: 'Conditional Offers',          defDesc: 'Browse our time-limited promotions.' },
			'service-offer-srp':     { single: 'service-offer',     slug: 'service-offers',     defName: 'Service Offers',              defDesc: 'Browse our current service specials.' },
			'research-srp':          { single: 'research',          slug: 'research',           defName: 'Research & Buying Guides',    defDesc: 'Browse our expert buying guides and vehicle research articles.' },
		};
		if (OFFER_SRP_MAP[schemaType]) {
			const meta    = OFFER_SRP_MAP[schemaType];
			const iprops  = collectStateForType(meta.single);
			const ITEM_SAMPLES = {
				'lease-offer':       [ { title:'2024 Toyota Camry XSE Lease', price:'289', end:'2026-12-31' }, { title:'2025 Honda CR-V Sport Lease', price:'349', end:'2026-12-31' }, { title:'2025 Ford Escape ST Lease', price:'319', end:'2026-12-31' } ],
				'finance-offer':     [ { title:'0% APR for 60 Months on Camry', price:'399', end:'2026-12-31' }, { title:'1.9% APR for 48 Months on Accord', price:'449', end:'2026-12-31' }, { title:'2.9% APR on Escape', price:'375', end:'2026-12-31' } ],
				'conditional-offer': [ { title:'$2,500 Loyalty Cash Bonus', price:'2500', end:'2026-09-30' }, { title:'$1,000 Military Appreciation', price:'1000', end:'2026-09-30' }, { title:'College Grad Bonus Cash', price:'500', end:'2026-09-30' } ],
				'service-offer':     [ { title:'Oil Change & Filter Special', price:'49.95', end:'2026-12-31' }, { title:'Tire Rotation Offer', price:'19.95', end:'2026-12-31' }, { title:'Brake Inspection & Service', price:'89.95', end:'2026-12-31' } ],
				'research':          [ { title:'2024 Toyota Camry Buyer\'s Guide', date:'2024-01-15', img: SITE+'/media/camry-guide.webp' }, { title:'Best Family SUVs of 2025', date:'2024-11-01', img: SITE+'/media/suvs.webp' }, { title:'Electric vs. Hybrid: Which is Right for You?', date:'2025-03-10', img: SITE+'/media/ev.webp' } ],
			};
			const samples = ITEM_SAMPLES[meta.single] || [];
			const archiveUrl = SITE + '/' + meta.slug + '/';
			const isResearch = meta.single === 'research';
			const items = samples.map((sv, i) => {
				const url = SITE + '/' + meta.slug + '/sample-' + (i + 1) + '/';
				if (isResearch) {
					const art = { '@type':'Article', 'name': sv.title, 'url': url };
					if (isOn(iprops.headline))      art.headline      = rv(iprops.headline,      sv.title);
					if (isOn(iprops.description))   art.description   = rv(iprops.description,   'An in-depth guide from ' + DLRNAME + '.');
					if (isOn(iprops.author))        art.author        = { '@type':'Organization', 'name': rv(iprops.author, DLRNAME) };
					if (isOn(iprops.datePublished)) art.datePublished = rv(iprops.datePublished, sv.date);
					if (isOn(iprops.image))         art.image         = rv(iprops.image,         sv.img);
					return { '@type':'ListItem', 'position': i + 1, 'url': url, 'item': art };
				}
				const offer = { '@type':'Offer', 'name': sv.title, 'url': url };
				if (isOn(iprops.description))   offer.description   = rv(iprops.description,   'Contact us for details and eligibility.');
				if (isOn(iprops.priceCurrency)) offer.priceCurrency = rv(iprops.priceCurrency, 'USD');
				if (isOn(iprops.price))         offer.price         = rv(iprops.price,         sv.price);
				if (isOn(iprops.validThrough))  offer.validThrough  = rv(iprops.validThrough,  sv.end);
				if (isOn(iprops.availability)) { const av = rv(iprops.availability, 'InStock'); offer.availability = av.startsWith('http') ? av : 'https://schema.org/' + av; }
				if (isOn(iprops.seller))        offer.seller        = { '@type':'AutoDealer', 'name': rv(iprops.seller, DLRNAME), 'url': SITE + '/' };
				if (meta.single === 'service-offer' && isOn(iprops.itemOffered))
					offer.itemOffered = { '@type':'Service', 'name': rv(iprops.itemOffered, sv.title) };
				return { '@type':'ListItem', 'position': i + 1, 'url': url, 'item': offer };
			});
			const s = { '@context':'https://schema.org', '@type':'CollectionPage', '@id': archiveUrl + '#collection' };
			if (isOn(props.name))        s.name        = rv(props.name,        meta.defName);
			if (isOn(props.description)) s.description = rv(props.description, meta.defDesc);
			if (isOn(props.url))         s.url         = rv(props.url,         archiveUrl);
			s.mainEntity = { '@type':'ItemList', 'numberOfItems': samples.length, 'itemListElement': items };
			return s;
		}

		/* ── Article (Research) ── */
		if (schemaType === 'research') {
			const s = { '@context':'https://schema.org', '@type':'Article' };
			if (isOn(props.headline))      s.headline      = rv(props.headline,      '2024 Toyota Camry Buyer\'s Guide');
			if (isOn(props.description))   s.description   = rv(props.description,   'Everything you need about the 2024 Toyota Camry.');
			if (isOn(props.author))        s.author        = { '@type':'Organization', 'name': rv(props.author, DLRNAME) };
			if (isOn(props.datePublished)) s.datePublished = rv(props.datePublished,  '2024-01-15');
			if (isOn(props.image))         s.image         = rv(props.image,          SITE + '/media/camry.webp');
			s.publisher = { '@type':'Organization', 'name': DLRNAME, 'url': SITE + '/' };
			return s;
		}

		return { '@context':'https://schema.org', '@type':'Thing', 'name': schemaType };
	}

	/* ── Mode toggle UX ─────────────────────────────────────────── */
	function applyModeUI(mode) {
		const shell = document.getElementById('jl-shell');
		shell.classList.toggle('jl-mode-legacy',  mode === 'legacy');
		shell.classList.toggle('jl-mode-builder', mode === 'builder');
		document.getElementById('jl-mode-note-legacy').style.display  = mode === 'legacy'  ? '' : 'none';
		document.getElementById('jl-mode-note-builder').style.display = mode === 'builder' ? '' : 'none';
	}
	document.querySelectorAll('input[name="jl_mode"]').forEach(r => {
		r.addEventListener('change', () => applyModeUI(r.value));
	});
	applyModeUI('<?php echo esc_js( $mode ); ?>');

	/* ── Source editor toggles ─────────────────────────────────── */
	document.querySelectorAll('.jl-src-toggle').forEach(btn => {
		btn.addEventListener('click', () => {
			const expanded = btn.getAttribute('aria-expanded') === 'true';
			const panelId  = btn.getAttribute('aria-controls');
			const panel    = document.getElementById(panelId);
			btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			if (panel) panel.classList.toggle('jl-src-open', !expanded);
		});
	});

	/* Template variable chip → insert at cursor into the static_value input of the same card */
	document.querySelectorAll('.jl-var-chip').forEach(chip => {
		chip.addEventListener('click', () => {
			const propKey = chip.dataset.for;
			const editor  = document.getElementById('jl-src-' + propKey);
			const inp     = editor?.querySelector('[data-src="static_value"]');
			if (!inp) return;
			const tpl   = chip.dataset.tpl;
			const start = inp.selectionStart ?? inp.value.length;
			const end   = inp.selectionEnd   ?? inp.value.length;
			inp.value   = inp.value.slice(0, start) + tpl + inp.value.slice(end);
			inp.selectionStart = inp.selectionEnd = start + tpl.length;
			inp.focus();
			inp.dispatchEvent(new Event('input', { bubbles: true }));
		});
	});

	/* Update static-preview div and warning note when static_value input changes */
	function updateStaticPreview(inp) {
		const propKey = inp.dataset.prop;
		const preview = document.getElementById('jl-sp-' + propKey);
		const note    = inp.closest('.jl-src-editor')?.querySelector('.jl-src-static-note');
		const raw     = inp.value.trim();
		if (preview) {
			if (raw) {
				const resolved = resolveTemplate(raw);
				preview.textContent = '→ ' + resolved;
				preview.classList.add('jl-sp-show');
			} else {
				preview.classList.remove('jl-sp-show');
			}
		}
		if (note) note.style.display = raw ? 'block' : 'none';
	}

	/* Show static-value warning inline when user types */
	document.querySelectorAll('.jl-src-input[data-src="static_value"]').forEach(inp => {
		inp.addEventListener('input', () => updateStaticPreview(inp));
		/* Run once on load for pre-filled values */
		if (inp.value.trim()) updateStaticPreview(inp);
	});

	/* ── Wire toggles ───────────────────────────────────────────── */
	document.querySelectorAll('input[data-prop][type="checkbox"]').forEach(inp => {
		inp.addEventListener('change', () => {
			const card = inp.closest('.jl-prop');
			if (card) card.classList.toggle('jl-off', !inp.checked);
			renderPreview();
		});
	});
	document.querySelectorAll('.jl-src-input').forEach(inp => {
		inp.addEventListener('input', renderPreview);
	});
	document.querySelectorAll('.jl-srp-osub-field').forEach(el => {
		el.addEventListener(el.type === 'checkbox' ? 'change' : 'input', renderPreview);
	});
	document.getElementById('jl-feat-limit').addEventListener('input', renderPreview);
	document.getElementById('jl-archive-limit').addEventListener('input', renderPreview);

	/* ── Custom properties: add / remove / live preview ─────── */
	function makeCustomRow(key, val) {
		const row = document.createElement('div');
		row.className = 'jl-custom-row';
		row.innerHTML =
			`<input type="text" class="jl-custom-key" list="jl-custom-keys" value="${key}" placeholder="schema key e.g. numberOfPreviousOwners">` +
			`<input type="text" class="jl-custom-val" value="${val}" placeholder="value or {{year}} template">` +
			`<button type="button" class="jl-custom-remove" title="Remove">✕</button>`;
		row.querySelector('.jl-custom-remove').addEventListener('click', () => {
			row.remove();
			toggleCustomEmpty();
			renderPreview();
		});
		row.querySelector('.jl-custom-key').addEventListener('input', renderPreview);
		row.querySelector('.jl-custom-val').addEventListener('input', renderPreview);
		return row;
	}
	function toggleCustomEmpty() {
		const list  = document.getElementById('jl-custom-list');
		let   empty = document.getElementById('jl-custom-empty');
		const hasRows = list.querySelectorAll('.jl-custom-row').length > 0;
		if (hasRows && empty)  { empty.remove(); }
		if (!hasRows && !empty) {
			empty = document.createElement('div');
			empty.id = 'jl-custom-empty';
			empty.className = 'jl-custom-empty';
			empty.textContent = 'No custom properties yet. Click "Add Property" to add any schema.org field.';
			list.appendChild(empty);
		}
	}
	document.getElementById('jl-add-custom').addEventListener('click', () => {
		const list = document.getElementById('jl-custom-list');
		const empty = document.getElementById('jl-custom-empty');
		if (empty) empty.remove();
		const row = makeCustomRow('', '');
		list.appendChild(row);
		row.querySelector('.jl-custom-key').focus();
		renderPreview();
	});
	/* Wire existing (server-rendered) custom rows */
	document.querySelectorAll('#jl-custom-list .jl-custom-row').forEach(row => {
		row.querySelector('.jl-custom-remove').addEventListener('click', () => {
			row.remove(); toggleCustomEmpty(); renderPreview();
		});
		row.querySelector('.jl-custom-key').addEventListener('input', renderPreview);
		row.querySelector('.jl-custom-val').addEventListener('input', renderPreview);
	});

	/* ── JSON tree toggle (event delegation on preview body) ────── */
	document.getElementById('jl-preview-body').addEventListener('click', e => {
		const toggle = e.target.closest('.j-toggle');
		if (!toggle) return;
		const uid    = toggle.dataset.uid;
		const body   = document.getElementById('jb' + uid);
		const hint   = document.getElementById('jh' + uid);
		const close  = document.getElementById('jcb' + uid);
		const isOpen = body && body.style.display !== 'none';
		if (body)  body.style.display  = isOpen ? 'none' : '';
		if (hint)  hint.style.display  = isOpen ? 'inline' : 'none';
		if (close) close.style.display = isOpen ? 'none' : '';
		toggle.textContent = isOpen ? '▸' : '▾';
		toggle.title       = isOpen ? 'Expand' : 'Collapse';
	});

	function setAllNodes(collapsed) {
		document.querySelectorAll('#jl-preview-body .j-toggle').forEach(toggle => {
			const uid   = toggle.dataset.uid;
			const body  = document.getElementById('jb' + uid);
			const hint  = document.getElementById('jh' + uid);
			const close = document.getElementById('jcb' + uid);
			if (body)  body.style.display  = collapsed ? 'none' : '';
			if (hint)  hint.style.display  = collapsed ? 'inline' : 'none';
			if (close) close.style.display = collapsed ? 'none' : '';
			toggle.textContent = collapsed ? '▸' : '▾';
			toggle.title       = collapsed ? 'Expand' : 'Collapse';
		});
	}
	document.getElementById('jl-collapse-all').addEventListener('click', () => setAllNodes(true));
	document.getElementById('jl-expand-all').addEventListener('click',   () => setAllNodes(false));

	/* ── Copy ───────────────────────────────────────────────────── */
	document.getElementById('jl-copy-btn').addEventListener('click', () => {
		const state      = collectState();
		const effectType = computeActiveSchemaType();
		const isVeh      = effectType === 'vehicle-listings' || effectType === 'vehicle-used-listings';
		const vState     = isVeh && effectType === 'vehicle-used-listings'
			? { ...state, vehicle: state.vehicle_used_listings }
			: { ...state, vehicle: state.vehicle_listings };
		const schema = isVeh ? buildPreviewSchema(vState) : buildSchemaForType(effectType);
		const tag    = `<script type="application/ld+json">\n${JSON.stringify(schema,null,2)}\n<\/script>`;
		navigator.clipboard.writeText(tag).then(() => {
			const btn = document.getElementById('jl-copy-btn');
			btn.textContent = 'copied!';
			btn.classList.add('jl-copied');
			setTimeout(() => { btn.textContent = 'copy'; btn.classList.remove('jl-copied'); }, 1800);
		});
	});

	/* ── Sidebar switching ───────────────────────────────────── */
	document.querySelectorAll('.jl-sb-item[data-pt], .jl-sb-item[data-schema], .jl-sb-item[data-offer]').forEach(item => {
		item.addEventListener('click', () => {
			document.querySelectorAll('.jl-sb-item').forEach(el => el.classList.remove('active'));
			item.classList.add('active');

			const pt    = item.dataset.pt;
			const offer = item.dataset.offer;

			if (pt) {
				isPtMode        = true;
				isOfferMode     = false;
				activePostType  = pt;
			} else if (offer) {
				isPtMode        = false;
				isOfferMode     = true;
				activeOfferType = offer;
				activeOfferTab  = 'srp';
			} else {
				isPtMode         = false;
				isOfferMode      = false;
				activeSchemaType = item.dataset.schema;
			}

			showActivePanel();

			if (typeof previewMode !== 'undefined' && previewMode === 'real') {
				syncPickerPostType();
				loadPostsForPicker();
			} else {
				renderPreview();
			}
		});
	});

	/* ── Inventory inner tabs (Archive/SRP ↔ Vehicle VDP) ────── */
	document.querySelectorAll('.jl-stab[data-tab]').forEach(btn => {
		btn.addEventListener('click', () => {
			activeSchemaTab = btn.dataset.tab;
			showActivePanel();
			if (typeof previewMode !== 'undefined' && previewMode === 'real') {
				syncPickerPostType();
				loadPostsForPicker();
			} else {
				renderPreview();
			}
		});
	});

	/* ── Offer inner tabs (Archive/SRP ↔ Single) ────────────── */
	document.querySelectorAll('.jl-stab[data-offer-tab]').forEach(btn => {
		btn.addEventListener('click', () => {
			activeOfferTab = btn.dataset.offerTab;
			showActivePanel();
			renderPreview();
		});
	});

	/* ── Save ───────────────────────────────────────────────────── */
	document.getElementById('jl-save-btn').addEventListener('click', () => {
		const btn    = document.getElementById('jl-save-btn');
		const notice = document.getElementById('jl-notice');
		const state  = collectState();

		btn.textContent = 'Saving…';
		btn.classList.add('jl-saving');
		notice.classList.remove('jl-show');

		const body = new URLSearchParams({
			action:  'soc_json_ld_save',
			nonce:   nonce,
			config:  JSON.stringify(state),
		});

		fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' })
			.then(r => r.json())
			.then(res => {
				btn.textContent = res.success ? 'Save Changes' : 'Error — try again';
				btn.classList.remove('jl-saving');
				if (res.success) {
					btn.classList.add('jl-saved');
					notice.textContent = 'Changes saved.';
					notice.classList.add('jl-show');
					setTimeout(() => {
						btn.classList.remove('jl-saved');
						notice.classList.remove('jl-show');
					}, 2500);
				}
			})
			.catch(() => {
				btn.textContent = 'Error — try again';
				btn.classList.remove('jl-saving');
			});
	});

	/* ── Demo / Real preview toggle ─────────────────────────────── */
	let previewMode = 'demo';
	let cachedRealJson = null;

	function setPreviewMode(mode) {
		previewMode = mode;
		document.getElementById('jl-pmode-demo').classList.toggle('jl-pmode-active', mode === 'demo');
		document.getElementById('jl-pmode-real').classList.toggle('jl-pmode-active', mode === 'real');
		document.getElementById('jl-real-picker').classList.toggle('jl-real-active', mode === 'real');
		if (mode === 'demo') {
			cachedRealJson = null;
			renderPreview();
		} else {
			/* Auto-load posts for the post type that matches the current schema type */
			syncPickerPostType();
			loadPostsForPicker();
		}
	}

	/* Map effective schema type → post type for the real-preview picker */
	const schemaToPostType = {
		'vehicle-listings'          : 'listings',
		'vehicle-used-listings'     : 'used-listings',
		'archive-srp-listings'      : 'listings',
		'archive-srp-used-listings' : 'used-listings',
		'lease-offer'               : 'lease-offers',
		'finance-offer'             : 'finance-offers',
		'conditional-offer'         : 'conditional-offers',
		'service-offer'             : 'service-offers',
		'research'                  : 'research',
	};

	function syncPickerPostType() {
		const pt = schemaToPostType[computeActiveSchemaType()] || 'listings';
		const sel = document.getElementById('jl-pick-posttype');
		if (sel) sel.value = pt;
	}

	function loadPostsForPicker() {
		const postType = document.getElementById('jl-pick-posttype')?.value || 'listings';
		const status   = document.getElementById('jl-real-status');
		const postSel  = document.getElementById('jl-pick-post');
		if (status) { status.textContent = 'Loading…'; status.className = 'jl-real-status'; }
		if (postSel) { postSel.innerHTML = '<option value="">— loading —</option>'; }

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: new URLSearchParams({ action: 'soc_json_ld_get_posts', nonce, post_type: postType }),
		})
			.then(r => r.json())
			.then(res => {
				if (!res.success || !res.data?.posts?.length) {
					if (postSel) postSel.innerHTML = '<option value="">— no posts found —</option>';
					if (status) { status.textContent = 'No posts'; status.className = 'jl-real-status'; }
					return;
				}
				if (postSel) {
					postSel.innerHTML = '<option value="">— select post —</option>' +
						res.data.posts.map(p => `<option value="${p.id}">${p.title.substring(0,60)}</option>`).join('');
				}
				if (status) { status.textContent = res.data.posts.length + ' posts'; status.className = 'jl-real-status jl-rs-ok'; }
			})
			.catch(() => {
				if (status) { status.textContent = 'Error'; status.className = 'jl-real-status jl-rs-err'; }
			});
	}

	function loadRealPreview() {
		const postId = document.getElementById('jl-pick-post')?.value;
		const status = document.getElementById('jl-real-status');
		if (!postId) return;

		if (status) { status.textContent = 'Fetching…'; status.className = 'jl-real-status'; }
		document.getElementById('jl-preview-body').innerHTML = '<span style="color:rgba(255,255,255,.2);">Fetching real data…</span>';

		const state = collectState();
		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: new URLSearchParams({ action: 'soc_json_ld_preview', nonce, post_id: postId, config: JSON.stringify(state) }),
		})
			.then(r => r.json())
			.then(res => {
				if (!res.success || !res.data?.json) {
					document.getElementById('jl-preview-body').innerHTML = '<span style="color:#F87171;">No JSON-LD output for this post.</span>';
					if (status) { status.textContent = 'Empty output'; status.className = 'jl-real-status jl-rs-err'; }
					return;
				}
				try {
					_juid = 0;
					const parsed = JSON.parse(res.data.json);
					cachedRealJson = parsed;
					document.getElementById('jl-preview-body').innerHTML = jNode(parsed, 0);
					const json = res.data.json;
					const bytes = new Blob([json]).size;
					document.getElementById('jl-stat-bytes').textContent = bytes > 1024 ? (bytes/1024).toFixed(1)+'kb' : bytes+'b';
					if (status) { status.textContent = '✓ real data'; status.className = 'jl-real-status jl-rs-ok'; }
				} catch(e) {
					document.getElementById('jl-preview-body').innerHTML = '<span style="color:#F87171;">Could not parse JSON-LD output.</span>';
					if (status) { status.textContent = 'Parse error'; status.className = 'jl-real-status jl-rs-err'; }
				}
			})
			.catch(() => {
				if (status) { status.textContent = 'Request failed'; status.className = 'jl-real-status jl-rs-err'; }
			});
	}

	document.getElementById('jl-pmode-demo').addEventListener('click', () => setPreviewMode('demo'));
	document.getElementById('jl-pmode-real').addEventListener('click', () => setPreviewMode('real'));
	document.getElementById('jl-pick-posttype').addEventListener('change', loadPostsForPicker);
	document.getElementById('jl-real-fetch').addEventListener('click', loadRealPreview);
	/* Also load when post is selected via Enter or direct click */
	document.getElementById('jl-pick-post').addEventListener('change', () => {
		if (document.getElementById('jl-pick-post').value) loadRealPreview();
	});

	/* ── Update active-count for non-vehicle types ───────────────── */
	function updateStypeActiveCounts() {
		document.querySelectorAll('.jl-stype-active-count').forEach(el => {
			const stype = el.closest('[data-stype-stats]')?.dataset.stypeStats;
			if (!stype) return;
			const count = document.querySelectorAll(
				`input[data-prop][data-stype="${stype}"][type="checkbox"]:checked`
			).length;
			el.textContent = count;
		});
	}

	/* Wire checkbox changes to also update active counts */
	document.querySelectorAll('input[data-prop][type="checkbox"]').forEach(inp => {
		inp.addEventListener('change', updateStypeActiveCounts);
	});

	/* ── Initial render ─────────────────────────────────────────── */
	showActivePanel();
	renderPreview();
	updateStypeActiveCounts();
})();
</script>
