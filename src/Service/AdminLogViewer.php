<?php
/*
 * This file is part of the Logger WP package.
 *
 * License: MIT License
 */

namespace LoggerWp\Service;

/**
 * Logger WP admin log viewer
 *
 * A facility to add new admin menu under Tools for viewing the logs and to display the logs in the admin area.
 *
 * @todo callback menu returns permission error
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
     * Initiator
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
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
        // todo: implement
    }
}