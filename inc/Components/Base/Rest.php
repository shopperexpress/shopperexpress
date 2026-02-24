<?php

/**
 * WordPress Rest API.
 *
 * @package ThemeName
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Rest
 *
 * @package App\Base\Component
 */
class Rest implements Theme_Component
{

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('rest_api_init', function () {
            $methods = get_class_methods($this);

            foreach ($methods as $method) {
                if (strpos($method, 'rest_') === 0) {
                    $route = str_replace('_', '-', substr($method, 5));
                    register_rest_route(
                        'favorite/v1',
                        $route,
                        [
                            'methods'             => ['GET', 'POST'],
                            'callback'            => [$this, $method],
                            'permission_callback' => '__return_true',
                        ]
                    );
                }
            }
        });
    }

    public function rest_render(\WP_REST_Request $request)
    {
        $posts = $request->get_param('post_id');
        $post_type  = sanitize_key($request->get_param('action'));

        if (!$posts || !$post_type) {
            return new \WP_REST_Response(
                ['error' => 'Missing required parameters'],
                400
            );
        }
        $posts = explode(',', $posts);
        $posts = is_array($posts) ? $posts : [$posts];

        $allowed = [
            'lease-offers',
            'finance-offers',
            'conditional-offers',
            'used-listings',
            'listings',
            'offers'
        ];

        if (in_array($post_type, $allowed, true)) {
            ob_start();
            foreach ($posts as $post_id) {
                if ($post_type === get_post_type($post_id)) {
                    get_template_part(
                        'template-parts/content',
                        $post_type,
                        [
                            'post_id' => $post_id,
                            'action'  => 'favorite',
                        ]
                    );
                }
            }
            $html = ob_get_clean();

            return new \WP_REST_Response(
                [
                    'success' => true,
                    'html'    => $html,
                ],
                200
            );
        }

        return new \WP_REST_Response(
            ['error' => 'Invalid post type or not allowed'],
            400
        );
    }

    public function rest_favorite_count(\WP_REST_Request $request)
    {
        $user_id = intval($request->get_param('user')) ?? 0;

        $post_types = [
            'listings',
            'used-listings',
            'conditional-offers',
            'lease-offers',
            'finance-offers',
            'offers',
        ];

        $output = [];
        foreach ($post_types as $post_type) {
            $output[$post_type] = get_user_favorites_count(
                $user_id,
                null,
                ['post_type' => [$post_type]]
            );
        }

        return new \WP_REST_Response($output, 200);
    }
}
