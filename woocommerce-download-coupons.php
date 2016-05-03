<?php

/**
 * Plugin Name: WooCommerce Download Coupons
 * Plugin URI: https://none
 * Description: Download woocommerce coupons
 * Author: Lucas Fialho Zawacki
 * Author URI: http://blog.lfzawacki.com/
 * Version: 0.1
 * License: AGPLv3 or later
 * Text Domain: woocommerce-download-coupons
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_action('init' , function (){

      class CSVExport extends WC_Coupon {

        public function __construct()
        {
          if(isset($_GET['download_report']))
          {
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"coupons.csv\";" );
            header("Content-Transfer-Encoding: binary");

            $this->generate_csv();

            exit;
          }

          // Add extra menu items for admins
          add_action('admin_menu', array($this, 'admin_menu'));

          // Create end-points
          add_filter('query_vars', array($this, 'query_vars'));
          add_action('parse_request', array($this, 'parse_request'));
        }

        /**
        * Add extra menu items for admins
        */
        public function admin_menu()
        {
          add_menu_page('Download Coupons', 'Download Coupons', 'manage_options', 'download_report', array($this, 'download_report'));
        }

        /**
        * Allow for custom query variables
        */
        public function query_vars($query_vars)
        {
          $query_vars[] = 'download_report';
          return $query_vars;
        }

        /**
        * Parse the request
        */
        public function parse_request(&$wp)
        {
          if(array_key_exists('download_report', $wp->query_vars))
          {
            $this->download_report();
            exit;
          }
        }

        /**
        * Download report
        */
        public function download_report()
        {
          echo '<div class="wrap">';
          echo '<div id="icon-tools" class="icon32">
          </div>';
          echo '<h2>Download dos cupons</h2>';

          echo '<p><a href="wp-admin/?download_report=true">Exportar os usuários</a> </p>';
        }

        /**
        * Converting data to CSV
        */
        public function generate_csv()
        {
            global $wpdb;

            $wpdb->show_errors();

            $MyQuery = $wpdb->get_results(
              $wpdb->prepare("SELECT post_title,post_excerpt FROM wp_posts p WHERE p.post_type = %s;", ['shop_coupon'])
            );

            if (! $MyQuery) {
              $Error = $wpdb->print_error();
              die("The following error was found: $Error");
            } else {

              $csv_fields = array();
              $csv_fields[] = 'Codigo';
              $csv_fields[] = 'Email';
              $csv_fields[] = 'Valor';
              $output_handle = @fopen( 'php://output', 'w' );

              fputcsv( $output_handle, $csv_fields );

              foreach ($MyQuery as $Result) {
                $leadArray = (array) $Result; // Cast the Object to an array

                $coupon = new WC_Coupon( $leadArray['post_title'] );

                if ( $coupon->usage_count == 0 ) {
                  preg_match('/.* \((.*)\) ([0-9]+)/', $leadArray['post_excerpt'], $matches);

                  $leadArray['post_excerpt'] = $matches[1];
                  $leadArray['value'] = $coupon->coupon_amount;

                  fputcsv( $output_handle, $leadArray );
                }

              }

              fclose( $output_handle );
            }

            exit();
        }

      }

      // Instantiate plugin
      $csvExport = new CSVExport();
  });
}
