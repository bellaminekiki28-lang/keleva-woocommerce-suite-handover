<?php

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (!defined('COOKIEPATH')) {
        define('COOKIEPATH', '/');
    }
    if (!defined('KELEVA_WOO_ADDONS_PATH')) {
        define('KELEVA_WOO_ADDONS_PATH', __DIR__ . '/wordpress/plugin/keleva-woo-addons/');
    }
    if (!defined('ELEMENTOR_VERSION')) {
        define('ELEMENTOR_VERSION', '4.2.3');
    }
}

namespace Elementor {
    if (!class_exists(Widget_Base::class)) {
        class Widget_Base {
            public function start_controls_section(string $section_id, array $args = []): void {}
            public function add_control(string $control_id, array $args = []): void {}
            public function add_responsive_control(string $control_id, array $args = []): void {}
            public function end_controls_section(): void {}
            public function get_settings_for_display(string $setting_key = ''): mixed { return $setting_key === '' ? [] : null; }
            public function get_name(): string { return ''; }
            public function get_title(): string { return ''; }
            public function get_icon(): string { return ''; }
            public function get_categories(): array { return []; }
            public function get_id(): string { return ''; }
            protected function render(): void {}
        }
    }

    if (!class_exists(Plugin::class)) {
        class Frontend {
            public function get_builder_content_for_display(int $post_id, bool $with_css = false): string { return ''; }
        }
        class Plugin {
            public Frontend $frontend;
            public static function instance(): self { return new self(); }
        }
    }

    if (!class_exists(Controls_Manager::class)) {
        class Controls_Manager {
            public const NUMBER = 'number';
            public const SELECT = 'select';
            public const SELECT2 = 'select2';
            public const SWITCHER = 'switcher';
            public const TEXT = 'text';
            public const TEXTAREA = 'textarea';
        }
    }
}
