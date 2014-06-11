<?php
/*
Plugin Name: WooCommerce View
Plugin URI: http://tandukar.com//woocommerce-view/
Description: Adds a grid/list/date/map view toggle to product archives
Version: 1.0
Author: Rajesh, Alchemist
Author URI: http://tandukar.com
Requires at least: 3.1
Tested up to: 3.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc_list_grid_date_map
Domain Path: /languages/
*/


/*  Copyright YEAR  Rajesh  (email : rtandukar@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    load_plugin_textdomain('woo_view', false, dirname(plugin_basename(__FILE__)) . '/languages/');

if (!class_exists('WC_Woo_View')) {

class WC_Woo_View {

    var $_addresses = array();
    var $_kml = array();
    var $_latArray = array();
    var $_lngArray = array();
    var $_increment = 0;
    var $_checkinc = 0;
    var $_lat = null;
    var $_lng = null;
    var $_showMap = null;
    var $_checkaddresses = array();


    public function __construct()
    {
        // Hooks
        add_action('wp', array($this, 'setup_view'), 20);

        $this->_showMap = false;
        $this->_kml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->_kml[] = '<kml xmlns="http://earth.google.com/kml/2.0">';
        $this->_kml[] = '<Document>';

        add_action('add_meta_boxes', array($this, 'woo_view_add_custom_box'));
        add_action('woocommerce_process_product_meta', array(&$this, 'woo_view_process_meta_box'), 1, 2);
        add_action('admin_enqueue_scripts', array($this, 'load_admin_js'));

    }
    public function load_admin_js($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            $woo_view_options = get_post_meta($post->ID, '_woo_view_meta_options', true);

            if ('product' == $post->post_type) {
                wp_enqueue_script('googlemapapi', 'http://maps.google.com/maps/api/js?sensor=false');
                wp_enqueue_script('woo_admin_js', plugins_url('/assets/js/woo_view_map.js', __FILE__), array('jquery'));
                wp_enqueue_style('woo_view_css', plugins_url('/assets/css/woo_view.css', __FILE__));

                wp_localize_script('woo_admin_js', 'woo_admin_js', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'map_api' => '',
                    'glat' => !empty($woo_view_options['woo_view_lat']) ? $woo_view_options['woo_view_lat'] : -27.4710107,
                    'glng' => !empty($woo_view_options['woo_view_lng']) ? $woo_view_options['woo_view_lng'] : 153.02344889999995
                ));
            }
        }
    }

    function woo_view_process_meta_box($post_id, $post)
    {
        global $woocommerce_errors;
        if (isset($_POST['woo_view_location'])) {

            $woo_view_meta_options = array(
                'woo_view_location' => isset($_POST['woo_view_location']) ? $_POST['woo_view_location'] : '',
                'woo_view_lat' => isset($_POST['woo_view_lat']) ? $_POST['woo_view_lat'] : '',
                'woo_view_lng' => isset($_POST['woo_view_lng']) ? $_POST['woo_view_lng'] : ''

            );
            update_post_meta($post_id, '_woo_view_meta_options', $woo_view_meta_options);
        } else {
            delete_post_meta($post_id, '_woo_view_meta_options');
        }
    }


    public function woo_view_add_custom_box()
    {
        global $post;
        add_meta_box('woocommerce-woo-view-form-meta', __('Map View', 'woo_view'), array(&$this, 'woo_view_custom_box'), 'product', 'normal', 'default');

    }

    public function woo_view_custom_box($post)
    {
        ?>

        <div class="panel-wrap product_data woocommerce">
        <ul class="tabs wc-tabs">
            <li class="active"><a href="#woo_view_metabox_data"><?php echo 'General'; ?></a></li>
        </ul>

        <div id="woo_view_metabox_data" class="panel woocommerce_options_panel">
            <?php
            $woo_view_meta_options = get_post_meta($post->ID, '_woo_view_meta_options', true);
            ?>
            <div class="options_group">
                <table id="woo_view_tbl">
                    <tr>
                        <td>
                            <?php

                            woocommerce_wp_text_input(array(
                                    'name' => 'woo_view_location',
                                    'id' => 'woo_view_location',
                                    'label' => __('Location', 'woo_view'),
                                    'custom_attributes' => array('style' => "width:75%"),
                                    'description' => __("Enter the address for the product and click on locate button.",'woo_view'),
                                    'desc_tip' => true,
                                    'value' => isset($woo_view_meta_options['woo_view_location']) && $woo_view_meta_options['woo_view_location'] ? $woo_view_meta_options['woo_view_location'] : '')
                            );
                            ?>
                        </td>
                        <td>
                            <?php
                            echo '<input type="button" value="' . __('Locate', 'woo_view') . '" id="agmap_add_button" style="width:auto;"/>';
                            echo '<span id="locate_my_add" style="padding :20px;"><a href="#" id="agmap_geoloc">' . __(' Or Locate My Local Address', 'woo_view') . '</a></span>';

                            woocommerce_wp_hidden_input(array(
                                    'name' => 'woo_view_lat',
                                    'id' => 'woo_view_lat',
                                    'value' => isset($woo_view_meta_options['woo_view_lat']) && $woo_view_meta_options['woo_view_lat'] ? $woo_view_meta_options['woo_view_lat'] : '')
                            );

                            woocommerce_wp_hidden_input(array(
                                    'name' => 'woo_view_lng',
                                    'id' => 'woo_view_lng',
                                    'value' => isset($woo_view_meta_options['woo_view_lng']) && $woo_view_meta_options['woo_view_lng'] ? $woo_view_meta_options['woo_view_lng'] : '')
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div align="center" class="woo_view_admin_canvas_wrapper">
                                <div id="woo_view_map_canvas"
                                     style="width:670px; height: 400px; border: 1px solid #E0E0DD;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        </div><?php
    }

    public function setup_view()
    {

        add_action('wp_enqueue_scripts', array($this, 'setup_scripts_styles'), 20);
        add_action('wp_enqueue_scripts', array($this, 'setup_scripts_script'), 20);
        add_action('woocommerce_before_shop_loop', array($this, 'woo_view_nav'), 30);

        $view = isset($_GET['view'])? $_GET['view']:'';
        if ($view == 'date') {

            add_action('woocommerce_after_shop_loop_item', array($this, 'woo_view_date_view_open'), 9);
            add_action('woocommerce_after_shop_loop_item', array($this, 'woo_view_date_view_close'), 11);
        }

        if ($view == 'map') {
            add_action('woocommerce_before_shop_loop', array($this, 'woo_map_block'), 31);
            add_action('woocommerce_after_shop_loop_item', array($this, 'woo_grid_buttonwrap_open'), 9);
            add_action('woocommerce_after_shop_loop_item', array($this, 'woo_grid_buttonwrap_close'), 11);
            add_action('woocommerce_after_shop_loop', array($this, 'woo_map_block_close'), 31);
        }

        add_action('wp_footer', array(&$this, 'woo_view_set_view'));
    }

    public function woo_view_nav()
    {
        $type = array('list' =>'','map'=>'','grid' =>'','date'=>'');
        $view = isset($_GET['view'])? $_GET['view']:'list';
        $type[$view] = 'selected';
        echo '<div class="wc_woo_view_wrapper">
                        <div id="wc_woo_view_tabs">
                        <form id="woo_view_form" method="get">
                        <ul id="woo_view_nav_ul">';

        echo '<li><a href="#" class="'.$type['list'].'" id="list" title="' . __('List View', 'woo_view') . '"><span class="'.$type['list'].'"><em class="list"></em>List View</span></a></li>';
        echo '<li><a href="#" class="'.$type['date'].'" id="date" title="' . __('Date View', 'woo_view') . '"><span class="'.$type['date'].'"><em class="date"></em>Date View</span></a></li>';
        echo '<li><a href="#" class="'.$type['grid'].'" id="grid" title="' . __('Grid View', 'woo_view') . '"><span class="'.$type['grid'].'"><em class="grid"></em>Grid View</span></a></li>';
        echo '<li><a href="#" class="'.$type['map'].'" id="map" title="' . __('Map View', 'woo_view') . '"><span class="'.$type['map'].'"><em class="map"></em>Map View</span></a></li>';
        echo '</ul>';

        foreach ($_GET as $key => $val) {
            if ('view' == $key)
                continue;

            if (is_array($val)) {
                foreach ($val as $innerVal) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($innerVal) . '" />';
                }

            } else {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" />';
            }
        }
        echo '<input type="hidden" name="view" id="woo_view_view" value="" />';
        echo '</form>
                        </div>
                        </div>';
    }

    public function woo_map_block()
    {
        echo '<div class="wc_woo_view_content_wrapper">';
        echo '<div class="wc_woo_view_mapview">';
        echo '<div id="wc_woo_view_scroller" class="wc_scroll_height wc_woo_scroller">';

    }


    public function woo_map_block_close()
    {
        echo '</div>';
        echo '<div id="wc_woo_canvas_wrapper"><div id="map-canvas"  class="wc_scroll_height"  style="width:400px;"></div></div>';
        echo '</div>';
        echo '<div class="wc_woo_clear"></div>';
        echo '</div>';

        $this->_kml[] = '</Document>';
        $this->_kml[] = '</kml>';

    }

    public function woo_grid_buttonwrap_open()
    {
        global $post;
        echo '<div class="gridlist-buttonwrap">';
    }

    public function woo_grid_buttonwrap_close()
    {
        echo '</div>';

        global $post;

        $woo_view_meta_options = get_post_meta($post->ID, '_woo_view_meta_options', true);

        $lat = $woo_view_meta_options['woo_view_lat'];
        $lng = $woo_view_meta_options['woo_view_lng'];

        $this->_checkaddresses[$this->_checkinc] = (!empty($lat) && !empty($lng)) ? 1 : 0;
        $this->_checkinc++;

        if (!empty($lat) && !empty($lng)) {
            $this->_addresses[$this->_increment][] = (!empty($lat) && !empty($lng)) ? 1 : 0;
            $this->_addresses[$this->_increment][] = '<div><a href=\"' . get_permalink($post->ID) . '\">' . $post->post_title . '</a></div>';
            $this->_addresses[$this->_increment][] = $lat;
            $this->_addresses[$this->_increment][] = $lng;
            $this->_increment++;
            $this->_showMap = true;
            $this->_latArray[] = $lat;
            $this->_lngArray[] = $lng;
            if ($this->_lat == null) $this->_lat = $lat;
            if ($this->_lng == null) $this->_lng = $lng;

            $this->_kml[] = '<Placemark>';
            $this->_kml[] = '<name></name>';
            $this->_kml[] = '<description><![CDATA[' . $post->post_title . ']]></description>';
            $this->_kml[] = '<Style>';
            $this->_kml[] = '<IconStyle>';
            $this->_kml[] = '<Icon>';
            $this->_kml[] = '<href>http://googlemaps.googlermania.com/google_maps_api_v3/en/Google_Maps_Marker.png</href>';
            $this->_kml[] = '<href></href>';
            $this->_kml[] = '</Icon>';
            $this->_kml[] = '</IconStyle>';
            $this->_kml[] = '</Style>';
            $this->_kml[] = '<Point>';
            $this->_kml[] = '<coordinates>' . $lng . ',' . $lat . ',0</coordinates>';
            $this->_kml[] = '</Point>';
            $this->_kml[] = '</Placemark>';
        }
    }

    public function woo_view_date_view_open()
    {
        global $post;
        echo '<div class="date_view_wrapper" itemid="' . date('Y-m-d', strtotime($post->post_date)) . '" rel="' . mysql2date('j M Y', date('Y-m-d', strtotime($post->post_date))) . '">';
    }

    public function woo_view_date_view_close()
    {
        echo '</div>';
    }

    public function setup_scripts_styles()
    {
        wp_enqueue_style('grid-list-layout', plugins_url('/assets/css/woo_view.css', __FILE__));
    }

    public function setup_scripts_script()
    {
        wp_enqueue_script('jquery');
        $view = isset($_GET['view'])? $_GET['view']:'';
        if ($view == 'map') {
            wp_enqueue_script('googlemapapi', 'http://maps.google.com/maps/api/js?sensor=false');
            wp_enqueue_script('woo-geoxml', plugins_url('/assets/js/geokml.js', __FILE__));
            wp_enqueue_script('woo-overlapping', plugins_url('/assets/js/overlapping.js', __FILE__));           
        }

        add_action('wp_footer', array(&$this, 'woo_view_js_set_values'));
    }

    function woo_view_set_view()
    {
        $type = (isset($_GET['view'])) ? $_GET['view'] : 'list';
        $c = 0;
        ?>
        <script>
            jQuery(document).ready(function () {
                var licnt = 0;
                jQuery('#woo_view_nav_ul a').click(function () {
                    var vals = jQuery(this).attr('id');
                    jQuery('#woo_view_view').attr('value', vals);
                    jQuery('#woo_view_form').submit();
                });

                jQuery('ul.products').addClass('<?php echo $type; ?>');
                jQuery('ul.products li').each(function (e, i) {
                    jQuery(this).addClass('woo_view_map_li');
                });

                <?php foreach($this->_checkaddresses as $key=>$c){
                    if( $c == 1 ) {
                ?>
                jQuery('ul.products li').eq(<?php echo $key;?>).attr('id', 'woomap_' + licnt);
                licnt++;
                <?php }

                }?>

            });

        </script>
    <?php
    }

function woo_view_js_set_values() {
    ?>

    <script>
        <?php if( $_GET['view']=='map' && $this->_showMap){?>
        var height = 0;
        var geoXml = null;
        var map = null;
        var myLatLng = null;
        var marker = null;
        var infowindow = new google.maps.InfoWindow();
        var kml = '<?php echo trim(implode('',$this->_kml));?>';

        <?php } ?>

        jQuery(document).ready(function () {
            var container = jQuery('#wc_woo_view_scroller');

            <?php if( $_GET['view']=='map' && $this->_showMap){?>
            var i = 0;
            var cnt = <?php echo count($this->_addresses)?>;
            var addresses = new Array(cnt);
            for (var i = 0; i < cnt; i++) {
                addresses[i] = new Array(4);
            }
            i = 0;
            <?php foreach($this->_addresses as $key=>$add){?>
            addresses[i][0] = "<?php echo $add[0]?>";
            addresses[i][1] = "<?php echo $add[1]?>";
            addresses[i][2] = "<?php echo $add[2]?>";
            addresses[i][3] = "<?php echo $add[3]?>";
            i++;
            <?php }?>

            myLatLng = new google.maps.LatLng('<?php echo $this->_lat ;?>', '<?php echo  $this->_lng;?>');

            var maptype = google.maps.MapTypeId.ROADMAP;
            var myOptions = {
                mapTypeId: maptype,
                zoom: 13,
                center: myLatLng
            };
            var kmlZoom = true;
            <?php if(count(array_unique($this->_latArray))<=1 && count(array_unique($this->_lngArray))<=1){?>
            kmlZoom = false;
            <?php }?>
            i = 0;

            map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
            var oms = new OverlappingMarkerSpiderfier(map);

            geoXml = new geoXML3.parser({
                map: map,
                zoom: kmlZoom,
                createMarker: function () {
                    var lat = addresses[i][2];
                    var lng = addresses[i][3];

                    marker = new google.maps.Marker({
                        position: new google.maps.LatLng(lat, lng),
                        map: map,
                        icon: "http://googlemaps.googlermania.com/google_maps_api_v3/en/Google_Maps_Marker.png"
                    });
                    oms.addMarker(marker);
                    addresses[i][4] = marker;
                    google.maps.event.addListener(marker, 'click', (function (marker, i) {
                        return function () {
                            infowindow.setContent(addresses[i][1]);
                            infowindow.open(map, marker);
                            height = jQuery('#woomap_' + i).height();
                            container.animate({
                                scrollTop: i * height
                            });

                            jQuery('.woo_view_map_li').removeClass('selected');
                            jQuery('#woomap_' + i).addClass('selected');
                        }
                    })(marker, i));
                    i++;
                },
                failedParse: function () {
                    var a = confirm("Error Occur. Reload Again?");
                    if (a) location.reload();
                }
            });

            geoXml.parseKmlString(kml);

            jQuery('.wc_woo_view_content_wrapper').append(jQuery('.woocommerce-pagination'));
            <?php }elseif($_GET['view']=='map'){?>
            jQuery('#woo_view_view').attr('value', 'list');
            jQuery('#woo_view_form').submit();

            <?php }?>

            <?php if( $_GET['view']=='date'){?>
            var group = null;
            var elems = jQuery.makeArray(jQuery("ul.products>li"));

            elems.sort(function (a, b) {
                return new Date(jQuery(a).find('.date_view_wrapper').attr('itemid')) < new Date(jQuery(b).find('.date_view_wrapper').attr('itemid'));
            });

            jQuery('ul.products').html(elems);
            jQuery("ul.products li").each(function () {
                var d = jQuery(this).find('.date_view_wrapper').attr('itemid');
                if (group == null) {
                    group = d;
                    var formatted = jQuery(this).find('.date_view_wrapper').attr('rel');
                    jQuery("ul.products").append(jQuery('<li class="' + group + '"><div class="woo_view_date_list"><div class="woo_view_date">' + formatted + '</div></div><ul></ul></li>'));
                }

                if (d != group) {
                    group = d;
                    var formatted = jQuery(this).find('.date_view_wrapper').attr('rel');
                    jQuery("ul.products").append(jQuery('<li class="' + group + '"><div class="woo_view_date_list"><div class="woo_view_date">' + formatted + '</div></div><ul></ul></li>'));
                }
                jQuery(this).find('.date_view_wrapper').removeAttr('rel');
                jQuery(this).find('.date_view_wrapper').removeAttr('itemid');
                jQuery(this).appendTo(jQuery("ul.products li." + group + " ul"));

            });

            <?php }?>
        });

    </script>
<?php
}

}
    $WC_Woo_View = new WC_Woo_View();
}
}
