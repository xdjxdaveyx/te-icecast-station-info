<?php
/**
 * Plugin Name: TE Icecast 2 Station Information
 * Plugin URI: http://tauriemotum.uk/index.php/icecast-2-server-information-plugin-for-wordpress-by-tauri-emotum/
 * Description: Displays Icecast2 now playing tracks
 * Version: 1.00
 * Author: David Cropley
 * Author URI: http://tauriemotum.uk
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Disclaimer: This code is provided 'as is' and there are no guarantees with regards to compatibility or support.
 */
 
/**
 * The following function is to fetch Icecast stats and output to screen (This function is based on Open Source GPL licensed 'Icecast Now Playing' widget
 * by William J. Galway <--QDOS==:)
 * Original licence which we adhere to:
 *
 * Icecast Now Playing a widget to display Icecast server connection stats in a Wordpress blog.
 *   Copyright (C) <2010>  <William J. Galway>
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function te_show_isi_stats($server,$username,$password,$show_server,$show_mount,$mount,$mount2,$mount3)  { 
    $description1= 'Mount: '.$mount;
    $description2= 'Mount: '.$mount2;
    $description3= 'Mount: '.$mount3;


        // Read Icecast stats from server url
        $stats_file = fopen("http://$username:$password@$server/admin/stats","r");
        if (!$stats_file) {
            exit;
        }
        else {
            stream_set_timeout($stats_file, 2);
            $stats = "";
            while(!feof($stats_file))
            {
                $stats .= fread($stats_file, 8192);
            }
            fclose($stats_file);
            
            // Now parse the XML output for our mountpoint
            $xml_parser = xml_parser_create();
            xml_parse_into_struct($xml_parser, $stats, $vals, $index);
            xml_parser_free($xml_parser);
            
            $params = array();
            $level = array();
            foreach ($vals as $xml_elem) {
                if ($xml_elem['type'] == 'open') {
                    if (array_key_exists('attributes',$xml_elem)) {
                        list($level[$xml_elem['level']],$extra) =
                        array_values($xml_elem['attributes']);
                    } else {
                        $level[$xml_elem['level']] = $xml_elem['tag'];
                      }
                  }
                if ($xml_elem['type'] == 'complete') {
                    $start_level = 1;
                    $php_stmt = '$params';
                    while($start_level < $xml_elem['level']) {
                        $php_stmt .= '[$level['.$start_level.']]';
                        $start_level++;
                      }
                    $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                    eval($php_stmt);
                }
            
            $info = array(
                    'track1' => $params['ICESTATS'][$mount]['TITLE'],
                    'track2' => $params['ICESTATS'][$mount2]['TITLE'],
                    'track3' => $params['ICESTATS'][$mount3]['TITLE'],
                    'listen1' => $params['ICESTATS'][$mount]['LISTENURL'],
                    'listen2' => $params['ICESTATS'][$mount2]['LISTENURL'],
                    'listen3' => $params['ICESTATS'][$mount3]['LISTENURL'],
                    'users1' => $params['ICESTATS'][$mount]['LISTENERS'],
                    'users2' => $params['ICESTATS'][$mount2]['LISTENERS'],
                    'users3' => $params['ICESTATS'][$mount3]['LISTENERS'],
                    'server1' => $params['ICESTATS'][$mount]['SERVER_URL'],
                    'server2' => $params['ICESTATS'][$mount2]['SERVER_URL'],
                    'server3' => $params['ICESTATS'][$mount3]['SERVER_URL']);
               
            }
        
         

            // Begin Icecast connection statistics echo output
            
            $trackValue = 0;
            if ($info['track1']) $trackValue = 1;
            if ($info['track2']) $trackValue = 2;
            if ($info['track3']) $trackValue = 3;
            switch ($trackValue):
                case 1:
                  $listeners = $info[users1] + 1;
                  echo "<strong>Currently Playing: </strong><br>$info[track1]\n";
                  echo "<br/><br/>";
                  echo "<strong>Current Listeners: </strong><br>$listeners";
                  echo "<br/><br/>";
                  if ('on' == $show_mount){
                    echo "$description1<br/>";
                      } 
                break;     
                
                case 2:
                  $listeners = $info[users2] + 1;
                  echo "<strong>Currently Playing: </strong><br>$info[track2]\n";
                  echo "<br/><br/>";
                  echo "<strong>Current Listeners: </strong><br>$listeners";
                  echo "<br/><br/>";
                  if ('on' == $show_mount){
                    echo "$description2<br/>";
                      } 
                break;     
                
                case 3:
                  $listeners = $info[users3] + 1;
                  echo "<strong>Currently Playing: </strong><br>$info[track3]\n";
                  echo "<br/><br/>";
                  echo "<strong>Current Listeners: </strong><br>$listeners";
                  echo "<br/><br/>";
                  if ('on' == $show_mount){
                    echo "$description3<br/>";
                      } 
                break;     
                
                default:
                echo "Sorry - No current streaming info available!<br>There is either no stream playing or information for the current content.<br>";
                break;
            endswitch;
            
            if ('on' == $show_server) {
              echo "Server: $server";
              echo "<br/>";
            }
        }
} // end of stats function

// Instantiate widget class  
class TE_ISI_Widget extends WP_Widget {
    
    // Set up the widget name and description.
    public function __construct() {
        $widget_options = array( 'classname' => 'te_isi_widget', 'description' => 'Tauri Emotum - Icecast Station Info widget' );
        parent::__construct( 'te_isi_widget', 'TE Icecast 2 S.I. Widget', $widget_options );
    }
    
    
    // Create the widget output.
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance[ 'title' ] );
        $te_server = $instance[ 'te_serverinput' ];
        $te_username = $instance[ 'te_username' ];
        $te_password = $instance[ 'te_password' ];
        $te_server_info = $instance[ 'te_server_info' ] ? 'on' : 'off';
        $te_mount_info = $instance[ 'te_mount_info' ] ? 'on' : 'off';
        $te_mount1 = $instance[ 'te_mount1' ];
        $te_mount2 = $instance[ 'te_mount2' ];
        $te_mount3 = $instance[ 'te_mount3' ];
        $te_display = $instance[ 'te_display' ] ? 'on' : 'off';
        $blog_title = get_bloginfo( 'name' );
        // check if brand logo allowed
        if ($te_display == 'on') {$te_tagline = '<a style="border-radius: 15px 25px; background-image: linear-gradient(to bottom right, white, powderblue);" target="blank" href="http://tauriemotum.uk">Tauri Emotum</a>';}
          else {$te_tagline = '';}
        echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; 
        ?>

        <head>
          <style>
          #grad1 {
            height: 100px;
            background-color: red; /* For browsers that do not support gradients */
            background-image: linear-gradient(red, yellow, green); /* Standard syntax (must be last) */
            }
          </style>
        </head>
        <div id="grad1">
          <h5 align="center" style="color:white;">Station information for:<br></h5>
          <h5 align="center" style="color:black;"><?php echo $blog_title ?></h5>
        </div> 

 		    <button value="Refresh Page" onClick="window.location.reload(true)">
       	<?php
        // call function to display stats from within the button
        te_show_isi_stats($te_server,$te_username,$te_password,$te_server_info,$te_mount_info,$te_mount1,$te_mount2,$te_mount3); ?> 
    		<p>* Please click here to reload page *</p>
    		</button>

    		<?php
        echo $te_tagline; 
        echo $args['after_widget'];
        }



  // Create the admin area widget settings form.
  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Now Playing...'; ?>
    <h3>Tauri Emotum Icecast Stats Information</h3>
    <h4>Please setup your Icecast 2 server connection below...</h4>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
    $te_serverinput = ! empty( $instance['te_serverinput'] ) ? $instance['te_serverinput'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_serverinput' ); ?>">Server (Eg. example.com:8000):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'te_serverinput' ); ?>" name="<?php echo $this->get_field_name( 'te_serverinput' ); ?>" value="<?php echo esc_attr( $te_serverinput ); ?>" />
    </p>
    <?php
    $te_username = ! empty( $instance['te_username'] ) ? $instance['te_username'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_username' ); ?>">Username (for Icecast):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'te_username' ); ?>" name="<?php echo $this->get_field_name( 'te_username' ); ?>" value="<?php echo esc_attr( $te_username ); ?>" />
    </p>
    <?php
    $te_password = ! empty( $instance['te_password'] ) ? $instance['te_password'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_password' ); ?>">Password  (for Icecast):</label>
      <input type="password" id="<?php echo $this->get_field_id( 'te_password' ); ?>" name="<?php echo $this->get_field_name( 'te_password' ); ?>" value="<?php echo esc_attr( $te_password ); ?>" />
    </p>
    <?php
    $te_server_info = ! empty( $instance['te_server_info'] ) ? $instance['te_server_info'] : 'off';
    $te_mount_info = ! empty( $instance['te_mount_info'] ) ? $instance['te_mount_info'] : 'off'; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_server_info' ); ?>">Show server?:</label>
      <input class="checkbox" type="checkbox" <?php checked( $instance[ 'te_server_info' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'te_server_info' ); ?>" name="<?php echo $this->get_field_name( 'te_server_info' ); ?>" /> 
      <label for="<?php echo $this->get_field_id( 'te_mount_info' ); ?>">Show mountpoint? (only shows on connection):</label>
      <input class="checkbox" type="checkbox" <?php checked( $instance[ 'te_mount_info' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'te_mount_info' ); ?>" name="<?php echo $this->get_field_name( 'te_mount_info' ); ?>" /> 
    </p>
    <?php
    $te_mount1 = ! empty( $instance['te_mount1'] ) ? $instance['te_mount1'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_mount1' ); ?>">Mount 1 (Eg. /stream):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'te_mount1' ); ?>" name="<?php echo $this->get_field_name( 'te_mount1' ); ?>" value="<?php echo esc_attr( $te_mount1 ); ?>" />
    </p>
    <?php
    $te_mount2 = ! empty( $instance['te_mount2'] ) ? $instance['te_mount2'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_mount2' ); ?>">Mount 2 (optional - Eg. /stream2):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'te_mount2' ); ?>" name="<?php echo $this->get_field_name( 'te_mount2' ); ?>" value="<?php echo esc_attr( $te_mount2 ); ?>" />
    </p>
    <?php
    $te_mount3 = ! empty( $instance['te_mount3'] ) ? $instance['te_mount3'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_mount3' ); ?>">Mount 3 (optional - Eg. /stream3):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'te_mount3' ); ?>" name="<?php echo $this->get_field_name( 'te_mount3' ); ?>" value="<?php echo esc_attr( $te_mount3 ); ?>" />
    </p>
    <?php
    $te_display = ! empty( $instance['te_display'] ) ? $instance['te_display'] : 'off'; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'te_display' ); ?>">Display Tauri Emotum branding?:</label>
      <input class="checkbox" type="checkbox" <?php checked( $instance[ 'te_display' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'te_display' ); ?>" name="<?php echo $this->get_field_name( 'te_display' ); ?>" /> 
    </p>
    <?php
    if ($te_display == 'on') {
      $te_tagline = '<a style="border-radius: 15px 25px;background-image: linear-gradient(to bottom right, white, powderblue);" target="blank" href="http://tauriemotum.uk">Tauri Emotum</a>';
      echo $te_tagline;
      }
      
  }


  // Apply settings to the widget instance.
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
    $instance[ 'te_serverinput' ] = strip_tags( $new_instance[ 'te_serverinput' ] );
    $instance[ 'te_username' ] = strip_tags( $new_instance[ 'te_username' ] );
    $instance[ 'te_password' ] = strip_tags( $new_instance[ 'te_password' ] );
    $instance[ 'te_server_info' ] = $new_instance[ 'te_server_info' ] ;
    $instance[ 'te_mount_info' ] = $new_instance[ 'te_mount_info' ] ;
    $instance[ 'te_mount1' ] = $new_instance[ 'te_mount1' ] ;
    $instance[ 'te_mount2' ] = strip_tags( $new_instance[ 'te_mount2' ] ) ;
    $instance[ 'te_mount3' ] = strip_tags( $new_instance[ 'te_mount3' ] ) ;
    $instance[ 'te_display' ] = $new_instance[ 'te_display' ] ;
    return $instance;
  }

} // end of class

// Register the widget.
function te_register_isi_widget() { 
  register_widget( 'TE_ISI_Widget' );
    }
add_action( 'widgets_init', 'te_register_isi_widget' );