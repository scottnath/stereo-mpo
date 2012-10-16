<?php
/*
Plugin Name: WP Stereo MPO 
Plugin URI: http://example.com
Description: Adds support to stereo MPO files into the media upload. 
Author: Martin Sudolsky
Version: 0.1
Author URI: http://martin.sudolsky.com
*/


// load MPO class
require_once 'mpo.class.php';

// main class
class wpstereompo
{
  const ld = 'wpstereompo'; // localization domain
  const version = '0.1'; // version
  const nonce = 'wpstereompo_nonce';  

  // base URL and path of the plugin  
  public $url;
  public $path;
  
  // default options for external tools
  private $default_tools = array(
                      'exiftool' => array('path' => '', 'valid' => false),
                      'convert' => array('path' => '', 'valid' => false),
                      'composite' => array('path' => '', 'valid' => false)
                  );
  
  function __construct()
  {
    // run in the administration area            
    if (is_admin())
    {
      $path = dirname(plugin_basename(__FILE__));        
      $this->url = WP_PLUGIN_URL.'/'.$path;
      $this->path = WP_PLUGIN_DIR.'/'.$path;
                  
      // add admin menu
      add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
      add_action('admin_menu', array(&$this, 'admin_menu'));
      
      // add new extension to upload mimes
      add_action('upload_mimes', array(&$this, 'add_mpo_mime'));

      // handle upload process      
      add_action('wp_handle_upload', array(&$this, 'handle_upload'));
    }

    // register activation and uninstall hooks
    register_activation_hook(__FILE__, array(&$this, 'activation'));    
    register_uninstall_hook(__FILE__, array(__class__, 'uninstall'));  
  }
  
  // on plugin activation
  function activation()
  {
    add_option('wpstereompo_tools', $this->default_tools);
  }
  
  // on plugin uninstall
  static function uninstall()
  {
    delete_option('wpstereompo_tools');  
  }
  
  // called after plugins are loaded  
  function plugins_loaded()
  {
    // language setup
    load_plugin_textdomain(self::ld, false, $this->path.'/languages/');  
  }
  
  // add menu item to the options of the admin menu
  function admin_menu()
  {
    // add option page
    $hook = add_options_page(__('Stereo MPO', self::ld), __('Stereo MPO', self::ld), 'manage_options', __class__, array(&$this, 'options_page'));
    add_filter('plugin_action_links_'.plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2);      

    // add print actions for javascript and CSS
    add_action("admin_print_styles-$hook", array(&$this, 'options_page_css'));                             
  }
  
  // add shortcut to the options page in the installed plugins area
  function filter_plugin_actions($l, $file)
  {
    $settings_link = '<a href="options-general.php?page='.__class__.'">'.__('Settings').'</a>';
    array_unshift($l, $settings_link); 
    return $l;       
  }  

  // add CSS styles to the options page                     
  function options_page_css()
  {  
    wp_enqueue_style(__class__.'_styles', $this->url.'/backend/styles.css', array(), self::version, 'all');          
  }
  
  // options page
  function options_page()
  {
    $options_url = admin_url('options-general.php?page='.__class__);    
    require_once $this->path.'/backend/top.php';
       
    $tools = get_option('wpstereompo_tools', $this->default_tools);
         
    // save changes
    if (isset($_POST['save_changes']) && $_POST['save_changes'])
    {
      if (!wp_verify_nonce($_POST['_wpnonce'], self::nonce))
      {
        die(__('Whoops! There was a problem with the data you posted. Please go back and try again.', self::ld));
      }
      
      $tools['exiftool']['path'] = isset($_POST['wpsm_exiftool_path'])?str_replace("\\\\", "/", $_POST['wpsm_exiftool_path']):'';
      $tools['exiftool']['valid'] = $this->check_exec($tools['exiftool']['path']);

      $tools['convert']['path'] = isset($_POST['wpsm_convert_path'])?str_replace("\\\\", "/", $_POST['wpsm_convert_path']):'';
      $tools['convert']['valid'] = $this->check_exec($tools['convert']['path']);

      $tools['composite']['path'] = isset($_POST['wpsm_composite_path'])?str_replace("\\\\", "/", $_POST['wpsm_composite_path']):'';
      $tools['composite']['valid'] = $this->check_exec($tools['composite']['path']);
      
      update_option('wpstereompo_tools', $tools);
      
      echo '<div class="updated"><p>'.__('Settings were sucessfully saved.', self::ld).'</p></div>';
    }
                   
    require_once $this->path.'/backend/options.php';
  }

  // check if file is executeable
  protected function check_exec($filename)
  {
    if (!$filename) return;
    @exec($filename, $r, $ret_code);
    return $ret_code?false:true;
  }
  
  // add support for MPO mime type
  function add_mpo_mime($mimes)
  {
    $mimes['mpo'] = 'application/stereo-mpo';
    return $mimes;    
  }
  
  // process MPO file
  function handle_upload($file)
  {
    if ($file['type'] == 'application/stereo-mpo')
    {
      // initialize MPO class
      $tools = get_option('wpstereompo_tools', $this->default_tools);
      
      if (!($tools && $tools['exiftool']['valid'] &&
            $tools['convert']['valid'] && $tools['composite']['valid'])
        ) return $file;
      
      $mpo = new CMPO(isset($tools['exiftool']['path'])?$tools['exiftool']['path']:false,
                      isset($tools['convert']['path'])?$tools['convert']['path']:false,                        
                      isset($tools['composite']['path'])?$tools['composite']['path']:false);
    
    
      $filename = $file['file'];
      $info = pathinfo($filename);

      $tmp_dir = $info['dirname'].'/';
      
      // create filenames
      $left_filename = $tmp_dir.$info['filename'].'-left.jpg';      
      $right_filename = $tmp_dir.$info['filename'].'-right.jpg';
      $stereo_filename = $tmp_dir.$info['filename'].'-stereo.jpg'; 
      $anaglyph_filename = $tmp_dir.$info['filename'].'-anaglyph.jpg'; 

      // get left and right image
      $mpo->split($filename, $left_filename, $right_filename);
      $this->add_attachment($left_filename);
      $this->add_attachment($right_filename);
      
      // create anaglyph
      $mpo->anaglyph($left_filename, $right_filename, $anaglyph_filename);
      $this->add_attachment($anaglyph_filename);
      
      // create stereo image
      $mpo->stereo($left_filename, $right_filename, $stereo_filename);
      $this->add_attachment($stereo_filename);      
    }
    
    return $file;      
  }
  
  // add image as attachmend
  protected function add_attachment($filename)
  {
    global $post;
        
    $wp_filetype = wp_check_filetype(basename($filename), null);
    $wp_upload_dir = wp_upload_dir();
    $attachment = array(
      'guid' => $wp_upload_dir['baseurl']._wp_relative_upload_path($filename), 
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
      'post_content' => '',
      'post_status' => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $filename, isset($_REQUEST['post_id'])?$_REQUEST['post_id']:0);

    require_once ABSPATH.'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
    wp_update_attachment_metadata($attach_id, $attach_data);      
  }

}

new wpstereompo();