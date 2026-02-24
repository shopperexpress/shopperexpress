<?php

/**
 * Register custom gutenberg blocks.
 *
 * @package ThemeName
 */

namespace App\Components\Gutenberg;

use App\Components\Theme_Component;

/**
 * Class Register_Gutenberg_Blocks
 *
 * @package App\Components\Gutenberg
 */
class Register_Gutenberg_Blocks implements Theme_Component
{

	/**
	 * ACF blocks folder
	 */
	const ACF_BLOCKS_FOLDER = '/template-parts/acf-blocks/';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void
	{
		if (function_exists('acf_register_block_type')) {
			add_action('acf/init', [$this, 'register_acf_block_types']);
		}
	}

	/**
	 * Get ACF blocks.
	 *
	 * @return array Blocks array
	 */
	private function get_blocks(): array
	{
		$acf_gutenberg_blocks = scandir(get_theme_file_path(self::ACF_BLOCKS_FOLDER));

		if ($acf_gutenberg_blocks) {
			$acf_gutenberg_blocks = array_diff($acf_gutenberg_blocks, ['.', '..']);
		}

		return ($acf_gutenberg_blocks) ? $acf_gutenberg_blocks : [];
	}

	/**
	 * Get block data.
	 *
	 * @param  string $path Path to the block file.
	 * @param  string $name Block file name.
	 * @return array
	 */
	private function get_block_data(string $path, string $name): array
	{
		$block_data = get_file_data($path, [
			'title'       => 'Title',
			'description' => 'Description',
			'keywords'    => 'Keywords',
			'category'    => 'Category',
			'icon'        => 'Icon',
			'mode'        => 'Mode',
		]);

		$block_data['title']       = ($block_data['title']) ?: $name;
		$block_data['description'] = ($block_data['description']) ?: $name;
		$block_data['category']    = ($block_data['category']) ?: 'custom-acf-blocks';
		$block_data['icon']        = ($block_data['icon']) ?: 'block-default';
		$block_data['mode']        = ($block_data['mode']) ?: 'auto';
		$block_data['keywords']    = ($block_data['keywords']) ? array_map('trim', explode(', ', $block_data['keywords'])) : [$block_data['title'], $block_data['description']];

		return $block_data;
	}

	/**
	 * ACF block render callback.
	 *
	 * @param array $block Block array.
	 * @return void
	 */
	public function acf_render_callback(array $block): void
	{
		$slug = str_replace('acf/', '', $block['name']);

		$file_path = get_theme_file_path(self::ACF_BLOCKS_FOLDER . $slug . '.php');
		if (file_exists($file_path)) {
			echo '<!-- START ' . esc_html($block['title']) . ' -->';
			include $file_path;
			echo '<!-- END ' . esc_html($block['title']) . ' -->';
		}
	}

	/**
	 * Register ACF blocks.
	 *
	 * @return void
	 */
	public function register_acf_block_types(): void
	{
		$blocks = array_filter($this->get_blocks(), function ($file) {
			return ! is_dir($file) ? ! is_dir($file) : $file;
		});

		array_map(function ($block) {
			$name = str_replace('.php', '', $block);
			$path = get_theme_file_path(self::ACF_BLOCKS_FOLDER . $block);

			$block_data = $this->get_block_data($path, $name);

			acf_register_block([
				'name'            => $name,
				'title'           => $block_data['title'],
				'description'     => $block_data['description'],
				'category'        => $block_data['category'],
				'icon'            => $block_data['icon'],
				'keywords'        => $block_data['keywords'],
				'mode'            => 'edit',
				'render_callback' => [$this, 'acf_render_callback'],
				'supports'        => [
					'align' => false,
					'mode'  => false,
				],
			]);
		}, $blocks);
	}
}
