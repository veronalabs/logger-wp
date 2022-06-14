<?php
/*
 * This file is part of the LoggerWP package.
 *
 * License: MIT License
 */

namespace LoggerWp\Service;

use LoggerWp\Utils;

/**
 * LoggerWP admin log viewer
 *
 * A facility to add new admin menu under Tools for viewing the logs and to display the logs in the admin area.
 *
 */
class AdminLogViewer
{
    /**
     * Instance
     *
     * @access private
     * @var object Class object.
     */
    private static $instance;

    /**
     * Logger config
     *
     * @var array
     */
    private $config;

    /**
     * Initiator
     */
    public function __construct()
    {
        if (WP_DEBUG) {
            add_action('admin_menu', [$this, 'registerMenu']);
        }
    }

    /**
     * Initiator
     *
     * @return object initialized object of class.
     * @since 1.0.0
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set config
     *
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Handle actions
     *
     * @return void
     */
    public function handleActions()
    {
        if (isset($_GET['page']) && $_GET['page'] == 'logger-wp' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['log'])) {
            $log = sanitize_text_field($_GET['log']);

            if (file_exists(Utils::getLogDirectory($this->config['dir_name']) . '/' . $log)) {
                unlink(Utils::getLogDirectory($this->config['dir_name']) . '/' . $log);
                wp_redirect(admin_url('tools.php?page=logger-wp'));
            }
        }
    }

    public function registerMenu()
    {
        add_submenu_page(
            'tools.php',
            'Logs',
            'Logs',
            'manage_options',
            'logger-wp',
            [$this, 'renderLogViewer']
        );
    }

    public function renderLogViewer()
    {
        $logs       = $this->getLogFiles();
        $viewed_log = current($logs);

        if (isset($_GET['log_file'])) {
            $viewed_log = sanitize_text_field($_GET['log_file']);
        }
        ?>
        <div class="wrap"><h1>Logs</h1>
            <hr/>
            <style>
                #log-viewer-select {
                    padding: 10px 0 8px;
                    line-height: 28px;
                }

                #log-viewer {
                    background: #fff;
                    border: 1px solid #e5e5e5;
                    box-shadow: 0 1px 1px rgb(0 0 0 / 4%);
                    padding: 5px 20px;
                }

                #log-viewer pre {
                    font-family: monospace;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }

                div#log-clipboard {
                    padding: 18px 0;
                }
            </style>
            <script>
                function copyToClipboard() {
                    var text = document.getElementById('log-viewer').innerText;
                    navigator.clipboard.writeText(text);
                }
            </script>
            <?php if ($logs) : ?>
                <div id="log-viewer-select">
                    <div class="alignleft">
                        <h2>
                            <?php echo esc_html($viewed_log); ?>
                            <?php if (!empty($viewed_log)) : ?>
                                <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'delete', 'log' => $viewed_log], admin_url('tools.php?page=logger-wp')), 'remove_log')); ?>" class="button"><?php esc_html_e('Delete log'); ?></a>
                            <?php endif; ?>
                        </h2>
                    </div>
                    <div class="alignright">
                        <form action="<?php echo esc_url(admin_url('tools.php')); ?>" method="get">
                            <input type="hidden" name="page" value="logger-wp">
                            <select name="log_file">
                                <?php foreach ($logs as $log_key => $log_file) : ?><?php
                                    $timestamp = filemtime(Utils::getLogDirectory($this->config['dir_name']) . '/' . $log_file);
                                    $date      = sprintf(
                                        __('%1$s at %2$s %3$s'),
                                        wp_date(get_option('date_format'), $timestamp),
                                        wp_date(get_option('time_format'), $timestamp),
                                        wp_date('T', $timestamp)
                                    );
                                    ?>
                                    <option value="<?php echo esc_attr($log_key); ?>" <?php selected($viewed_log, $log_key); ?>><?php echo esc_html($log_file); ?> (<?php echo esc_html($date); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="button" value="<?php esc_attr_e('View'); ?>"><?php esc_html_e('View'); ?></button>
                        </form>
                    </div>
                    <div class="clear"></div>
                </div>
                <div id="log-viewer">
                    <pre><?php echo esc_html(file_get_contents(Utils::getLogDirectory($this->config['dir_name']) . '/' . $viewed_log)); ?></pre>
                </div>

                <div id="log-clipboard">
                    <button type="button" class="button" onclick="copyToClipboard()">Copy site info to clipboard</button>
                </div>
            <?php else : ?>
                <div class="updated inline"><p><?php esc_html_e('There are currently no logs to view.'); ?></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function getLogFiles()
    {
        $logPath = Utils::getLogDirectory($this->config['dir_name']);
        $files   = @scandir($logPath);
        $result  = array();

        if (!empty($files)) {
            foreach ($files as $key => $value) {
                if (!in_array($value, array('.', '..'), true)) {
                    if (!is_dir($value) && strstr($value, '.log')) {
                        $result[$value] = $value;
                    }
                }
            }
        }

        return $result;
    }
}