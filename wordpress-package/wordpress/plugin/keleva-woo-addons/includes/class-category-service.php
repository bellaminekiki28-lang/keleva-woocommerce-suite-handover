<?php
defined('ABSPATH') || exit;

/**
 * Couche de domaine des catégories marchand : aucune dépendance wp-admin dans la console publique.
 */
final class Keleva_Category_Service {
    private const META_VISIBLE = '_keleva_category_visible';
    private const META_ORDER = '_keleva_category_order';
    private const META_COVER_ID = '_keleva_category_cover_id';
    private const META_OPTIONS = '_keleva_category_option_templates';
    private const PRODUCT_SOURCE = '_keleva_options_source';
    private const PRODUCT_CATEGORY = '_keleva_options_category_id';

    public static function list(): array {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            return [];
        }
        usort($terms, static function (WP_Term $a, WP_Term $b): int {
            $order = (int) get_term_meta($a->term_id, self::META_ORDER, true) <=> (int) get_term_meta($b->term_id, self::META_ORDER, true);
            return $order ?: strnatcasecmp($a->name, $b->name);
        });
        return array_map([self::class, 'payload'], $terms);
    }

    public static function find(int $term_id): ?WP_Term {
        $term = get_term($term_id, 'product_cat');
        return $term instanceof WP_Term ? $term : null;
    }

    public static function payload(WP_Term $term): array {
        $cover_id = absint(get_term_meta($term->term_id, self::META_COVER_ID, true));
        $templates = self::option_templates($term->term_id);
        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'visible' => 'no' !== get_term_meta($term->term_id, self::META_VISIBLE, true),
            'order' => (int) get_term_meta($term->term_id, self::META_ORDER, true),
            'count' => (int) $term->count,
            'cover' => $cover_id ? [
                'id' => $cover_id,
                'url' => wp_get_attachment_image_url($cover_id, 'medium') ?: wp_get_attachment_url($cover_id),
            ] : null,
            'option_templates' => $templates,
            'options_usage_count' => self::options_usage_count($term->term_id),
        ];
    }

    public static function option_templates(int $term_id): array {
        $raw = get_term_meta($term_id, self::META_OPTIONS, true);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        return class_exists('Keleva_Product_Options') ? Keleva_Product_Options::normalize_groups($decoded) : [];
    }

    public static function template_for_product(WC_Product $product): array {
        $category_ids = $product->is_type('variation') ? wc_get_product($product->get_parent_id())?->get_category_ids() : $product->get_category_ids();
        foreach ((array) $category_ids as $category_id) {
            $groups = self::option_templates((int) $category_id);
            if ($groups) {
                return ['category_id' => (int) $category_id, 'groups' => $groups];
            }
        }
        return ['category_id' => 0, 'groups' => []];
    }

    public static function source_for_product(WC_Product $product): array {
        $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $source = (string) get_post_meta($product_id, self::PRODUCT_SOURCE, true);
        if ('custom' === $source) {
            return ['source' => 'custom', 'category_id' => absint(get_post_meta($product_id, self::PRODUCT_CATEGORY, true))];
        }
        $template = self::template_for_product($product);
        return ['source' => $template['groups'] ? 'category' : 'none', 'category_id' => $template['category_id']];
    }

    public static function inherit_template(WC_Product $product): void {
        $template = self::template_for_product($product);
        if ($template['groups']) {
            delete_post_meta($product->get_id(), '_keleva_product_option_groups');
            update_post_meta($product->get_id(), self::PRODUCT_SOURCE, 'category');
            update_post_meta($product->get_id(), self::PRODUCT_CATEGORY, $template['category_id']);
        } else {
            delete_post_meta($product->get_id(), self::PRODUCT_SOURCE);
            delete_post_meta($product->get_id(), self::PRODUCT_CATEGORY);
        }
    }

    public static function mark_custom(WC_Product $product): void {
        update_post_meta($product->get_id(), self::PRODUCT_SOURCE, 'custom');
        update_post_meta($product->get_id(), self::PRODUCT_CATEGORY, 0);
    }

    private static function options_usage_count(int $term_id): int {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- recherche ciblée sur une clé d’index métier pour éviter la suppression d’un modèle encore utilisé.
        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- requête de protection ciblée sur la clé métier.
            'meta_query' => [[
                'key' => self::PRODUCT_CATEGORY,
                'value' => $term_id,
                'compare' => '=',
            ]],
        ]);
        return (int) $query->found_posts;
    }

    public static function create(array $input): WP_Term|WP_Error {
        $created = wp_insert_term($input['name'], 'product_cat', [
            'slug' => $input['slug'] ?: null,
            'description' => $input['description'],
        ]);
        if (is_wp_error($created)) {
            return $created;
        }
        $term = self::find((int) $created['term_id']);
        if (!$term) {
            return new WP_Error('keleva_category_create_failed', __('La catégorie ne peut pas être créée.', 'keleva-woo-addons'));
        }
        self::save($term, $input);
        return self::find($term->term_id) ?: $term;
    }

    public static function save(WP_Term $term, array $input): WP_Term|WP_Error {
        $update = [];
        foreach (['name', 'slug', 'description'] as $field) {
            if (array_key_exists($field, $input)) {
                $update[$field] = $input[$field];
            }
        }
        if ($update) {
            $result = wp_update_term($term->term_id, 'product_cat', $update);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        if (array_key_exists('visible', $input)) {
            update_term_meta($term->term_id, self::META_VISIBLE, $input['visible'] ? 'yes' : 'no');
        }
        if (array_key_exists('order', $input)) {
            update_term_meta($term->term_id, self::META_ORDER, max(0, (int) $input['order']));
        }
        if (array_key_exists('cover_id', $input)) {
            $cover_id = absint($input['cover_id']);
            if ($cover_id && !wp_attachment_is_image($cover_id)) {
                return new WP_Error('keleva_category_cover_invalid', __('La couverture doit être une image de la médiathèque.', 'keleva-woo-addons'), ['status' => 422]);
            }
            update_term_meta($term->term_id, self::META_COVER_ID, $cover_id);
        }
        if (array_key_exists('option_templates', $input)) {
            $groups = Keleva_Product_Options::normalize_groups($input['option_templates']);
            update_term_meta($term->term_id, self::META_OPTIONS, wp_slash(wp_json_encode($groups)));
        }
        return self::find($term->term_id) ?: $term;
    }

    public static function move_products(WP_Term $term, array $product_ids, string $mode = 'replace'): array {
        $moved = [];
        foreach (array_slice(array_unique(array_map('absint', $product_ids)), 0, 100) as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product || $product->is_type('variation')) {
                continue;
            }
            $categories = 'append' === $mode ? array_unique(array_merge($product->get_category_ids(), [$term->term_id])) : [$term->term_id];
            $product->set_category_ids($categories);
            $product->save();
            if ('custom' !== (string) get_post_meta($product->get_id(), self::PRODUCT_SOURCE, true)) {
                self::inherit_template($product);
            }
            $moved[] = $product->get_id();
        }
        return $moved;
    }
}
