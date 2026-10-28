<?php

declare(strict_types=1);

namespace {
    if (!class_exists('WP_Error', false)) {
        class WP_Error
        {
            public function __construct(
                private string $code = '',
                private string $message = '',
                private mixed $data = null
            ) {
            }

            public function get_error_code(): string
            {
                return $this->code;
            }

            public function get_error_message(): string
            {
                return $this->message;
            }
        }
    }

    if (!class_exists('WP_Post_Type', false)) {
        class WP_Post_Type
        {
            public function __construct(public string $name = '')
            {
            }
        }
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1): bool
        {
            $GLOBALS['wwp_test_actions'][$hook][] = compact('callback', 'priority', 'accepted_args');

            return true;
        }
    }

    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): bool
        {
            $GLOBALS['wwp_test_filters'][$hook][] = compact('callback', 'priority', 'accepted_args');

            return true;
        }
    }

    if (!function_exists('apply_filters')) {
        function apply_filters($hook, $value, ...$args)
        {
            if (empty($GLOBALS['wwp_test_filters'][$hook])) {
                return $value;
            }

            foreach ($GLOBALS['wwp_test_filters'][$hook] as $filter) {
                $value = ($filter['callback'])($value, ...$args);
            }

            return $value;
        }
    }

    if (!function_exists('sanitize_title')) {
        function sanitize_title(string $title): string
        {
            $title = strtolower(trim($title));
            $title = preg_replace('/[^a-z0-9\-_]+/', '-', $title) ?? $title;

            return trim($title, '-');
        }
    }

    if (!function_exists('register_post_type')) {
        function register_post_type($post_type, $args = [])
        {
            $GLOBALS['wwp_test_register_post_type_calls'][] = compact('post_type', 'args');

            if (!empty($GLOBALS['wwp_test_register_post_type_return'])) {
                return $GLOBALS['wwp_test_register_post_type_return'];
            }

            return new WP_Post_Type((string) $post_type);
        }
    }

    if (!function_exists('add_rewrite_rule')) {
        function add_rewrite_rule($regex, $query, $after = 'bottom'): void
        {
            $GLOBALS['wwp_test_rewrite_rules'][] = compact('regex', 'query', 'after');
        }
    }
}
