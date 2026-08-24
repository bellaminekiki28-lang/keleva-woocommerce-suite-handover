<?php
defined('ABSPATH') || exit;

final class Keleva_Dashboard_Audit_Log {
    private const DB_VERSION = '1.0.0';
    private const VERSION_OPTION = 'keleva_dashboard_audit_db_version';
    private const RETENTION_DAYS = 365;

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'keleva_dashboard_audit';
    }

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::table();
        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event varchar(100) NOT NULL,
            actor varchar(191) NOT NULL,
            context longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_created_at (event, created_at),
            KEY created_at (created_at)
        ) {$charset_collate};");
        update_option(self::VERSION_OPTION, self::DB_VERSION, false);
    }

    public static function maybe_install(): void {
        if (get_option(self::VERSION_OPTION) !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function record(string $event, array $context, string $actor = 'merchant-token'): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table d’audit dédiée, écriture immédiate nécessaire pour la traçabilité.
        $wpdb->insert(self::table(), [
            'event' => sanitize_key($event),
            'actor' => sanitize_text_field($actor),
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql', true),
        ], ['%s', '%s', '%s', '%s']);
        self::prune();
    }

    public static function recent(int $limit = 50): array {
        global $wpdb;
        $safe_limit = min(100, max(1, $limit));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- lecture fraîche du journal d’audit demandé par le dashboard.
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT id, event, actor, context, created_at FROM %i ORDER BY id DESC LIMIT %d',
            self::table(),
            $safe_limit
        ), ARRAY_A);
        return array_map(static function (array $row): array {
            $context = json_decode((string) $row['context'], true);
            return [
                'id' => (int) $row['id'],
                'event' => $row['event'],
                'actor' => $row['actor'],
                'at' => $row['created_at'],
                'context' => is_array($context) ? $context : [],
            ];
        }, is_array($rows) ? $rows : []);
    }

    private static function prune(): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- purge contrôlée de la table d’audit dédiée.
        $wpdb->query($wpdb->prepare(
            'DELETE FROM %i WHERE created_at < %s',
            self::table(),
            gmdate('Y-m-d H:i:s', time() - (self::RETENTION_DAYS * DAY_IN_SECONDS))
        ));
    }
}
