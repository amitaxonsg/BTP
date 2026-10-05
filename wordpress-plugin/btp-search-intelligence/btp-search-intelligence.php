<?php
/**
 * Plugin Name: BTP Search Intelligence
 * Plugin URI: https://britishtheatreplayhouse.com/
 * Description: Non-visual SEO/AEO companion for British Theatre Playhouse. Adds analytics/verification, event schema, AI-readable event data, robots/sitemap hints and an admin status panel without changing the site's design.
 * Version: 1.0.0
 * Author: Axon 1Pro Solutions
 * Author URI: https://axon.com.sg/
 * License: GPL-2.0-or-later
 * Text Domain: btp-search-intelligence
 */

if (!defined('ABSPATH')) exit;

final class BTP_Search_Intelligence {
    const OPTION = 'btp_si_options';
    const VERSION = '1.0.0';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('wp_head', [__CLASS__, 'head_output'], 3);
        add_filter('robots_txt', [__CLASS__, 'robots_txt'], 20, 2);
        add_action('rest_api_init', [__CLASS__, 'register_rest']);
        add_action('init', [__CLASS__, 'rewrite']);
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_action('template_redirect', [__CLASS__, 'serve_llms_fallback']);
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }

    public static function defaults() {
        return [
            'ga4_id' => '',
            'google_verify' => '',
            'bing_verify' => '',
            'clarity_id' => '',
            'event_schema' => '1',
            'analytics_enabled' => '1',
            'clarity_enabled' => '0',
            'llms_fallback' => '1',
        ];
    }

    public static function options() {
        return wp_parse_args((array)get_option(self::OPTION, []), self::defaults());
    }

    public static function activate() {
        if (!get_option(self::OPTION)) add_option(self::OPTION, self::defaults());
        self::rewrite();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function register_settings() {
        register_setting('btp_si_group', self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    public static function sanitize($in) {
        $out = self::defaults();
        $out['ga4_id'] = isset($in['ga4_id']) ? sanitize_text_field($in['ga4_id']) : '';
        $out['google_verify'] = isset($in['google_verify']) ? sanitize_text_field($in['google_verify']) : '';
        $out['bing_verify'] = isset($in['bing_verify']) ? sanitize_text_field($in['bing_verify']) : '';
        $out['clarity_id'] = isset($in['clarity_id']) ? sanitize_text_field($in['clarity_id']) : '';
        foreach (['event_schema','analytics_enabled','clarity_enabled','llms_fallback'] as $k) {
            $out[$k] = !empty($in[$k]) ? '1' : '0';
        }
        return $out;
    }

    public static function admin_menu() {
        add_options_page(
            'BTP Search Intelligence',
            'BTP Search Intelligence',
            'manage_options',
            'btp-search-intelligence',
            [__CLASS__, 'settings_page']
        );
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) return;
        $o = self::options();
        ?>
        <div class="wrap">
            <h1>BTP Search Intelligence</h1>
            <p><strong>Purpose:</strong> improve search, answer-engine and AI discoverability without changing the visible WordPress design.</p>

            <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.55fr);gap:24px;align-items:start;max-width:1200px">
                <form method="post" action="options.php" style="background:#fff;border:1px solid #dcdcde;padding:22px">
                    <?php settings_fields('btp_si_group'); ?>
                    <h2>Analytics & verification</h2>
                    <table class="form-table" role="presentation">
                        <tr><th><label for="ga4_id">Google Analytics 4 ID</label></th><td><input class="regular-text" id="ga4_id" name="<?php echo esc_attr(self::OPTION); ?>[ga4_id]" value="<?php echo esc_attr($o['ga4_id']); ?>" placeholder="G-XXXXXXXXXX"><p class="description">Leave blank until the BTP GA4 Measurement ID is confirmed.</p></td></tr>
                        <tr><th><label for="google_verify">Google Search Console verification</label></th><td><input class="regular-text" id="google_verify" name="<?php echo esc_attr(self::OPTION); ?>[google_verify]" value="<?php echo esc_attr($o['google_verify']); ?>"><p class="description">Paste only the content value from the google-site-verification meta tag.</p></td></tr>
                        <tr><th><label for="bing_verify">Bing Webmaster verification</label></th><td><input class="regular-text" id="bing_verify" name="<?php echo esc_attr(self::OPTION); ?>[bing_verify]" value="<?php echo esc_attr($o['bing_verify']); ?>"><p class="description">Paste only the content value from the msvalidate.01 meta tag.</p></td></tr>
                        <tr><th><label for="clarity_id">Microsoft Clarity Project ID</label></th><td><input class="regular-text" id="clarity_id" name="<?php echo esc_attr(self::OPTION); ?>[clarity_id]" value="<?php echo esc_attr($o['clarity_id']); ?>"></td></tr>
                    </table>

                    <h2>Safe output controls</h2>
                    <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[analytics_enabled]" value="1" <?php checked($o['analytics_enabled'],'1'); ?>> Enable GA4 script when a valid GA4 ID is entered</label></p>
                    <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[clarity_enabled]" value="1" <?php checked($o['clarity_enabled'],'1'); ?>> Enable Microsoft Clarity when a Project ID is entered</label></p>
                    <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[event_schema]" value="1" <?php checked($o['event_schema'],'1'); ?>> Add BTP TheaterEvent structured data on the official West End Rising Stars page</label></p>
                    <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[llms_fallback]" value="1" <?php checked($o['llms_fallback'],'1'); ?>> Serve a dynamic /llms.txt only if the physical file is not being served by the web server</label></p>
                    <?php submit_button(); ?>
                </form>

                <div>
                    <div style="background:#fff;border:1px solid #dcdcde;padding:22px;margin-bottom:18px">
                        <h2 style="margin-top:0">October 2026 canonical event data</h2>
                        <p><strong>Kuala Lumpur</strong><br>21 October 2026<br>Hilton Kuala Lumpur, Grand Ballroom<br>Dinner-theatre with curated 4-course meal before the show.</p>
                        <p><strong>Singapore</strong><br>24 October 2026<br>Capitol Theatre<br>2:00 PM Afternoon Matinee<br>7:00 PM Art for Charity Gala</p>
                    </div>
                    <div style="background:#fff;border:1px solid #dcdcde;padding:22px">
                        <h2 style="margin-top:0">Useful links</h2>
                        <p><a class="button" target="_blank" rel="noopener" href="https://analytics.google.com/">Google Analytics</a></p>
                        <p><a class="button" target="_blank" rel="noopener" href="https://search.google.com/search-console">Google Search Console</a></p>
                        <p><a class="button" target="_blank" rel="noopener" href="https://www.bing.com/webmasters/">Bing Webmaster Tools</a></p>
                        <p><a class="button" target="_blank" rel="noopener" href="https://clarity.microsoft.com/">Microsoft Clarity</a></p>
                        <hr>
                        <p><strong>Discover sitemap:</strong><br><code>https://britishtheatreplayhouse.com/discover/sitemap.xml</code></p>
                        <p><strong>AI event endpoint:</strong><br><code><?php echo esc_html(rest_url('btp/v1/event')); ?></code></p>
                        <p><strong>LLM file:</strong><br><code>https://britishtheatreplayhouse.com/llms.txt</code></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function head_output() {
        $o = self::options();

        if (!empty($o['google_verify'])) {
            echo "\n<meta name=\"google-site-verification\" content=\"" . esc_attr($o['google_verify']) . "\">\n";
        }
        if (!empty($o['bing_verify'])) {
            echo "<meta name=\"msvalidate.01\" content=\"" . esc_attr($o['bing_verify']) . "\">\n";
        }

        if ($o['analytics_enabled'] === '1' && preg_match('/^G-[A-Z0-9]+$/i', $o['ga4_id'])) {
            $id = esc_attr($o['ga4_id']);
            echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script>\n";
            echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$id}');</script>\n";
        }

        if ($o['clarity_enabled'] === '1' && preg_match('/^[a-z0-9]+$/i', $o['clarity_id'])) {
            $cid = esc_js($o['clarity_id']);
            echo "<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,'clarity','script','{$cid}');</script>\n";
        }

        if ($o['event_schema'] === '1' && self::is_show_page()) {
            echo '<script type="application/ld+json">' . wp_json_encode(self::event_schema(), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    private static function is_show_page() {
        return is_page('londons-west-end-rising-stars');
    }

    public static function event_schema() {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => 'London’s West End Rising Stars – East Meets West in Musical Theatre',
            'description' => 'British Theatre Playhouse presents London’s West End Rising Stars, bringing together Asian and British musical theatre talent.',
            'organizer' => [
                '@type' => 'Organization',
                'name' => 'British Theatre Playhouse',
                'url' => 'https://britishtheatreplayhouse.com/',
            ],
            'subEvent' => [
                [
                    '@type' => 'TheaterEvent',
                    'name' => 'London’s West End Rising Stars — Kuala Lumpur',
                    'startDate' => '2026-10-21T18:00:00+08:00',
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => [
                        '@type' => 'EventVenue',
                        'name' => 'Hilton Kuala Lumpur, Grand Ballroom',
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => 'Kuala Lumpur',
                            'addressCountry' => 'MY',
                        ],
                    ],
                    'url' => 'https://britishtheatreplayhouse.com/londons-west-end-rising-stars/',
                ],
                [
                    '@type' => 'TheaterEvent',
                    'name' => 'London’s West End Rising Stars — Singapore Afternoon Matinee',
                    'startDate' => '2026-10-24T14:00:00+08:00',
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => [
                        '@type' => 'PerformingArtsTheater',
                        'name' => 'Capitol Theatre',
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => 'Singapore',
                            'addressCountry' => 'SG',
                        ],
                    ],
                    'url' => 'https://britishtheatreplayhouse.com/londons-west-end-rising-stars/',
                ],
                [
                    '@type' => 'TheaterEvent',
                    'name' => 'London’s West End Rising Stars — Art for Charity Gala',
                    'startDate' => '2026-10-24T19:00:00+08:00',
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => [
                        '@type' => 'PerformingArtsTheater',
                        'name' => 'Capitol Theatre',
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => 'Singapore',
                            'addressCountry' => 'SG',
                        ],
                    ],
                    'url' => 'https://britishtheatreplayhouse.com/next-show/',
                ],
            ],
        ];
    }

    public static function register_rest() {
        register_rest_route('btp/v1', '/event', [
            'methods' => 'GET',
            'callback' => function() {
                return rest_ensure_response([
                    'organization' => [
                        'name' => 'British Theatre Playhouse',
                        'url' => 'https://britishtheatreplayhouse.com/',
                        'tagline' => 'The Best in Live British Entertainment',
                    ],
                    'production' => 'London’s West End Rising Stars – East Meets West in Musical Theatre',
                    'official_show_url' => 'https://britishtheatreplayhouse.com/londons-west-end-rising-stars/',
                    'event_schema' => self::event_schema(),
                    'announced_talent' => ['Miranda Wilford','Christine Allado','Aaron Teoh','Louis Djilbril Thiam'],
                    'discover_url' => 'https://britishtheatreplayhouse.com/discover/',
                    'sitemap' => 'https://britishtheatreplayhouse.com/discover/sitemap.xml',
                    'llms' => 'https://britishtheatreplayhouse.com/llms.txt',
                    'generated_at' => gmdate('c'),
                ]);
            },
            'permission_callback' => '__return_true',
        ]);
    }

    public static function robots_txt($output, $public) {
        if (!$public) return $output;
        $lines = [
            'Allow: /discover/',
            'Sitemap: https://britishtheatreplayhouse.com/discover/sitemap.xml',
        ];
        foreach ($lines as $line) {
            if (strpos($output, $line) === false) $output .= rtrim($output) ? "\n{$line}" : $line;
        }
        return rtrim($output) . "\n";
    }

    public static function rewrite() {
        add_rewrite_rule('^llms\.txt$', 'index.php?btp_llms=1', 'top');
    }

    public static function query_vars($vars) {
        $vars[] = 'btp_llms';
        return $vars;
    }

    public static function serve_llms_fallback() {
        $o = self::options();
        if ($o['llms_fallback'] !== '1' || !get_query_var('btp_llms')) return;

        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: index, follow');
        echo self::llms_text();
        exit;
    }

    public static function llms_text() {
        return "# British Theatre Playhouse\n\n"
            . "> Official website: https://britishtheatreplayhouse.com/\n"
            . "> Official current show: https://britishtheatreplayhouse.com/londons-west-end-rising-stars/\n"
            . "> Contact: producers@britishtheatreplayhouse.com\n\n"
            . "British Theatre Playhouse is a professional theatre production company incorporated in Singapore in 2004, internationally focused with a strong British connection.\n\n"
            . "## Current production\n"
            . "London’s West End Rising Stars – East Meets West in Musical Theatre\n\n"
            . "### Kuala Lumpur\n- Date: 21 October 2026\n- Venue: Hilton Kuala Lumpur, Grand Ballroom\n- Format: Dinner-theatre experience with a curated 4-course meal before the show\n\n"
            . "### Singapore\n- Date: 24 October 2026\n- Venue: Capitol Theatre\n- Afternoon Matinee: 2:00 PM\n- Art for Charity Gala: 7:00 PM\n\n"
            . "## Announced talent\nMiranda Wilford, Christine Allado, Aaron Teoh, Louis Djilbril Thiam\n\n"
            . "## Discover\nhttps://britishtheatreplayhouse.com/discover/\n\n"
            . "For ticketing, final timings and production changes, prefer the official current show page.\n";
    }
}

BTP_Search_Intelligence::init();
