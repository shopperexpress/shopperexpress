<?php
/**
 * Block metadata, written by the build. Edit the block.json files instead.
 *
 * @package shopperexpress
 */

return array(
	'block-logo' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/block-logo',
		'title' => 'Block Logo',
		'description' => 'Large logo image display block.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-image',
		'keywords' => array(
			'logo image brand',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'buttons' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/buttons',
		'title' => 'Buttons',
		'description' => 'Info section with a list of button codes.',
		'category' => 'custom-acf-blocks',
		'icon' => 'button',
		'keywords' => array(
			'buttons info list',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'buy' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/buy',
		'title' => 'Buy',
		'description' => 'Feature columns with icons, heading, and slogan.',
		'category' => 'custom-acf-blocks',
		'icon' => 'grid-view',
		'keywords' => array(
			'buy features icons columns',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'contact-information' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/contact-information',
		'title' => 'Contact Information',
		'description' => 'Contact details with phone, social, and weekly schedule.',
		'category' => 'custom-acf-blocks',
		'icon' => 'phone',
		'keywords' => array(
			'contact phone social schedule hours',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'contact-widget' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/contact-widget',
		'title' => 'Contact Widget',
		'description' => 'Full contact widget with contacts, social links, and tabbed hours/info.',
		'category' => 'custom-acf-blocks',
		'icon' => 'businessman',
		'keywords' => array(
			'contact widget social tabs hours',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'content-and-video' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/content-and-video',
		'title' => 'Content and Video',
		'description' => 'Side-by-side content card and Wistia video.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-video',
		'keywords' => array(
			'content video wistia awards',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'content-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/content-block',
		'title' => 'Content Block',
		'description' => 'Cash-offer style content block with icon, text, and images.',
		'category' => 'custom-acf-blocks',
		'icon' => 'align-left',
		'keywords' => array(
			'content block cash offer',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'content-header' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/content-header',
		'title' => 'Content Header',
		'description' => 'Logo, heading, and text block for page headers.',
		'category' => 'custom-acf-blocks',
		'icon' => 'heading',
		'keywords' => array(
			'header content logo title',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'form' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/form',
		'title' => 'Form',
		'description' => 'WPForms embed section with optional heading and text.',
		'category' => 'custom-acf-blocks',
		'icon' => 'forms',
		'keywords' => array(
			'form contact wpforms',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'full-width-image' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/full-width-image',
		'title' => 'Full Width Image',
		'description' => 'Responsive full-width image with optional link and mobile variant.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-image',
		'keywords' => array(
			'image fullwidth banner',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'full-width-slider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/full-width-slider',
		'title' => 'Full Width Slider',
		'description' => 'Date-gated full-width image slider with disclosure support.',
		'category' => 'custom-acf-blocks',
		'icon' => 'slides',
		'keywords' => array(
			'slider fullwidth banner images',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'gallery' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/gallery',
		'title' => 'Gallery',
		'description' => 'Fancybox grid gallery with optional full-width layout.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-gallery',
		'keywords' => array(
			'gallery grid fancybox images',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'get-offer' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/get-offer',
		'title' => 'Get Offer',
		'description' => 'Call-to-action section with background image and offer code.',
		'category' => 'custom-acf-blocks',
		'icon' => 'megaphone',
		'keywords' => array(
			'offer cta background',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'html-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/html-block',
		'title' => 'HTML Block',
		'description' => 'Custom HTML/embed section with optional container and background.',
		'category' => 'custom-acf-blocks',
		'icon' => 'editor-code',
		'keywords' => array(
			'html embed code custom',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'image-left-text-right' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/image-left-text-right',
		'title' => 'Image Left & Text Right',
		'description' => 'Two-column block with image on the left and text on the right.',
		'category' => 'custom-acf-blocks',
		'icon' => 'align-left',
		'keywords' => array(
			'image text columns trade',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'images' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/images',
		'title' => 'Images',
		'description' => 'Simple image gallery section.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-gallery',
		'keywords' => array(
			'images gallery media',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'info-accordion' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/info-accordion',
		'title' => 'Info Accordion',
		'description' => 'FAQ / info accordion with JSON-LD schema output.',
		'category' => 'custom-acf-blocks',
		'icon' => 'list-view',
		'keywords' => array(
			'accordion faq info schema',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'info-images' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/info-images',
		'title' => 'Info Images',
		'description' => 'Titled image section.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-image',
		'keywords' => array(
			'info images title media',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'intro-blocks' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/intro-blocks',
		'title' => 'Intro Blocks',
		'description' => 'Complex intro section with trade-sell columns and cash offer block.',
		'category' => 'custom-acf-blocks',
		'icon' => 'layout',
		'keywords' => array(
			'intro blocks trade sell cash offer',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'intro-slider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/intro-slider',
		'title' => 'Intro Slider',
		'description' => 'Hero slider pulling from offers CPT and/or manually added slides.',
		'category' => 'custom-acf-blocks',
		'icon' => 'slides',
		'keywords' => array(
			'hero slider intro offers',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'logo-section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/logo-section',
		'title' => 'Logo Section',
		'description' => 'Logo grid with configurable columns and optional background colour.',
		'category' => 'custom-acf-blocks',
		'icon' => 'screenoptions',
		'keywords' => array(
			'logos brands partners grid',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-comparison-table' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-comparison-table',
		'title' => 'LP Comparison Table',
		'description' => 'Landing page comparison table (N columns), row values render as a checkmark, "not available" cross, or free text.',
		'category' => 'custom-acf-blocks',
		'icon' => 'editor-table',
		'keywords' => array(
			'landing comparison table versus features',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-content-columns' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-content-columns',
		'title' => 'LP Content Columns',
		'description' => 'Landing page two-column block — text/buttons on one side, image on the other. Light or dark style.',
		'category' => 'custom-acf-blocks',
		'icon' => 'align-pull-left',
		'keywords' => array(
			'landing two columns text image',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-hero' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-hero',
		'title' => 'LP Hero',
		'description' => 'Landing page hero — background image, heading, buttons, and a quick-nav link row.',
		'category' => 'custom-acf-blocks',
		'icon' => 'cover-image',
		'keywords' => array(
			'landing hero visual banner',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-icon-cards' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-icon-cards',
		'title' => 'LP Icon Cards',
		'description' => 'Landing page grid of icon + title + text cards. Optionally link each card, and toggle light/dark/gray style.',
		'category' => 'custom-acf-blocks',
		'icon' => 'grid-view',
		'keywords' => array(
			'landing icon cards grid features',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-service-slider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-service-slider',
		'title' => 'LP Service Slider',
		'description' => 'Landing page slick carousel of service cards — image, title, text, and a CTA button.',
		'category' => 'custom-acf-blocks',
		'icon' => 'slides',
		'keywords' => array(
			'landing services slider carousel cards',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'lp-steps' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/lp-steps',
		'title' => 'LP Steps',
		'description' => 'Landing page text column paired with a numbered step-by-step list.',
		'category' => 'custom-acf-blocks',
		'icon' => 'editor-ol',
		'keywords' => array(
			'landing steps process how it works',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'map-section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/map-section',
		'title' => 'Map Section',
		'description' => 'Google Maps section with address and directions button.',
		'category' => 'custom-acf-blocks',
		'icon' => 'location',
		'keywords' => array(
			'map location address directions',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'offer-cards' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/offer-cards',
		'title' => 'Offer Cards',
		'description' => 'Slick slider of offer/service-offer cards pulled from post types.',
		'category' => 'custom-acf-blocks',
		'icon' => 'slides',
		'keywords' => array(
			'offers cards slider',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'offers' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/offers',
		'title' => 'Offers',
		'description' => 'Two-column offer cards with image and description.',
		'category' => 'custom-acf-blocks',
		'icon' => 'tag',
		'keywords' => array(
			'offers deals promotions',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'reviews-section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/reviews-section',
		'title' => 'Reviews Section',
		'description' => 'Google Reviews widget with live rating, stars and review list.',
		'category' => 'custom-acf-blocks',
		'icon' => 'star-filled',
		'keywords' => array(
			'reviews google rating testimonials',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'single-video' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/single-video',
		'title' => 'Single Video',
		'description' => 'Embed video code section.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-video',
		'keywords' => array(
			'video embed media',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'sub-footer' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/sub-footer',
		'title' => 'Sub Footer',
		'description' => 'Contact information block with addresses and schedule.',
		'category' => 'custom-acf-blocks',
		'icon' => 'building',
		'keywords' => array(
			'footer contact address schedule',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'tabs-slider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/tabs-slider',
		'title' => 'Tabs Slider',
		'description' => 'Vehicle type filter with model slider from options page.',
		'category' => 'custom-acf-blocks',
		'icon' => 'car',
		'keywords' => array(
			'slider tabs models vehicles filter',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'team-section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/team-section',
		'title' => 'Team Section',
		'description' => 'Filterable team member grid with photo, position and contact info.',
		'category' => 'custom-acf-blocks',
		'icon' => 'groups',
		'keywords' => array(
			'team staff members grid filter',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'text-left-image-right' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/text-left-image-right',
		'title' => 'Text Left & Image Right',
		'description' => 'Two-column block with text on the left and image on the right.',
		'category' => 'custom-acf-blocks',
		'icon' => 'align-right',
		'keywords' => array(
			'text image columns',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'text-video-slider' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/text-video-slider',
		'title' => 'Text & Video Slider',
		'description' => 'Specials slider with payment info and Wistia video per offer.',
		'category' => 'custom-acf-blocks',
		'icon' => 'slides',
		'keywords' => array(
			'specials slider video offers payment',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'video-section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/video-section',
		'title' => 'Video Section',
		'description' => 'Product/video section with preferred layout and nested video articles.',
		'category' => 'custom-acf-blocks',
		'icon' => 'format-video',
		'keywords' => array(
			'video section products preferred',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'widget-banner' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/widget-banner',
		'title' => 'Widget banner',
		'description' => 'Widget banner',
		'category' => 'custom-acf-blocks',
		'icon' => 'block-default',
		'keywords' => array(
			'Keyword 1',
			'Keyword 2',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
	'widget-news' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 2,
		'name' => 'acf/widget-news',
		'title' => 'Widget news',
		'description' => 'Widget news',
		'category' => 'custom-acf-blocks',
		'icon' => 'block-default',
		'keywords' => array(
			'Keyword 1',
			'Keyword 2',
		),
		'style' => 'file:./build/style.css',
		'viewScript' => 'file:./build/view.js',
		'acf' => array(
			'blockVersion' => 3,
			'mode' => 'edit',
			'renderTemplate' => 'block.php',
		),
		'supports' => array(
			'align' => false,
			'mode' => true,
			'anchor' => true,
		),
		'example' => array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array(
					'preview_image_help' => true,
				),
			),
		),
	),
);
